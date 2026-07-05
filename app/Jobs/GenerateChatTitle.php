<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class GenerateChatTitle implements ShouldQueue
{
    // SerializesModels: on the database queue the Conversation is stored as an
    // id and re-fetched by the worker, instead of serializing the whole model.
    use Queueable, SerializesModels;

    public $conversation;
    public $prompt;
    public $model;
    public $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(Conversation $conversation, string $prompt, string $model = 'claude-haiku-4-5', $userId = null)
    {
        $this->conversation = $conversation;
        $this->prompt = $prompt;
        $this->model = $model;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->userId) {
                // Use setUser() — NOT loginUsingId() — to establish the user context
                // for AiService (API keys / settings). loginUsingId() calls
                // session->migrate(true), which regenerates and DELETES the live
                // session file when this job runs inline under QUEUE_CONNECTION=sync,
                // logging the web user out and rotating their CSRF token mid-request.
                $user = User::find($this->userId);
                if ($user) {
                    Auth::setUser($user);
                }
            }

            // The passed prompt can be a stub (e.g. "." for extension-generated
            // replies), so base the title on the conversation's real first user
            // message when available.
            $basis = $this->conversation->messages()
                ->where('role', 'user')->orderBy('id')->value('content') ?: $this->prompt;

            $aiService = new AiService();

            // Web-account models (claude.ai / gemini via the browser extension)
            // can't be reached from a queued server job — the answer is produced
            // in the browser. Derive a title from the prompt instead of calling
            // the AI (which would otherwise return the same placeholder text for
            // every chat).
            if ($aiService->resolveProvider($this->model) instanceof \App\Services\AI\WebAiProvider) {
                $this->conversation->update(['title' => $this->deriveTitle($basis)]);
                return;
            }

            $messages = [
                [
                    'role' => 'user',
                    'content' => "Provide a short, concise title (1-4 words max) for a chat that starts with this prompt: \"{$basis}\". Reply ONLY with the title, no quotes, no extra text."
                ]
            ];

            // Generate title synchronously using AiService stream
            $stream = $aiService->streamResponse($messages, $this->model);
            $title = '';
            foreach ($stream as $chunk) {
                if (!is_string($chunk)) continue; // skip structured thinking deltas
                $title .= $chunk;
            }

            $title = trim(str_replace('"', '', $title));

            // Guard: never let an empty result or the web-provider placeholder
            // ("[Butuh Rynude Extension ...]") become the title — fall back to a
            // title derived from the prompt.
            if ($title === '' || mb_strlen($title) > 80 || stripos($title, 'Rynude Extension') !== false) {
                $title = $this->deriveTitle($basis);
            }

            if (!empty($title)) {
                $this->conversation->update(['title' => $title]);
            }
        } catch (\Exception $e) {
            Log::error('GenerateChatTitleJob Failed: ' . $e->getMessage());
        }
    }

    /**
     * Build a readable title from the first few words of the user's prompt.
     */
    private function deriveTitle(string $prompt): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags((string) $prompt)));
        if ($clean === '' || $clean === '.') {
            return 'New Chat';
        }
        $words = array_slice(explode(' ', $clean), 0, 6);
        $title = mb_substr(implode(' ', $words), 0, 60);
        return mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1);
    }
}
