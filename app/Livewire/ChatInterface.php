<?php

namespace App\Livewire;

use App\Models\AiModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Services\AI\AiService;

class ChatInterface extends Component
{
    public string $prompt = '';
    public array $messages = [];
    public ?int $conversationId = null;
    public $models = [];
    public $moreModels = [];
    public $selectedModel = null;

    public function mount()
    {
        $this->messages = [];
        $this->models = [];
        $this->moreModels = [];

        $user = Auth::user();
        $hasAnthropic = $user && !empty($user->anthropic_api_key);
        $hasOpenAI = $user && !empty($user->openai_api_key);
        $useProxy = $user && $user->use_proxy && !empty($user->proxy_api_key);
        
        $available = $hasAnthropic || $useProxy;

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
            if ($useProxy) {
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

        // Set default model if possible
        $this->selectedModel = count($this->models) > 0 ? $this->models[3]->code : 'claude-haiku-4-5';

        if ($this->conversationId) {
            $this->loadConversation();
        }
    }

    public function loadConversation()
    {
        $conversation = Conversation::with('messages.artifacts')->find($this->conversationId);
        if ($conversation && $conversation->user_id === Auth::id()) {
            $this->messages = [];
            foreach ($conversation->messages as $msg) {
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
                
                $this->messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'artifact' => $artifactData,
                ];
            }
        }
    }

    #[On('newChat')]
    public function resetChat()
    {
        $this->messages = [];
        $this->prompt = '';
        $this->conversationId = null;
    }

    #[On('selectConversation')]
    public function loadSelectedConversation($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadConversation();
    }

    #[On('apiKeysSaved')]
    public function refreshModels()
    {
        // Re-run mount logic to fetch models and API keys again
        $this->mount();
    }

    public function openArtifact($id)
    {
        $artifact = \App\Models\MessageArtifact::find($id);
        if ($artifact) {
            $artifactData = [
                'id' => $artifact->id,
                'type' => $artifact->type,
                'language' => $artifact->language,
                'title' => $artifact->title,
                'content' => $artifact->content,
            ];
            $this->dispatch('openArtifact', artifact: $artifactData);
        }
    }

    public function sendMessage()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $text = trim($this->prompt);

        if (empty($text)) {
            return;
        }

        // Create conversation if it doesn't exist
        if (!$this->conversationId) {
            $conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => substr($text, 0, 30) . '...',
            ]);
            $this->conversationId = $conversation->id;
        }

        // Add user message to DB
        Message::create([
            'conversation_id' => $this->conversationId,
            'role' => 'user',
            'content' => $text,
        ]);

        $this->messages[] = [
            'role' => 'user',
            'content' => $text,
        ];

        $this->prompt = '';
        
        $this->dispatch('messageAdded');
    }

    #[On('generateResponse')]
    public function generateResponse()
    {
        if (empty($this->messages) || end($this->messages)['role'] !== 'user') {
            return;
        }

        // Prepare sliding window context
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

        // Prepend system prompt for Artifacts
        array_unshift($messagesForAi, [
            'role' => 'system',
            'content' => "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a PDF, you must generate a beautifully styled HTML document (language=\"html\") tailored for printing (e.g. A4 size css), and inform the user they can preview it in the panel and click the Download icon to save it as a PDF. Provide brief explanation outside the tag if needed.",
        ]);

        // Call AI Service
        $aiService = new AiService();
        $stream = $aiService->streamResponse($messagesForAi, $this->selectedModel ?? 'claude-haiku-4-5');

        $fullResponse = '';
        foreach ($stream as $chunk) {
            $fullResponse .= $chunk;
            
            $displayContent = $fullResponse;
            $pattern = '/<antArtifact\b[^>]*>([\s\S]*?)(?:<\/antArtifact>|$)/';
            
            $loadingHtml = '<div class="mt-3 inline-flex items-center gap-3 border border-[#E5E5E5] dark:border-stone-700 rounded-xl p-2 pr-4 bg-white dark:bg-stone-800 shadow-sm max-w-full">
                <div class="w-10 h-10 bg-[#F9F8F6] dark:bg-stone-700 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#D97757] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate">Generating Artifact...</h4>
                    <p class="text-[12px] text-stone-500 mt-0.5 truncate">Writing content</p>
                </div>
            </div>';

            $displayContent = preg_replace_callback($pattern, function($matches) use ($loadingHtml) {
                return $loadingHtml;
            }, $displayContent);

            if (($pos = strpos($displayContent, '<antArtifact')) !== false) {
                $displayContent = substr($displayContent, 0, $pos) . $loadingHtml;
            }

            $htmlDisplay = \Illuminate\Support\Str::markdown($displayContent);

            $this->stream(
                to: 'message-stream',
                content: $htmlDisplay,
                replace: true
            );
        }

        // After stream is done, parse artifact if present
        $artifactData = null;
        $pattern = '/<antArtifact\s+identifier="([^"]+)"\s+type="([^"]+)"\s+language="([^"]*)"\s+title="([^"]+)">([\s\S]*?)<\/antArtifact>/';
        
        if (preg_match($pattern, $fullResponse, $matches)) {
            $type = $matches[2];
            $language = $matches[3];
            $title = $matches[4];
            $content = trim($matches[5]);
            
            // Remove the artifact block from the visible text
            $cleanResponse = preg_replace($pattern, '', $fullResponse);
            $cleanResponse = trim($cleanResponse);
            
            $assistantMessage = Message::create([
                'conversation_id' => $this->conversationId,
                'role' => 'assistant',
                'content' => $cleanResponse,
            ]);
            
            $artModel = MessageArtifact::create([
                'message_id' => $assistantMessage->id,
                'type' => $type === 'application/vnd.ant.code' ? 'code' : 'text',
                'language' => $language ?: 'text',
                'title' => $title,
                'content' => $content,
            ]);

            $artifactData = [
                'id' => $artModel->id,
                'type' => $artModel->type,
                'language' => $artModel->language,
                'title' => $artModel->title,
                'content' => $artModel->content,
            ];
            $fullResponse = $cleanResponse;
        } else {
            $assistantMessage = Message::create([
                'conversation_id' => $this->conversationId,
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);
        }

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $fullResponse,
            'artifact' => $artifactData,
        ];
    }

    public function render()
    {
        return view('livewire.chat-interface');
    }
}
