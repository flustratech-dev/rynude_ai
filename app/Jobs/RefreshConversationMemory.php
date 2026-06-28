<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AI\ConversationMemoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Async refresh of a conversation's persistent memory.
 *
 * Why a job: the refresh runs an LLM call (multi-second). Doing it inline at
 * the tail of generateResponse() forced the user to wait after their answer
 * had already streamed. Dispatched here, the message round-trip ends cleanly
 * and the queue worker handles the rebuild in the background.
 *
 * Falls back gracefully if no queue worker is running — Laravel's default
 * `sync` driver executes inline, matching the old behaviour.
 */
class RefreshConversationMemory implements ShouldQueue
{
    use Queueable;

    public int $conversationId;
    public string $model;

    public function __construct(int $conversationId, string $model = 'claude-haiku-4-5')
    {
        $this->conversationId = $conversationId;
        $this->model = $model;
    }

    public function handle(ConversationMemoryService $memory): void
    {
        try {
            $conversation = Conversation::find($this->conversationId);
            if (! $conversation) {
                return;
            }

            // Rebuild from the DB (not from in-memory state passed at dispatch
            // time) so the job sees the latest reality even if more messages
            // arrived between dispatch and execution.
            $messages = Message::where('conversation_id', $conversation->id)
                ->orderBy('id')
                ->get(['role', 'content'])
                ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $memory->refresh($conversation, $messages, $this->model);
        } catch (\Throwable $e) {
            Log::warning('RefreshConversationMemory job failed: ' . $e->getMessage());
        }
    }
}
