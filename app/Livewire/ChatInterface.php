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

use Livewire\WithFileUploads;

class ChatInterface extends Component
{
    use WithFileUploads;

    public string $prompt = '';
    public $attachment;
    public array $messages = [];
    public ?int $conversationId = null;
    public $models = [];
    public $moreModels = [];
    public $selectedModel = null;
    public ?int $activeProjectId = null;

    public function mount()
    {
        $this->messages = [];
        $this->models = [];
        $this->moreModels = [];

        $user = Auth::user();
        $hasAnthropic = $user && !empty($user->anthropic_api_key);
        $hasOpenAI = $user && !empty($user->openai_api_key);
        $useProxy = $user && $user->use_proxy && !empty($user->proxy_base_url);
        $hasNineRouter = $user && !empty($user->nine_router_api_key);
        $hasHuggingFace = $user && !empty($user->huggingface_api_key);
        $hasGoogle = $user && !empty($user->google_api_key);
        $hasMistral = $user && !empty($user->mistral_api_key);
        
        $available = $hasAnthropic || $useProxy || $hasNineRouter || $hasHuggingFace || $hasGoogle || $hasMistral;

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
            } elseif ($model->provider === 'google' && $hasGoogle) {
                $is_available = true;
            } elseif ($model->provider === 'mistral' && $hasMistral) {
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
        $conversation = Conversation::with(['messages.artifacts', 'messages.attachment'])->find($this->conversationId);
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
                
                $attachmentData = null;
                if ($msg->attachment) {
                    $attachmentData = [
                        'file_path' => $msg->attachment->file_path,
                        'file_type' => $msg->attachment->file_type,
                        'file_name' => $msg->attachment->file_name,
                    ];
                }
                
                $this->messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'rating' => $msg->rating,
                    'artifact' => $artifactData,
                    'attachment' => $attachmentData,
                ];
            }
            $this->activeProjectId = $conversation->project_id;
        }
    }

    #[On('newChat')]
    public function resetChat()
    {
        $this->messages = [];
        $this->prompt = '';
        $this->attachment = null;
        $this->conversationId = null;
        $this->activeProjectId = null;
    }

    #[On('startProjectChat')]
    public function startProjectChat($projectId)
    {
        $this->resetChat();
        $this->activeProjectId = $projectId;
    }

    #[On('openChat')]
    public function openChat($chatId)
    {
        $this->loadSelectedConversation($chatId);
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

    public function removeAttachment()
    {
        $this->attachment = null;
    }

    /**
     * Signal the running stream to stop. The cache flag is polled inside
     * generateResponse(). This works even mid-stream because Laravel's session
     * does not block concurrent requests for the same user.
     */
    public function stopGeneration()
    {
        if ($this->conversationId) {
            \Illuminate\Support\Facades\Cache::put('chat_stop_' . $this->conversationId, true, 120);
        }
    }

    /**
     * Edit a previous user message: pull its content back into the input and
     * drop that message (and everything after it) so the user can resend.
     */
    public function editMessage($index)
    {
        if (!isset($this->messages[$index]) || $this->messages[$index]['role'] !== 'user') {
            return;
        }

        $content = $this->messages[$index]['content'];

        if ($this->conversationId) {
            $dbMessages = Message::where('conversation_id', $this->conversationId)
                ->orderBy('id')
                ->get();
            foreach ($dbMessages->slice($index) as $dbMsg) {
                MessageArtifact::where('message_id', $dbMsg->id)->delete();
                $dbMsg->delete();
            }
        }

        $this->messages = array_slice($this->messages, 0, $index);
        $this->prompt = $content;
        $this->dispatch('focusPromptInput');
    }

    /**
     * Toggle a thumbs up/down rating on an assistant message.
     */
    public function rateMessage($index, $rating)
    {
        if (!isset($this->messages[$index]) || $this->messages[$index]['role'] !== 'assistant') {
            return;
        }

        // Toggle off if the same rating is clicked again
        $current = $this->messages[$index]['rating'] ?? null;
        $newRating = $current === $rating ? null : $rating;
        $this->messages[$index]['rating'] = $newRating;

        if ($this->conversationId) {
            $dbMessages = Message::where('conversation_id', $this->conversationId)
                ->orderBy('id')
                ->get();
            if (isset($dbMessages[$index]) && $dbMessages[$index]->role === 'assistant') {
                $dbMessages[$index]->update(['rating' => $newRating]);
            }
        }
    }

    #[On('sendPromptFromArtifact')]
    public function sendPromptFromArtifact($prompt)
    {
        $this->prompt = $prompt;
        $this->sendMessage();
    }

    public function sendMessage()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $text = trim($this->prompt);

        if (empty($text) && empty($this->attachment)) {
            return;
        }

        // Create conversation if it doesn't exist
        if (!$this->conversationId) {
            $conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title' => 'New Chat',
                'project_id' => $this->activeProjectId,
            ]);
            $this->conversationId = $conversation->id;
            $this->dispatch('chatCreated');
        }

        // Add user message to DB
        $userMessage = Message::create([
            'conversation_id' => $this->conversationId,
            'role' => 'user',
            'content' => $text,
        ]);

        $attachmentData = null;
        if ($this->attachment) {
            $path = $this->attachment->store('attachments', 'public');
            $attModel = \App\Models\MessageAttachment::create([
                'message_id' => $userMessage->id,
                'file_path' => $path,
                'file_type' => $this->attachment->getMimeType(),
                'file_name' => $this->attachment->getClientOriginalName(),
            ]);

            $attachmentData = [
                'file_path' => $path,
                'file_type' => $this->attachment->getMimeType(),
                'file_name' => $this->attachment->getClientOriginalName(),
            ];
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $text,
            'attachment' => $attachmentData,
        ];

        $this->prompt = '';
        $this->attachment = null;
        
        $this->dispatch('messageAdded');
    }

    #[On('regenerateResponse')]
    public function regenerateResponse()
    {
        if (empty($this->messages) || !$this->conversationId) {
            return;
        }

        // Find the last assistant message and remove it from DB and state
        $lastMessage = end($this->messages);
        if ($lastMessage['role'] === 'assistant') {
            array_pop($this->messages);
            $dbMsg = Message::where('conversation_id', $this->conversationId)->where('role', 'assistant')->latest()->first();
            if ($dbMsg) {
                // If there's an artifact attached, delete it
                MessageArtifact::where('message_id', $dbMsg->id)->delete();
                $dbMsg->delete();
            }
        }

        $this->dispatch('generateResponse');
    }

    #[On('generateResponse')]
    public function generateResponse()
    {
        // Prevent PHP from killing the streaming process during long generations
        set_time_limit(0);

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
        $baseSystemPrompt = "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a document, report, or PDF, you MUST generate a well-structured Markdown document (language=\"markdown\"). DO NOT EVER generate raw PDF byte streams or PostScript code. The system will automatically convert your Markdown into a downloadable PDF for the user. Focus only on writing excellent text content inside the <antArtifact> tag. Provide brief explanation outside the tag if needed.";

        if (Auth::check() && !empty(Auth::user()->custom_instructions)) {
            $baseSystemPrompt .= "\n\nUser Custom Instructions:\n" . Auth::user()->custom_instructions;
        }

        if ($this->activeProjectId) {
            $project = \App\Models\Project::with('files')->find($this->activeProjectId);
            if ($project) {
                if ($project->custom_instructions) {
                    $baseSystemPrompt .= "\n\nProject Custom Instructions:\n" . $project->custom_instructions;
                }
                if ($project->files->count() > 0) {
                    $baseSystemPrompt .= "\n\nProject Knowledge Files:\n";
                    foreach ($project->files as $file) {
                        if (\Illuminate\Support\Facades\Storage::exists($file->file_path)) {
                            // Basic extraction, limit size to prevent massive context explosion
                            $content = \Illuminate\Support\Facades\Storage::get($file->file_path);
                            $baseSystemPrompt .= "\n--- Document: {$file->file_name} ---\n" . substr($content, 0, 50000) . "\n";
                        }
                    }
                }
            }
        }

        array_unshift($messagesForAi, [
            'role' => 'system',
            'content' => $baseSystemPrompt,
        ]);

        // Clear any stale stop flag before we begin streaming
        $stopKey = 'chat_stop_' . $this->conversationId;
        \Illuminate\Support\Facades\Cache::forget($stopKey);

        // Call AI Service
        $aiService = new AiService();
        $stream = $aiService->streamResponse($messagesForAi, $this->selectedModel ?? 'claude-haiku-4-5');

        $fullResponse = '';
        $stopped = false;
        foreach ($stream as $chunk) {
            // Stop generation requested by the user
            if (\Illuminate\Support\Facades\Cache::get($stopKey)) {
                \Illuminate\Support\Facades\Cache::forget($stopKey);
                $stopped = true;
                break;
            }

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

        // If the user stopped an empty generation, store a small placeholder
        if ($stopped && trim($fullResponse) === '') {
            $fullResponse = '_Generation stopped._';
        }

        // After stream is done, parse artifact if present
        $artifactData = null;
        $pattern = '/<antArtifact\s+identifier="([^"]+)"\s+type="([^"]+)"\s+language="([^"]*)"\s+title="([^"]+)">([\s\S]*?)<\/antArtifact>/';
        
        if (preg_match($pattern, $fullResponse, $matches)) {
            $identifier = $matches[1];
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
                'identifier' => $identifier,
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

        // Generate title dynamically if it's a new chat
        if ($this->conversationId) {
            $conversation = \App\Models\Conversation::find($this->conversationId);
            if ($conversation && $conversation->title === 'New Chat') {
                $userMsg = collect($this->messages)->firstWhere('role', 'user');
                if ($userMsg) {
                    try {
                        $titleAi = new \App\Services\AI\AiService();
                        $titleStream = $titleAi->streamResponse([
                            [
                                'role' => 'user',
                                'content' => "Provide a short, concise title (1-4 words max) for a chat that starts with this prompt: \"{$userMsg['content']}\". Reply ONLY with the title, no quotes, no extra text."
                            ]
                        ], $this->selectedModel ?? 'claude-haiku-4-5');
                        
                        $title = '';
                        foreach ($titleStream as $chunk) {
                            $title .= $chunk;
                        }
                        $title = trim(str_replace('"', '', $title));
                        if (!empty($title)) {
                            $conversation->update(['title' => $title]);
                            $this->dispatch('chatCreated');
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Inline Title Gen Failed: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.chat-interface');
    }
}
