<?php

namespace App\Services\AI;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

/**
 * Maintains a durable, model-agnostic "memory" for a conversation.
 *
 * Why this exists: generateResponse() only sends a sliding window of the most
 * recent messages to the model, and the user can switch models mid-chat. Neither
 * the window nor any single model holds long-term state. This service distills the
 * conversation into a compact set of durable facts stored on the conversation row,
 * which is then injected into the system prompt on every turn — so the assistant
 * keeps its memory regardless of how long the chat gets or which model is answering.
 */
class ConversationMemoryService
{
    /** Re-summarize once this many new messages have accumulated since the last sync. */
    private const REFRESH_THRESHOLD = 6;

    /** Hard cap on stored memory size to keep the system prompt lean. */
    private const MAX_MEMORY_CHARS = 4000;

    public function __construct(private AiService $ai) {}

    /**
     * True when enough new messages exist to warrant rebuilding the memory.
     */
    public function shouldRefresh(Conversation $conversation, int $messageCount): bool
    {
        $synced = $conversation->memory_synced_count ?? 0;
        return ($messageCount - $synced) >= self::REFRESH_THRESHOLD;
    }

    /**
     * Render the stored memory as a system-prompt fragment. Empty when there's none.
     */
    public function formatForPrompt(?Conversation $conversation): string
    {
        if (!$conversation || empty(trim((string) $conversation->memory))) {
            return '';
        }

        return "\n\n--- PERSISTENT CONVERSATION MEMORY ---\n"
            . "These are durable facts established earlier in THIS conversation. They remain "
            . "true even though older messages may have scrolled out of your context window, "
            . "and even though the answering model may have changed mid-chat. Treat them as "
            . "ground truth and stay consistent with them:\n\n"
            . trim((string) $conversation->memory)
            . "\n--- END MEMORY ---";
    }

    /**
     * Rebuild the conversation memory from the full message list.
     *
     * `$messages` is the in-memory array of ['role' => ..., 'content' => ...] entries
     * (system messages are ignored). `$model` is the currently selected model, reused
     * here so the summarization always runs against a provider the user has keys for.
     *
     * Failures are swallowed and logged — memory is an enhancement, never a blocker
     * for the user's actual answer.
     */
    public function refresh(Conversation $conversation, array $messages, string $model): void
    {
        $transcript = $this->buildTranscript($messages);
        if ($transcript === '') {
            return;
        }

        $existing = trim((string) $conversation->memory);
        $existingBlock = $existing !== '' ? $existing : '(no memory recorded yet)';

        $instruction = <<<PROMPT
You are a memory-keeper for an ongoing chat. Maintain a compact, durable record of
everything a future responder would need to stay consistent, even with no access to
the earlier transcript.

Update the EXISTING MEMORY using the CONVERSATION below. Output the FULL updated memory
(not a diff). Rules:
- Keep only durable, reusable facts: the user's identity/role, stated preferences,
  goals, decisions made, constraints, names/IDs/values, and unresolved open tasks.
- Drop small talk, pleasantries, and anything already superseded by a newer fact.
- Be terse. Use short bullet points grouped under headings when helpful.
- Never invent facts. If nothing durable exists yet, output exactly: (none)
- Hard limit: stay under 1800 words.

EXISTING MEMORY:
{$existingBlock}

CONVERSATION:
{$transcript}

Reply with ONLY the updated memory text. No preamble, no explanation.
PROMPT;

        try {
            $stream = $this->ai->streamResponse(
                [['role' => 'user', 'content' => $instruction]],
                $model
            );

            $memory = '';
            foreach ($stream as $chunk) {
                $memory .= $chunk;
            }
            $memory = trim($memory);

            // Model signalled "nothing durable" — keep whatever we already had.
            if ($memory === '' || strcasecmp($memory, '(none)') === 0) {
                $conversation->update([
                    'memory_synced_count' => count($messages),
                    'memory_updated_at' => now(),
                ]);
                return;
            }

            if (mb_strlen($memory) > self::MAX_MEMORY_CHARS) {
                $memory = mb_substr($memory, 0, self::MAX_MEMORY_CHARS);
            }

            $conversation->update([
                'memory' => $memory,
                'memory_synced_count' => count($messages),
                'memory_updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ConversationMemory refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Flatten the message array into a plain transcript for the summarizer.
     */
    private function buildTranscript(array $messages): string
    {
        $lines = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? '';
            if ($role !== 'user' && $role !== 'assistant') {
                continue;
            }
            $content = trim((string) ($msg['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $label = $role === 'user' ? 'User' : 'Assistant';
            $lines[] = "{$label}: {$content}";
        }

        return trim(implode("\n\n", $lines));
    }
}
