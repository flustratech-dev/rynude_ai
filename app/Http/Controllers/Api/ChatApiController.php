<?php

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Chat REST surface.
 *
 * Migrated from the Livewire chat components without altering behaviour:
 *   - GET    /api/chats              -> ChatsPanel::loadConversations()/getFilteredConversations()
 *   - GET    /api/chats/{id}         -> ChatInterface::loadConversation()
 *   - PATCH  /api/chats/{id}         -> ChatsPanel::renameConversation()/archiveConversation()
 *   - DELETE /api/chats/{id}         -> ChatsPanel::deleteConversation()
 *   - POST   /api/chats/{id}/share   -> ChatsPanel::shareConversation()
 *
 * send()/stop() drive the live token stream (ChatInterface::sendMessage +
 * generateResponse / stopGeneration). That pipeline is owned by Phase 5
 * (Streaming Migration) and intentionally keeps the uniform 501 contract so the
 * streaming surface is migrated as one coherent unit rather than half here.
 */
class ChatApiController extends ApiController
{
    /** Date filters accepted by index(), mirroring ChatsPanel::$filterType. */
    private const FILTERS = ['all', 'today', 'week'];

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $filter = $request->query('filter', 'all');
        if (! in_array($filter, self::FILTERS, true)) {
            $filter = 'all';
        }
        $showArchived = $request->boolean('archived');

        // Mirror ChatsPanel::loadConversations(): newest 100 for the user, with
        // archived rows toggled by the `archived` flag.
        $query = Conversation::where('user_id', Auth::id());
        if ($showArchived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        $conversations = $query->orderByDesc('updated_at')->take(100)->get()
            ->map(fn (Conversation $c) => $this->transformListItem($c))
            ->filter(fn (array $c) => $this->matchesFilter($c, $filter, $search))
            ->values();

        return response()->json(['data' => $conversations]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:10000'],
            'model' => ['required', 'string'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'web_search' => ['nullable', 'boolean'],
            'research_mode' => ['nullable', 'boolean'],
            'repo_url' => ['nullable', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:50000'], // 50MB per file
        ]);

        $text = trim($validated['prompt']);
        $model = $validated['model'];
        $conversationId = $validated['conversation_id'] ?? null;
        $projectId = $validated['project_id'] ?? null;
        $webSearch = $validated['web_search'] ?? false;
        $researchMode = $validated['research_mode'] ?? false;
        $repoUrl = $validated['repo_url'] ?? null;

        // Create or load conversation
        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
        } else {
            $metadata = [];
            if ($repoUrl) {
                $parsed = \App\Services\GitHubService::parseUrl($repoUrl);
                if ($parsed) {
                    $token = Auth::user()->github_token ?? null;
                    $github = new \App\Services\GitHubService($token);
                    $info = $github->getRepoInfo($parsed['owner'], $parsed['repo']);
                    if ($info) {
                        $tree = $github->fetchTree($parsed['owner'], $parsed['repo'], $info['default_branch']);
                        $repoTree = [];
                        foreach ($tree as $item) {
                            $depth = substr_count($item['path'], '/');
                            $name = basename($item['path']);
                            $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'txt';
                            $repoTree[] = [
                                'type' => $item['type'] === 'tree' ? 'dir' : 'file',
                                'name' => $name,
                                'path' => $item['path'],
                                'depth' => $depth,
                                'extra' => $item['type'] === 'tree' ? false : strtolower($ext)
                            ];
                        }
                        $metadata = [
                            'repo' => $parsed['owner'] . '/' . $parsed['repo'],
                            'repoTree' => $repoTree,
                        ];
                    }
                }
            }

            $conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => 'New Chat',
                'project_id' => $projectId,
                'metadata' => $metadata,
            ]);
        }

        // Create user message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $text,
        ]);

        // Handle file attachments
        $attachmentData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachment = \App\Models\MessageAttachment::create([
                    'message_id' => $userMessage->id,
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_name' => $file->getClientOriginalName(),
                ]);

                $attachmentData[] = [
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_name' => $file->getClientOriginalName(),
                ];
            }
        }

        // Clear the autosaved draft
        $conversation->update(['draft_prompt' => null]);

        // Build message history for AI
        $conversation->load(['messages.artifacts', 'messages.attachments']);
        $messages = [];

        foreach ($conversation->messages as $msg) {
            $msgData = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];

            // Include attachments in message context if present
            if ($msg->attachments->isNotEmpty()) {
                $msgData['attachments'] = $msg->attachments->map(fn($att) => [
                    'file_path' => $att->file_path,
                    'file_type' => $att->file_type,
                    'file_name' => $att->file_name,
                ])->toArray();
            }

            $messages[] = $msgData;
        }

        // Stream the AI response using Server-Sent Events (SSE)
        // Resolve the service before the closure so mocks can intercept it
        $streamingService = app(\App\Services\ChatStreamingService::class);

        return response()->stream(function () use ($streamingService, $conversation, $messages, $model, $webSearch, $researchMode) {
            // Kill every buffering layer so each token reaches the browser
            // immediately instead of arriving as one big burst at the end.
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', 'off');
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            try {
                // First emit conversation ID so frontend can track it
                echo 'data: ' . json_encode([
                    'type' => 'init',
                    'data' => ['conversation_id' => $conversation->id],
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();

                $generator = $streamingService->stream($conversation, $messages, $model, $webSearch, $researchMode);

                foreach ($generator as $event) {
                    // Format as SSE: data: {...}\n\n
                    echo 'data: ' . json_encode($event) . "\n\n";

                    // Flush output buffer to send immediately
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

            } catch (\Exception $e) {
                // Send error event
                echo 'data: ' . json_encode([
                    'type' => 'error',
                    'data' => $e->getMessage(),
                ]) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Connection' => 'keep-alive',
        ]);
    }

    public function stop(Request $request): JsonResponse
    {
        $conversationId = $request->input('conversation_id');

        if (!$conversationId) {
            return response()->json(['error' => 'conversation_id required'], 400);
        }

        // Verify ownership
        $owns = Conversation::where('id', $conversationId)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$owns) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Set cache flag that the streaming loop checks
        \Illuminate\Support\Facades\Cache::put('chat_stop_' . $conversationId, true, 120);

        return response()->json(['stopped' => true]);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        // Mirror ChatInterface::loadConversation(): full message thread with the
        // first artifact per message and any attachments.
        $conversation->load(['messages.artifacts', 'messages.attachments']);

        $messages = $conversation->messages->map(function ($msg) {
            $artifactData = null;
            if ($msg->artifacts->isNotEmpty()) {
                $art = $msg->artifacts->first();
                $artifactData = [
                    'id' => $art->id,
                    'type' => $art->type,
                    'language' => $art->language,
                    'title' => $art->title,
                    'content' => $art->content,
                ];
            }

            $attachments = $msg->attachments->map(fn ($att) => [
                'file_path' => $att->file_path,
                'file_type' => $att->file_type,
                'file_name' => $att->file_name,
            ])->values();

            return [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'rating' => $msg->rating,
                'artifact' => $artifactData,
                'attachments' => $attachments,
            ];
        })->values();

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'title' => $conversation->title ?? 'New Chat',
                'project_id' => $conversation->project_id,
                'draft_prompt' => $conversation->draft_prompt,
                'archived' => $conversation->archived_at !== null,
                'shared' => ! empty($conversation->share_token),
                'share_url' => $conversation->share_token ? route('chat.shared', $conversation->share_token) : null,
                'created_at' => optional($conversation->created_at)->toIso8601String(),
                'updated_at' => optional($conversation->updated_at)->toIso8601String(),
                'messages' => $messages,
                'metadata' => $conversation->metadata,
            ],
        ]);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'archived' => ['sometimes', 'boolean'],
            'unshare' => ['sometimes', 'boolean'],
        ]);

        // Rename (ChatsPanel::renameConversation caps the title at 255 chars).
        if (array_key_exists('title', $validated)) {
            $conversation->title = Str::limit(trim($validated['title']), 255, '');
        }

        // Archive / unarchive (ChatsPanel::archiveConversation sets/clears the flag).
        if (array_key_exists('archived', $validated)) {
            $conversation->archived_at = $validated['archived'] ? now() : null;
        }

        // Unshare (ChatsPanel::unshareConversation clears the share token).
        if (array_key_exists('unshare', $validated) && $validated['unshare']) {
            $conversation->share_token = null;
        }

        $conversation->save();

        return response()->json(['data' => $this->transformListItem($conversation->fresh())]);
    }

    public function destroy(Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        // Mirror ChatsPanel::deleteConversation(): drop the messages, then the chat.
        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['deleted' => true]);
    }

    public function share(Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        // Mirror ChatsPanel::shareConversation(): generate the token once and
        // reuse it on subsequent calls (idempotent).
        if (empty($conversation->share_token)) {
            $conversation->share_token = Str::random(32);
            $conversation->save();
        }

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'shared' => true,
                'share_token' => $conversation->share_token,
                'share_url' => route('chat.shared', $conversation->share_token),
            ],
        ]);
    }

    public function export(Request $request, Conversation $conversation)
    {
        $this->authorizeOwnership($conversation);

        $format = $request->query('format', 'md');
        $conversation->load('messages');

        $title = $conversation->title ?: 'chat';
        $slug = Str::slug($title) ?: 'chat';

        if ($format === 'json') {
            $payload = [
                'title' => $conversation->title,
                'created_at' => optional($conversation->created_at)->toIso8601String(),
                'messages' => $conversation->messages->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                ])->toArray(),
            ];
            $data = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return response()->streamDownload(function () use ($data) {
                echo $data;
            }, $slug . '.json', ['Content-Type' => 'application/json']);
        }

        // Default: Markdown
        $lines = ["# {$title}", ''];
        foreach ($conversation->messages as $m) {
            $who = $m->role === 'user' ? 'You' : 'Rynude';
            $lines[] = "## {$who}";
            $lines[] = '';
            $lines[] = $m->content;
            $lines[] = '';
        }
        $md = implode("\n", $lines);

        return response()->streamDownload(function () use ($md) {
            echo $md;
        }, $slug . '.md', ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function authorizeOwnership(Conversation $conversation): void
    {
        abort_unless($conversation->user_id === Auth::id(), 403);
    }

    /**
     * Shape a conversation for the list, matching the array ChatsPanel built for
     * the Blade view (id, title, preview, grouping, share state).
     *
     * @return array<string, mixed>
     */
    private function transformListItem(Conversation $conversation): array
    {
        $lastMessage = $conversation->messages()->latest('id')->first();
        $preview = $lastMessage
            ? Str::limit(strip_tags((string) $lastMessage->content), 80)
            : null;

        return [
            'id' => $conversation->id,
            'title' => $conversation->title ?? 'New Chat',
            'preview' => $preview,
            'updated_at' => optional($conversation->updated_at)->format('Y-m-d'),
            'archived' => $conversation->archived_at !== null,
            'shared' => ! empty($conversation->share_token),
            'share_url' => $conversation->share_token ? route('chat.shared', $conversation->share_token) : null,
            'group' => $this->determineGroup($conversation->updated_at),
        ];
    }

    /** Bucket a conversation by recency — copied verbatim from ChatsPanel. */
    private function determineGroup(?Carbon $date): string
    {
        if (! $date) {
            return 'Older';
        }
        if ($date->isToday()) {
            return 'Today';
        }
        if ($date->isYesterday()) {
            return 'Yesterday';
        }
        if ($date->greaterThanOrEqualTo(now()->subDays(7))) {
            return 'Previous 7 days';
        }
        return 'Older';
    }

    /**
     * Apply the date filter + title search exactly as
     * ChatsPanel::getFilteredConversations() did (in-memory, post-query).
     *
     * @param  array<string, mixed>  $conversation
     */
    private function matchesFilter(array $conversation, string $filter, string $search): bool
    {
        if ($filter === 'today' && $conversation['group'] !== 'Today') {
            return false;
        }
        if ($filter === 'week'
            && ! in_array($conversation['group'], ['Today', 'Yesterday', 'Previous 7 days'], true)) {
            return false;
        }
        if ($search !== ''
            && ! str_contains(strtolower((string) $conversation['title']), strtolower($search))) {
            return false;
        }
        return true;
    }

    public function connectRepo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'repo_url' => ['required', 'string'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
        ]);

        $url = trim($validated['repo_url']);
        $conversationId = $validated['conversation_id'] ?? null;

        $parsed = \App\Services\GitHubService::parseUrl($url);
        if (!$parsed) {
            return response()->json(['error' => 'Only GitHub repository URLs are supported currently.'], 422);
        }

        $token = Auth::user()->github_token ?? null;
        $github = new \App\Services\GitHubService($token);

        $info = $github->getRepoInfo($parsed['owner'], $parsed['repo']);
        if (!$info) {
            return response()->json(['error' => 'Repository not found or requires a GitHub token in Settings.'], 404);
        }

        $tree = $github->fetchTree($parsed['owner'], $parsed['repo'], $info['default_branch']);
        
        $repoTree = [];
        foreach ($tree as $item) {
            $depth = substr_count($item['path'], '/');
            $name = basename($item['path']);
            $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'txt';
            $repoTree[] = [
                'type' => $item['type'] === 'tree' ? 'dir' : 'file',
                'name' => $name,
                'path' => $item['path'],
                'depth' => $depth,
                'extra' => $item['type'] === 'tree' ? false : strtolower($ext)
            ];
        }

        $repoConnected = $parsed['owner'] . '/' . $parsed['repo'];

        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where('user_id', Auth::id())
                ->first();
            if ($conversation) {
                $meta = $conversation->metadata ?? [];
                $meta['repo'] = $repoConnected;
                $meta['repoTree'] = $repoTree;
                $conversation->update(['metadata' => $meta]);
            }
        }

        return response()->json([
            'repo' => $repoConnected,
            'repo_tree' => $repoTree,
        ]);
    }

    public function disconnectRepo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
        ]);

        $conversationId = $validated['conversation_id'] ?? null;

        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where('user_id', Auth::id())
                ->first();
            if ($conversation) {
                $meta = $conversation->metadata ?? [];
                $meta['repo'] = '';
                $meta['repoTree'] = [];
                $meta['selectedFilesContext'] = [];
                $conversation->update(['metadata' => $meta]);
            }
        }

        return response()->json(['success' => true]);
    }
}
