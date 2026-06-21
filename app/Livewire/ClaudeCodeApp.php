<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ClaudeCodeApp extends Component
{
    public $message = '';
    public $conversation = null;
    public $messages = [];
    public $isStarted = false;
    public $currentView = 'chat'; // 'chat', 'routines', 'new-routine'
    public $selectedModel = 'claude-sonnet-4-6';
    public $models = [];
    public $moreModels = [];

    public function mount()
    {
        $this->messages = [];
        $this->models = [];
        $this->moreModels = [];

        $user = auth()->user();
        $hasAnthropic = $user && !empty($user->anthropic_api_key);
        $hasOpenAI = $user && !empty($user->openai_api_key);
        $useProxy = $user && $user->use_proxy && !empty($user->proxy_base_url);
        $hasNineRouter = $user && !empty($user->nine_router_api_key);
        $hasHuggingFace = $user && !empty($user->huggingface_api_key);
        
        $available = $hasAnthropic || $useProxy || $hasNineRouter || $hasHuggingFace;

        $this->models = [
            (object)[
                'code' => 'fable-5',
                'name' => 'Fable 5',
                'description' => 'For your toughest challenges',
                'is_available' => false,
            ],
            (object)[
                'code' => 'claude-opus-4-8',
                'name' => 'Opus 4.8',
                'description' => 'For complex tasks',
                'is_available' => $available,
            ],
            (object)[
                'code' => 'claude-sonnet-4-6',
                'name' => 'Sonnet 4.6',
                'description' => 'Most efficient for everyday tasks',
                'is_available' => $available,
            ],
            (object)[
                'code' => 'claude-haiku-4-5',
                'name' => 'Haiku 4.5',
                'description' => 'Fastest for quick answers',
                'is_available' => $available,
            ]
        ];

        // Restore moreModels from DB
        $allModels = \App\Models\AiModel::where('is_active', true)->get();
        foreach ($allModels as $model) {
            $isAnthropic = str_starts_with($model->code, 'claude');
            $isOpenAI = str_starts_with($model->code, 'gpt');

            $is_available = false;
            if (str_starts_with($model->code, 'kr/claude')) {
                $is_available = true;
            } elseif ($useProxy || $hasNineRouter) {
                $is_available = true;
            } elseif ($model->provider === 'huggingface' && $hasHuggingFace) {
                $is_available = true;
            } elseif ($isAnthropic && $hasAnthropic) {
                $is_available = true;
            } elseif ($hasOpenAI && !$isAnthropic) {
                $is_available = true;
            }

            // Exclude models already in $this->models
            if (!in_array($model->code, ['fable-5', 'claude-opus-4-8', 'claude-sonnet-4-6', 'claude-haiku-4-5'])) {
                $this->moreModels[] = (object)[
                    'code' => $model->code,
                    'name' => $model->name,
                    'description' => $model->name,
                    'is_available' => $is_available,
                ];
            }
        }
    }

    public function sendMessage()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (trim($this->message) === '') return;

        // Create conversation if it doesn't exist
        if (!$this->conversation) {
            $this->conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => substr($this->message, 0, 30) . '...',
            ]);
            $this->dispatch('chatCreated');
        }

        // Save user message
        $userMessage = Message::create([
            'conversation_id' => $this->conversation->id,
            'role' => 'user',
            'content' => $this->message,
        ]);
        
        $this->messages[] = $userMessage->toArray();

        $this->isStarted = true;
        $this->message = '';
        
        // Dispatch event to scroll to bottom
        $this->dispatch('message-added');
    }

    #[\Livewire\Attributes\On('generateResponse')]
    public function generateResponse()
    {
        set_time_limit(0);

        if (empty($this->messages) || end($this->messages)['role'] !== 'user') {
            return;
        }

        $messagesForAi = [];
        $historySize = 10;
        
        $userMessages = array_filter($this->messages, fn($m) => $m['role'] !== 'system');
        $userMessages = array_values($userMessages);
        $totalMsgs = count($userMessages);
        
        if ($totalMsgs > $historySize) {
            $firstMessage = $userMessages[0] ?? null;
            $recentMessages = array_slice($userMessages, -($historySize - 1));
            if ($firstMessage) {
                $messagesForAi[] = $firstMessage;
            }
            $messagesForAi = array_merge($messagesForAi, $recentMessages);
        } else {
            $messagesForAi = $userMessages;
        }

        $systemPrompt = "You are Rynude Code, an autonomous CLI agent and expert software engineer. You specialize in reading, writing, and analyzing code. When providing code blocks, shell commands, or configurations, format them appropriately using markdown. Act as an advanced developer tool directly integrated into the user's environment. You can assume you are operating in 'Simulated Agentic Mode'. Always give concise, professional, and accurate technical responses. Avoid unnecessary pleasantries.";

        array_unshift($messagesForAi, [
            'role' => 'system',
            'content' => $systemPrompt,
        ]);

        $aiService = new \App\Services\AI\AiService();
        $stream = $aiService->streamResponse($messagesForAi, $this->selectedModel);

        $fullResponse = '';
        
        foreach ($stream as $chunk) {
            $fullResponse .= $chunk;
            
            $htmlDisplay = \Illuminate\Support\Str::markdown($fullResponse);
            
            $this->stream(
                to: 'message-stream',
                content: $htmlDisplay,
                replace: true
            );
        }

        if (!empty($fullResponse)) {
            Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);
            
            $this->messages[] = [
                'role' => 'assistant',
                'content' => $fullResponse,
            ];
        }
    }

    public function render()
    {
        return view('livewire.claude-code-app')->layout('layouts.app');
    }
}
