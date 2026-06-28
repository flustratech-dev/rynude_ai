<?php

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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

    public function send(): JsonResponse
    {
        return $this->pendingMigration('chat.send', 'App\\Livewire\\ChatInterface::sendMessage', 'Phase 5');
    }

    public function stop(): JsonResponse
    {
        return $this->pendingMigration('chat.stop', 'App\\Livewire\\ChatInterface::stopGeneration', 'Phase 5');
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
            ],
        ]);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeOwnership($conversation);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'archived' => ['sometimes', 'boolean'],
        ]);

        // Rename (ChatsPanel::renameConversation caps the title at 255 chars).
        if (array_key_exists('title', $validated)) {
            $conversation->title = Str::limit(trim($validated['title']), 255, '');
        }

        // Archive / unarchive (ChatsPanel::archiveConversation sets/clears the flag).
        if (array_key_exists('archived', $validated)) {
            $conversation->archived_at = $validated['archived'] ? now() : null;
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
}
