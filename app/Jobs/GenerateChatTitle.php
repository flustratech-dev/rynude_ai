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

            $aiService = new AiService();
            $messages = [
                [
                    'role' => 'user',
                    'content' => "Provide a short, concise title (1-4 words max) for a chat that starts with this prompt: \"{$this->prompt}\". Reply ONLY with the title, no quotes, no extra text."
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

            if (!empty($title)) {
                $this->conversation->update(['title' => $title]);
            }
        } catch (\Exception $e) {
            Log::error('GenerateChatTitleJob Failed: ' . $e->getMessage());
        }
    }
}
