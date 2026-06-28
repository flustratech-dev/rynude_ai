<?php

namespace App\Services\AI;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

/**
 * Maintains a durable, model-agnostic "memory" for a conversation.
 *
 * Why this exists: generateResponse() only sends a sliding window of recent
 * messages to the model, and the user can switch models mid-chat. Neither the
 * window nor any single model holds long-term state. This service distills the
 * conversation into a compact set of durable facts stored on the conversation
 * row and injects them into the system prompt on every turn — so the assistant
 * keeps its memory regardless of how long the chat gets or which model answers.
 *
 * Memory is stored as **structured JSON** so that:
 *   - format preferences (font, spacing, margins) persist across chapters,
 *   - per-chapter (BAB) summaries can be referenced individually when the user
 *     says "perbaiki Bab 2",
 *   - dosen / penguji revisions don't get summarized away by the next refresh,
 *   - the AI sees a clean, sectioned brief instead of a wall of bullets.
 *
 * Backward compat: a legacy plain-text memory is still recognised and surfaced
 * as `free_text`; it gets converted to the new shape on the next refresh.
 */
class ConversationMemoryService
{
    /** Re-summarize once this many new messages have accumulated since the last sync. */
    private const REFRESH_THRESHOLD = 6;

    /** Hard cap on stored memory size to keep the system prompt lean. */
    private const MAX_MEMORY_CHARS = 12000;

    public function __construct(private AiService $ai) {}

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

        $memory = $this->decodeMemory((string) $conversation->memory);
        $body = $this->renderMemory($memory);
        if ($body === '') {
            return '';
        }

        return "\n\n--- PERSISTENT CONVERSATION MEMORY ---\n"
            . "Durable facts established earlier in THIS conversation. They remain true "
            . "even though older messages may have scrolled out of context, and even though "
            . "the answering model may have changed mid-chat. Treat them as ground truth "
            . "and stay consistent with them.\n\n"
            . $body
            . "\n--- END MEMORY ---";
    }

    /**
     * Render a parsed memory array into a Markdown brief that an AI can act on.
     */
    private function renderMemory(array $memory): string
    {
        $sections = [];

        if (!empty($memory['user_profile'])) {
            $sections[] = "### User Profile\n" . $this->renderAssoc($memory['user_profile']);
        }
        if (!empty($memory['skripsi_meta'])) {
            $sections[] = "### Skripsi / Document Metadata\n" . $this->renderAssoc($memory['skripsi_meta']);
        }
        if (!empty($memory['format_preferences'])) {
            $sections[] = "### Format Preferences (always apply when generating documents)\n"
                . $this->renderAssoc($memory['format_preferences']);
        }
        if (!empty($memory['chapters']) && is_array($memory['chapters'])) {
            $lines = [];
            foreach ($memory['chapters'] as $key => $summary) {
                $title = is_string($key) ? $key : ('Chapter ' . ($key + 1));
                $body = trim((string) $summary);
                if ($body === '') continue;
                $lines[] = "- **{$title}**: {$body}";
            }
            if ($lines) {
                $sections[] = "### Chapter / Bab Summaries\n" . implode("\n", $lines);
            }
        }
        if (!empty($memory['revisi']) && is_array($memory['revisi'])) {
            $lines = [];
            foreach ($memory['revisi'] as $item) {
                $body = trim((string) $item);
                if ($body === '') continue;
                $lines[] = "- {$body}";
            }
            if ($lines) {
                $sections[] = "### Revisi Dosen / Penguji (apply these)\n" . implode("\n", $lines);
            }
        }
        if (!empty($memory['decisions']) && is_array($memory['decisions'])) {
            $lines = [];
            foreach ($memory['decisions'] as $item) {
                $body = trim((string) $item);
                if ($body === '') continue;
                $lines[] = "- {$body}";
            }
            if ($lines) {
                $sections[] = "### Decisions & Constraints\n" . implode("\n", $lines);
            }
        }
        if (!empty($memory['open_tasks']) && is_array($memory['open_tasks'])) {
            $lines = [];
            foreach ($memory['open_tasks'] as $item) {
                $body = trim((string) $item);
                if ($body === '') continue;
                $lines[] = "- {$body}";
            }
            if ($lines) {
                $sections[] = "### Open Tasks\n" . implode("\n", $lines);
            }
        }
        if (!empty($memory['facts']) && is_array($memory['facts'])) {
            $lines = [];
            foreach ($memory['facts'] as $item) {
                $body = trim((string) $item);
                if ($body === '') continue;
                $lines[] = "- {$body}";
            }
            if ($lines) {
                $sections[] = "### Other Durable Facts\n" . implode("\n", $lines);
            }
        }
        if (!empty($memory['free_text'])) {
            $sections[] = "### Notes\n" . trim((string) $memory['free_text']);
        }

        return trim(implode("\n\n", $sections));
    }

    /** Render an associative array as `- key: value` bullets. */
    private function renderAssoc(array $assoc): string
    {
        $lines = [];
        foreach ($assoc as $k => $v) {
            if ($v === null || $v === '' || $v === []) continue;
            if (is_array($v)) $v = implode(', ', array_map('strval', $v));
            $lines[] = '- ' . $k . ': ' . trim((string) $v);
        }
        return implode("\n", $lines);
    }

    /**
     * Rebuild the conversation memory from the full message list.
     *
     * `$messages` is the in-memory array of ['role' => …, 'content' => …]
     * entries (system messages are ignored). `$model` is the currently selected
     * model, reused here so summarization always runs against a provider the
     * user has keys for. Failures are swallowed and logged — memory is an
     * enhancement, never a blocker for the user's actual answer.
     */
    public function refresh(Conversation $conversation, array $messages, string $model): void
    {
        $transcript = $this->buildTranscript($messages);
        if ($transcript === '') {
            return;
        }

        $existingDecoded = $this->decodeMemory((string) $conversation->memory);
        $existingJson = json_encode($existingDecoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $instruction = <<<PROMPT
You are a memory-keeper for an ongoing chat between a user (usually an Indonesian student) and an AI writing assistant. Maintain a compact, durable, structured record of everything a future responder would need to stay consistent, even with no access to the earlier transcript.

Update the EXISTING MEMORY using the CONVERSATION below. Output ONLY the FULL updated memory as a single JSON object. No prose, no markdown, no code fences.

Required JSON shape (omit any slot you have no data for):

{
  "user_profile": {                                  // who the user is
    "name": "...", "role": "...", "institution": "..."
  },
  "skripsi_meta": {                                  // document being written
    "judul": "...", "nim": "...", "prodi": "...",
    "fakultas": "...", "universitas": "...",
    "pembimbing": "...", "tahun": "..."
  },
  "format_preferences": {                            // applies to every generated doc
    "mode": "skripsi | laporan | jurnal | document",
    "font": "Times New Roman | Arial | ...",
    "font_size": 12,
    "line_spacing": 1.5,
    "align": "justify | left",
    "margin_top": 4, "margin_right": 3, "margin_bottom": 3, "margin_left": 4,
    "page_number": "bottom-center | top-right | ..."
  },
  "chapters": {                                      // per-bab one-paragraph summary
    "BAB I PENDAHULUAN": "...",
    "BAB II TINJAUAN PUSTAKA": "..."
  },
  "revisi": ["..."],                                 // every dosen/penguji revision request
  "decisions": ["..."],                              // major user decisions / preferences
  "open_tasks": ["..."],                             // unresolved TODOs from this chat
  "facts": ["..."]                                   // anything durable that doesn't fit above
}

Rules:
- KEEP existing slots unless the conversation explicitly changes them. NEVER drop chapter summaries — append/update only.
- Be terse: each chapter summary ≤ 200 words. Each revisi item one sentence.
- Drop small talk, pleasantries, and anything already superseded.
- If the user states a format preference (font, margin, spacing, mode), record it in format_preferences immediately.
- Never invent facts.
- Hard limit: the whole JSON must stay under 9000 characters.

EXISTING MEMORY (JSON):
{$existingJson}

CONVERSATION:
{$transcript}

Reply with ONLY the updated JSON object.
PROMPT;

        try {
            $stream = $this->ai->streamResponse(
                [['role' => 'user', 'content' => $instruction]],
                $model
            );

            $raw = '';
            foreach ($stream as $chunk) {
                $raw .= $chunk;
            }
            $raw = trim($raw);
            if ($raw === '') {
                $conversation->update([
                    'memory_synced_count' => count($messages),
                    'memory_updated_at' => now(),
                ]);
                return;
            }

            $parsed = $this->extractJson($raw);
            if ($parsed === null) {
                // Model returned text instead of JSON — stash it as free_text so
                // nothing is lost and we don't overwrite previous structured memory.
                $merged = $existingDecoded;
                $merged['free_text'] = mb_substr($raw, 0, 4000);
                $parsed = $merged;
            }

            $encoded = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                Log::warning('ConversationMemory: failed to encode parsed JSON memory.');
                return;
            }

            if (mb_strlen($encoded) > self::MAX_MEMORY_CHARS) {
                // Compact (no pretty-print) then truncate as a last resort.
                $encoded = mb_substr($encoded, 0, self::MAX_MEMORY_CHARS);
            }

            $conversation->update([
                'memory' => $encoded,
                'memory_synced_count' => count($messages),
                'memory_updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ConversationMemory refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse the stored memory column. Returns a normalised array. Handles:
     *   - New JSON shape ({"chapters":…})
     *   - Legacy plain text (wrapped under 'free_text')
     *   - Empty / whitespace-only (empty array)
     */
    private function decodeMemory(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return ['free_text' => $raw];
    }

    /**
     * Try hard to extract a JSON object from a model response. Strips code
     * fences if present, then locates the outermost `{…}` block.
     */
    private function extractJson(string $raw): ?array
    {
        // Strip ```json fences if the model added them despite being told not to.
        $stripped = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw) ?? $raw;
        $stripped = trim($stripped);

        $decoded = json_decode($stripped, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Last-resort: scan for the first '{' and try increasingly-large
        // substrings until one parses. This handles "Here's the JSON: { … }".
        $first = strpos($stripped, '{');
        $last = strrpos($stripped, '}');
        if ($first === false || $last === false || $last <= $first) {
            return null;
        }
        $candidate = substr($stripped, $first, $last - $first + 1);
        $decoded = json_decode($candidate, true);
        return is_array($decoded) ? $decoded : null;
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
