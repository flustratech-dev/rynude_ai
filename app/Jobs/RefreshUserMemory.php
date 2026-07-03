<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Distill the user's per-conversation memories into one durable cross-chat
 * profile (users.ai_memory) — stable facts about who they are and what they're
 * working on, so a brand-new chat already knows them. Mirrors GenerateChatTitle:
 * runs on the database queue, uses Auth::setUser() for API-key context.
 */
class RefreshUserMemory implements ShouldQueue
{
    use Queueable;

    private const MAX_MEMORY_CHARS = 4000;

    public function __construct(
        public int $userId,
        public string $model,
    ) {}

    public function handle(): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user) {
                return;
            }
            Auth::setUser($user);

            // Source material: the most recent conversations that have memory.
            $memories = Conversation::where('user_id', $user->id)
                ->whereNotNull('memory')
                ->where('memory', '!=', '')
                ->orderByDesc('updated_at')
                ->take(10)
                ->get(['title', 'memory'])
                ->map(fn ($c) => "### Chat: {$c->title}\n" . mb_substr((string) $c->memory, 0, 3000))
                ->implode("\n\n");

            if (trim($memories) === '') {
                $user->forceFill(['ai_memory_synced_at' => now()])->save();
                return;
            }

            $existing = trim((string) $user->ai_memory);

            $prompt = "You maintain a durable CROSS-CONVERSATION profile of a user of an AI assistant. "
                . "From the per-conversation memories below, update the existing profile. Keep ONLY stable, reusable facts: "
                . "who the user is (name, role, institution), long-running projects (e.g. judul skripsi, prodi), and standing preferences "
                . "(language, formatting, tone). EXCLUDE anything conversation-specific (individual revisions, one-off tasks, chapter contents).\n\n"
                . "Reply with ONLY the updated profile as short Markdown bullets, max 15 bullets, max 3500 characters. No preamble.\n\n"
                . "EXISTING PROFILE:\n" . ($existing !== '' ? $existing : '(kosong)') . "\n\n"
                . "PER-CONVERSATION MEMORIES:\n" . mb_substr($memories, 0, 24000);

            $stream = (new AiService())->streamResponse([['role' => 'user', 'content' => $prompt]], $this->model);
            $result = '';
            foreach ($stream as $chunk) {
                if (is_string($chunk)) {
                    $result .= $chunk;
                }
            }
            $result = trim($result);

            // Never overwrite a good profile with an error string or emptiness.
            if ($result === '' || str_starts_with($result, '[Error')) {
                $user->forceFill(['ai_memory_synced_at' => now()])->save();
                return;
            }

            $user->forceFill([
                'ai_memory' => mb_substr($result, 0, self::MAX_MEMORY_CHARS),
                'ai_memory_synced_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('RefreshUserMemory failed: ' . $e->getMessage());
        }
    }
}
