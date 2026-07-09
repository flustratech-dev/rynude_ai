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

        // Full-text search: match the title OR any message body (so the
        // sidebar search finds chats by what was said, like Claude).
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('messages', fn ($m) => $m->where('content', 'like', "%{$search}%"));
            });
        }

        $conversations = $query->orderByDesc('updated_at')->take(100)->get()
            ->map(fn (Conversation $c) => $this->transformListItem($c))
            ->filter(fn (array $c) => $this->matchesFilter($c, $filter, ''))
            ->values();

        return response()->json(['data' => $conversations]);
    }

    /**
     * Tell the client how a model must be run for the current user:
     *   - "extension": no server credentials, but a web-account (claude.ai) is
     *     connected → the browser extension must produce the answer.
     *   - "server": the normal server-side provider handles it.
     */
    public function providerMode(Request $request): JsonResponse
    {
        $model = trim((string) $request->query('model', ''));
        if ($model === '') {
            return response()->json(['mode' => 'server', 'provider' => null]);
        }

        $provider = app(\App\Services\AI\AiService::class)->resolveProvider($model);
        $isExtension = $provider instanceof \App\Services\AI\WebAiProvider;

        $webProvider = null;
        if ($isExtension) {
            $webProvider = str_starts_with($model, 'claude') ? 'claude'
                : (str_starts_with($model, 'gpt') ? 'chatgpt'
                : (str_starts_with($model, 'gemini') ? 'gemini' : null));
        }

        return response()->json([
            'mode' => $isExtension ? 'extension' : 'server',
            'provider' => $webProvider,
        ]);
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
            'edit_of' => ['nullable', 'integer', 'exists:messages,id'],
            'thinking' => ['nullable', 'boolean'],
            // Self-critique quality mode: draft → review → improved rewrite
            'quality' => ['nullable', 'boolean'],
            'style' => ['nullable', 'string', 'in:normal,concise,explanatory,formal'],
            // Answer already generated client-side by the browser extension
            // (claude.ai tab). When present the server skips the AI call and
            // just persists + streams this text back.
            'precomputed_response' => ['nullable', 'string', 'max:200000'],
        ]);

        $text = trim($validated['prompt']);
        $model = $validated['model'];
        $conversationId = $validated['conversation_id'] ?? null;
        $projectId = $validated['project_id'] ?? null;
        $webSearch = $validated['web_search'] ?? false;
        $researchMode = $validated['research_mode'] ?? false;
        $repoUrl = $validated['repo_url'] ?? null;
        $thinking = (bool) ($validated['thinking'] ?? false);
        $quality = (bool) ($validated['quality'] ?? false);
        $style = $validated['style'] ?? null;
        $precomputed = $validated['precomputed_response'] ?? null;

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

            // Give the chat a content-based title immediately so it never shows
            // as "New Chat" — this doesn't depend on a queue worker (the async
            // AI-title job below only runs when a worker + server credentials are
            // available, and would otherwise never fire under QUEUE_CONNECTION=sync
            // with the title job pinned to the database queue).
            $conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => $this->deriveTitle($text),
                'project_id' => $projectId,
                'metadata' => $metadata,
            ]);
        }

        // Editing an earlier user message forks the thread: the old message and
        // its tail become an inactive branch, the new message their sibling.
        $parentId = null;
        $editOf = $validated['edit_of'] ?? null;
        if ($editOf) {
            $edited = Message::where('id', $editOf)
                ->where('conversation_id', $conversation->id)
                ->where('role', 'user')
                ->first();
            if ($edited) {
                $parentId = $edited->ensureParentLink();
                Message::deactivateTail($conversation->id, $edited->id);
            }
        }
        if ($parentId === null) {
            $lastActive = Message::where('conversation_id', $conversation->id)
                ->where('is_active_branch', true)
                ->orderByDesc('id')
                ->first();
            $parentId = $lastActive ? $lastActive->id : 0;
        }

        // Create user message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $text,
            'parent_id' => $parentId,
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

        // Clear the autosaved draft; persist the response style when it changed
        $updates = ['draft_prompt' => null];
        if ($style !== null) {
            $updates['style'] = $style === 'normal' ? null : $style;
        }
        $conversation->update($updates);

        // Build message history for AI (active branch only)
        $messages = $this->buildAiMessages($conversation);

        // Web-account (extension) mode: the model resolves to WebAiProvider and
        // no answer was produced yet. The server can't call claude.ai (Cloudflare),
        // so — with the user message already saved above — it asks the browser
        // extension to generate the reply, which then calls complete-extension.
        // Persisting first means the chat survives even if the extension fails.
        $isExtensionMode = $precomputed === null
            && app(\App\Services\AI\AiService::class)->resolveProvider($model) instanceof \App\Services\AI\WebAiProvider;
        if ($isExtensionMode) {
            $webProvider = str_starts_with($model, 'claude') ? 'claude'
                : (str_starts_with($model, 'gpt') ? 'chatgpt'
                : (str_starts_with($model, 'gemini') ? 'gemini' : 'claude'));
            return $this->streamNeedExtension($conversation, $messages, $model, $userMessage->id, $webProvider);
        }

        return $this->streamAiResponse($conversation, $messages, $model, $webSearch, $researchMode, $userMessage->id, $thinking, $precomputed, $quality);
    }

    /**
     * Emit an SSE stream asking the client to run this turn through the browser
     * extension (claude.ai tab). Carries the built context + ids so the client
     * can call complete-extension with the answer afterwards.
     */
    private function streamNeedExtension(Conversation $conversation, array $messages, string $model, int $parentMessageId, string $webProvider = 'claude')
    {
        return response()->stream(function () use ($conversation, $messages, $model, $parentMessageId, $webProvider) {
            @ini_set('output_buffering', 'off');
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            echo ':' . str_repeat(' ', 2048) . "\n\n";
            echo 'data: ' . json_encode(['type' => 'init', 'data' => ['conversation_id' => $conversation->id]]) . "\n\n";
            echo 'data: ' . json_encode(['type' => 'need_extension', 'data' => [
                'conversation_id' => $conversation->id,
                'parent_message_id' => $parentMessageId,
                'model' => $model,
                'provider' => $webProvider,
                'messages' => $messages,
            ]]) . "\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Persist + stream an assistant reply that the browser extension generated
     * from a real claude.ai tab. Reuses the precomputed streaming path so the
     * save/artifact/title/done logic is identical to a normal generation.
     */
    public function completeExtension(Request $request, Conversation $conversation)
    {
        $this->authorizeOwnership($conversation);

        $validated = $request->validate([
            'model' => ['required', 'string'],
            'content' => ['required', 'string', 'max:200000'],
            'parent_message_id' => ['nullable', 'integer'],
        ]);

        // The precomputed path ignores $messages beyond the "last is user" guard.
        $messages = [['role' => 'user', 'content' => '.']];

        return $this->streamAiResponse(
            $conversation,
            $messages,
            $validated['model'],
            false,
            false,
            $validated['parent_message_id'] ?? null,
            false,
            $validated['content']
        );
    }

    /**
     * Build a readable chat title from the first few words of the prompt.
     */
    private function deriveTitle(string $prompt): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($prompt)));
        if ($clean === '' || $clean === '.') {
            return 'New Chat';
        }
        $words = array_slice(explode(' ', $clean), 0, 6);
        $title = mb_substr(implode(' ', $words), 0, 60);
        return mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1);
    }

    /**
     * Message history for the AI from the active branch of the thread.
     */
    private function buildAiMessages(Conversation $conversation): array
    {
        $thread = Message::activeThread($conversation->id)->with('attachments')->get();

        $messages = [];
        foreach ($thread as $msg) {
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

        return $messages;
    }

    /**
     * Stream the AI response using Server-Sent Events (SSE). Shared by send()
     * and regenerate(); $parentMessageId links the saved assistant reply to the
     * user message it answers (branching).
     */
    private function streamAiResponse(
        Conversation $conversation,
        array $messages,
        string $model,
        bool $webSearch,
        bool $researchMode,
        ?int $parentMessageId,
        bool $thinking = false,
        ?string $precomputed = null,
        bool $quality = false
    ) {
        // Resolve the service before the closure so mocks can intercept it
        $streamingService = app(\App\Services\ChatStreamingService::class);

        return response()->stream(function () use ($streamingService, $conversation, $messages, $model, $webSearch, $researchMode, $parentMessageId, $thinking, $precomputed, $quality) {
            // Keep generating even if the browser disconnects (refresh/closed
            // tab): the answer still gets saved, and the client can catch up
            // via stream-resume from the StreamBuffer mirror below.
            ignore_user_abort(true);

            // Kill every buffering layer so each token reaches the browser
            // immediately instead of arriving as one big burst at the end.
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', 'off');
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            // SSE comment padding: defeats fixed-size buffers in some web
            // servers/antivirus proxies that hold back small responses.
            echo ':' . str_repeat(' ', 2048) . "\n\n";
            flush();

            try {
                // First emit conversation ID so frontend can track it
                echo 'data: ' . json_encode([
                    'type' => 'init',
                    'data' => ['conversation_id' => $conversation->id],
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();

                // Mirror progress so a disconnected client can resume.
                $buffer = new \App\Services\StreamBuffer($conversation->id);
                $buffer->start();

                $generator = $streamingService->stream($conversation, $messages, $model, $webSearch, $researchMode, $parentMessageId, $thinking, $precomputed, $quality);

                foreach ($generator as $event) {
                    $buffer->apply($event);

                    // Format as SSE: data: {...}\n\n
                    echo 'data: ' . json_encode($event) . "\n\n";

                    // Flush output buffer to send immediately
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    if (connection_aborted() || \Illuminate\Support\Facades\Cache::get('chat_stop_' . $conversation->id)) {
                        break;
                    }
                }

                $buffer->flush();

            } catch (\Exception $e) {
                if (isset($buffer)) {
                    $buffer->apply(['type' => 'error', 'data' => $e->getMessage()]);
                }

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

    /**
     * Reattach to a generation that is (or was) running for this conversation.
     *
     * The client reports how many BYTES of content/thinking it already has
     * (UTF-8 byte counts match PHP strlen exactly, unlike JS string length);
     * the missing tail is streamed as normal SSE events, followed by the
     * stored artifact/done/error event. Emits {type: "gone"} when no buffer
     * exists (finished long ago or expired).
     */
    public function streamResume(Request $request, Conversation $conversation)
    {
        $this->authorizeOwnership($conversation);

        $contentLen = max(0, (int) $request->query('content_len', 0));
        $thinkingLen = max(0, (int) $request->query('thinking_len', 0));

        return response()->stream(function () use ($conversation, $contentLen, $thinkingLen) {
            set_time_limit(0);

            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', 'off');
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $emit = function (array $event) {
                echo 'data: ' . json_encode($event) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            // SSE comment padding (see send()).
            echo ':' . str_repeat(' ', 2048) . "\n\n";
            flush();

            $deadline = microtime(true) + 600;
            $ticks = 0;

            while (true) {
                $state = \App\Services\StreamBuffer::read($conversation->id);

                if (!$state) {
                    $emit(['type' => 'gone', 'data' => null]);
                    return;
                }

                // Stream the tail the client hasn't seen yet.
                if (strlen($state['content']) > $contentLen) {
                    $emit(['type' => 'content', 'data' => substr($state['content'], $contentLen)]);
                    $contentLen = strlen($state['content']);
                }
                if (strlen($state['thinking']) > $thinkingLen) {
                    $emit(['type' => 'thinking', 'data' => substr($state['thinking'], $thinkingLen)]);
                    $thinkingLen = strlen($state['thinking']);
                }

                if ($state['status'] === 'error') {
                    $emit(['type' => 'error', 'data' => $state['error'] ?? 'Unknown error']);
                    return;
                }

                if ($state['status'] === 'done') {
                    // 'artifact' (singular) = buffer written before the
                    // multi-artifact upgrade; replay whichever shape exists.
                    $artifactEvents = $state['artifacts'] ?? (!empty($state['artifact']) ? [$state['artifact']] : []);
                    foreach ($artifactEvents as $artifactEvent) {
                        $emit(['type' => 'artifact', 'data' => $artifactEvent]);
                    }
                    if (!empty($state['citations'])) {
                        $emit(['type' => 'citations', 'data' => $state['citations']]);
                    }
                    $emit(['type' => 'done', 'data' => $state['done']]);
                    return;
                }

                // Writer stopped updating (crashed worker / killed process).
                if ((microtime(true) - ($state['updated_at'] ?? 0)) > 90 || microtime(true) > $deadline) {
                    $emit(['type' => 'error', 'data' => 'Generation stalled on the server.']);
                    return;
                }

                // Periodic SSE comment: connection_aborted() only notices a
                // dead client after an actual write.
                if ((++$ticks % 10) === 0) {
                    echo ": ping\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                if (connection_aborted()) {
                    return;
                }

                usleep(200000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Re-answer an assistant message (optionally with a different model). The
     * old reply becomes an inactive sibling; the stream saves the new one.
     */
    public function regenerate(Request $request, Conversation $conversation)
    {
        $this->authorizeOwnership($conversation);

        $validated = $request->validate([
            'message_id' => ['required', 'integer', 'exists:messages,id'],
            'model' => ['required', 'string'],
            'web_search' => ['nullable', 'boolean'],
            'research_mode' => ['nullable', 'boolean'],
            'thinking' => ['nullable', 'boolean'],
        ]);

        $assistant = Message::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->firstOrFail();

        // Parent = the user message this reply answered. Resolve it while the
        // old reply is still on the active chain, then retire the tail.
        $parentId = $assistant->ensureParentLink();
        Message::deactivateTail($conversation->id, $assistant->id);

        $messages = $this->buildAiMessages($conversation);

        return $this->streamAiResponse(
            $conversation,
            $messages,
            $validated['model'],
            (bool) ($validated['web_search'] ?? false),
            (bool) ($validated['research_mode'] ?? false),
            $parentId ?: null,
            (bool) ($validated['thinking'] ?? false)
        );
    }

    /**
     * Make another sibling (an earlier edit/regeneration) the visible branch.
     */
    public function switchBranch(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        $validated = $request->validate([
            'message_id' => ['required', 'integer', 'exists:messages,id'],
        ]);

        $target = Message::where('id', $validated['message_id'])
            ->where('conversation_id', $conversation->id)
            ->firstOrFail();

        if ($target->parent_id === null) {
            return response()->json(['error' => 'Message is not part of a branch'], 422);
        }

        // Retire the currently visible sibling (and its tail), then bring the
        // target branch back.
        $currentSibling = Message::where('conversation_id', $conversation->id)
            ->where('parent_id', $target->parent_id)
            ->where('role', $target->role)
            ->where('is_active_branch', true)
            ->first();

        if ($currentSibling && $currentSibling->id !== $target->id) {
            Message::deactivateTail($conversation->id, $currentSibling->id);
        }

        $target->activateBranch();

        return response()->json(['switched' => true]);
    }

    /**
     * Persist thumbs up/down feedback on an assistant message.
     */
    public function rateMessage(Request $request, Message $message): JsonResponse
    {
        $conversation = $message->conversation;
        if (! $conversation || $conversation->user_id !== Auth::id()) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'rating' => ['nullable', 'string', 'in:up,down'],
        ]);

        $message->update(['rating' => $validated['rating'] ?? null]);

        return response()->json(['rating' => $message->rating]);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        // Eager load the project relationship for the response
        $conversation->load('project');

        // Mirror ChatInterface::loadConversation(): the active branch of the
        // thread with the first artifact per message and any attachments.
        // Sibling groups (edits/regenerations sharing a parent) power the
        // < 1/2 > branch navigation on the client.
        $thread = Message::activeThread($conversation->id)
            ->with(['artifacts', 'attachments'])
            ->get();

        $siblingGroups = Message::where('conversation_id', $conversation->id)
            ->whereNotNull('parent_id')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'role'])
            ->groupBy(fn ($m) => $m->parent_id . ':' . $m->role);

        $messages = $thread->map(function ($msg) use ($siblingGroups) {
            $artifactList = $msg->artifacts->map(fn ($art) => [
                'id' => $art->id,
                'type' => $art->type,
                'language' => $art->language,
                'title' => $art->title,
                'content' => $art->content,
            ])->values();
            $artifactData = $artifactList->first();

            $attachments = $msg->attachments->map(fn ($att) => [
                'file_path' => $att->file_path,
                'file_type' => $att->file_type,
                'file_name' => $att->file_name,
                'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
            ])->values();

            $siblingIds = [];
            if ($msg->parent_id !== null) {
                $group = $siblingGroups->get($msg->parent_id . ':' . $msg->role);
                if ($group && $group->count() > 1) {
                    $siblingIds = $group->pluck('id')->all();
                }
            }

            return [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'rating' => $msg->rating,
                'model' => $msg->model,
                'thinking' => $msg->thinking,
                'citations' => $msg->citations,
                'suggestions' => $msg->suggestions,
                'sibling_ids' => $siblingIds,
                'sibling_index' => $siblingIds ? array_search($msg->id, $siblingIds) : 0,
                'sibling_count' => count($siblingIds),
                'artifact' => $artifactData,
                'artifacts' => $artifactList,
                'attachments' => $attachments,
            ];
        })->values();

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'title' => $conversation->title ?? 'New Chat',
                'project_id' => $conversation->project_id,
                'project_name' => $conversation->project ? $conversation->project->name : null,
                'style' => $conversation->style,
                'draft_prompt' => $conversation->draft_prompt,
                'archived' => $conversation->archived_at !== null,
                'is_starred' => (bool) $conversation->is_starred,
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
            'is_starred' => ['sometimes', 'boolean'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
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

        // Star / unstar — pinned to the "Starred" sidebar section.
        if (array_key_exists('is_starred', $validated)) {
            $conversation->is_starred = (bool) $validated['is_starred'];
        }

        // Add to project / remove from project.
        if (array_key_exists('project_id', $validated)) {
            $conversation->project_id = $validated['project_id'];
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
            'is_starred' => (bool) $conversation->is_starred,
            'group' => $conversation->is_starred ? 'Starred' : $this->determineGroup($conversation->updated_at),
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
