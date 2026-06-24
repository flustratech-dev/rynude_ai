<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\AiModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ClaudeCodeApp extends Component
{
    use WithFileUploads;

    // ── Chat State ─────────────────────────────────────
    public string $message = '';
    public ?Conversation $conversation = null;
    public array $messages = [];
    public bool $isStarted = false;
    public bool $isStreaming = false;

    // ── Navigation ─────────────────────────────────────
    public string $currentView = 'chat'; // 'chat' | 'routines' | 'new-routine'

    // ── Models ─────────────────────────────────────────
    public string $selectedModel = 'claude-sonnet-4-6';
    public array $models = [];
    public array $moreModels = [];

    // ── File Attachments ───────────────────────────────
    public array $attachments = [];       // uploaded Livewire temp files
    public array $attachmentPreviews = []; // [{name, type, path}]

    // ── Repository ─────────────────────────────────────
    public string $repoUrl = '';
    public string $repoConnected = ''; // display name once connected
    public bool $repoModalOpen = false;
    public string $repoError = '';
    public array $repoTree = []; // [ ['type'=>'dir|file', 'name'=>'...', 'path'=>'...', 'size'=>0, 'depth'=>0] ]

    // ── File Explorer & Context ────────────────────────
    public array $localFilesTree = []; // similar structure for uploaded files
    public array $selectedFilesContext = []; // [ ['name'=>'...', 'content'=>'...'] ]

    // ── Mode ───────────────────────────────────────────
    // accept = writes code directly, plan = outlines first, auto = autonomous
    public string $selectedMode = 'accept';

    // ── Session History ────────────────────────────────
    public array $recentSessions = [];

    // ── Token Counter ──────────────────────────────────
    public int $sessionTokens = 0;

    // ── Agent Tool Calls (right-panel "Tools" tab) ─────
    // [ ['name'=>'read_file', 'input'=>'app/Models/User.php', 'summary'=>'1.2k chars'] ]
    public array $toolCalls = [];

    public function mount(): void
    {
        $this->buildModelList();
        $this->restoreSelectedModel();
        $this->loadRecentSessions();
    }

    // ═══════════════════════════════════════
    //  MODEL LIST  (mirrors ChatInterface)
    // ═══════════════════════════════════════
    private function buildModelList(): void
    {
        $user = Auth::user();
        $hasAnthropic  = $user && !empty($user->anthropic_api_key);
        $hasOpenAI     = $user && !empty($user->openai_api_key);
        $useProxy      = $user && $user->use_proxy && !empty($user->proxy_base_url);
        $hasNineRouter = $user && !empty($user->nine_router_api_key);
        $hasHuggingFace= $user && !empty($user->huggingface_api_key);
        $hasGoogle     = $user && !empty($user->google_api_key);
        $hasMistral    = $user && !empty($user->mistral_api_key);

        $available = $hasAnthropic || $useProxy || $hasNineRouter || $hasHuggingFace || $hasGoogle || $hasMistral;

        $this->models = [
            (object)['code'=>'fable-5',           'name'=>'Fable 5',    'description'=>'For your toughest challenges', 'is_available'=>false],
            (object)['code'=>'claude-opus-4-8',   'name'=>'Opus 4.8',   'description'=>'For complex tasks',             'is_available'=>$available],
            (object)['code'=>'claude-sonnet-4-6', 'name'=>'Sonnet 4.6', 'description'=>'Most efficient for everyday',   'is_available'=>$available],
            (object)['code'=>'claude-haiku-4-5',  'name'=>'Haiku 4.5',  'description'=>'Fastest for quick answers',     'is_available'=>$available],
        ];

        $pinned = ['fable-5','claude-opus-4-8','claude-sonnet-4-6','claude-haiku-4-5'];
        foreach (AiModel::where('is_active', true)->get() as $m) {
            if (in_array($m->code, $pinned)) continue;

            $isAnthropic = str_starts_with($m->code, 'claude');
            $isOpenAI    = str_starts_with($m->code, 'gpt');
            $avail = false;
            if (str_starts_with($m->code, 'kr/claude'))                        $avail = true;
            elseif ($useProxy || $hasNineRouter)                               $avail = true;
            elseif ($m->provider === 'huggingface'  && $hasHuggingFace)        $avail = true;
            elseif ($m->provider === 'google'        && $hasGoogle)             $avail = true;
            elseif ($m->provider === 'mistral'       && $hasMistral)            $avail = true;
            elseif ($isAnthropic                     && $hasAnthropic)          $avail = true;
            elseif ($hasOpenAI                       && !$isAnthropic)          $avail = true;

            $this->moreModels[] = (object)['code'=>$m->code,'name'=>$m->name,'description'=>$m->name,'is_available'=>$avail];
        }
    }

    private function restoreSelectedModel(): void
    {
        $valid = array_merge(
            array_map(fn($m) => $m->code, $this->models),
            array_map(fn($m) => $m->code, $this->moreModels)
        );
        $remembered = session('code_selected_model');
        $this->selectedModel = ($remembered && in_array($remembered, $valid))
            ? $remembered
            : 'claude-sonnet-4-6';
    }

    public function updatedSelectedModel($value): void
    {
        if (!empty($value)) session(['code_selected_model' => $value]);
    }

    // ═══════════════════════════════════════
    //  SESSION HISTORY
    // ═══════════════════════════════════════
    private function loadRecentSessions(): void
    {
        if (!Auth::check()) return;
        $this->recentSessions = Conversation::where('user_id', Auth::id())
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get(['id','title','updated_at'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'title' => $c->title ?? 'Untitled session',
                'ago'   => $c->updated_at->diffForHumans(short: true),
            ])
            ->toArray();
    }

    public function loadSession(int $conversationId): void
    {
        $conv = Conversation::with('messages')->find($conversationId);
        if (!$conv || $conv->user_id !== Auth::id()) return;

        $this->conversation = $conv;
        $this->messages = [];
        $this->sessionTokens = 0;
        $this->toolCalls = [];

        foreach ($conv->messages as $msg) {
            $this->messages[] = [
                'role'    => $msg->role,
                'content' => $msg->content,
            ];
            $this->sessionTokens += (int)(strlen($msg->content) / 4);
        }

        $this->isStarted    = !empty($this->messages);
        
        $meta = $conv->metadata ?? [];
        $this->repoConnected = $meta['repo'] ?? '';
        $this->repoTree = $meta['repoTree'] ?? [];
        $this->selectedFilesContext = $meta['selectedFilesContext'] ?? [];
        
        $this->currentView  = 'chat';

        $this->dispatch('scroll-to-bottom');
    }

    public function deleteSession(int $conversationId): void
    {
        $conv = Conversation::find($conversationId);
        if (!$conv || $conv->user_id !== Auth::id()) return;

        if ($this->conversation?->id === $conversationId) {
            $this->newSession();
        }
        $conv->messages()->delete();
        $conv->delete();
        $this->loadRecentSessions();
    }

    public function newSession(): void
    {
        $this->conversation     = null;
        $this->messages         = [];
        $this->message          = '';
        $this->attachments      = [];
        $this->attachmentPreviews = [];
        $this->isStarted        = false;
        $this->isStreaming       = false;
        $this->sessionTokens    = 0;
        $this->toolCalls        = [];
        $this->repoConnected    = '';
        $this->repoTree         = [];
        $this->selectedFilesContext = [];
        $this->currentView      = 'chat';
    }

    // ═══════════════════════════════════════
    //  FILE UPLOADS
    // ═══════════════════════════════════════
    public function updatedAttachments(): void
    {
        $this->attachmentPreviews = [];
        $this->localFilesTree = [];
        
        foreach ($this->attachments as $file) {
            $name = $file->getClientOriginalName();
            $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'txt';
            
            $this->attachmentPreviews[] = [
                'name' => $name,
                'type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
            
            $this->localFilesTree[] = [
                'type' => 'file',
                'name' => $name,
                'path' => $name, // local files don't have deep paths in temp
                'depth' => 0,
                'extra' => strtolower($ext)
            ];
        }
    }

    public function removeAttachment(int $index): void
    {
        array_splice($this->attachments, $index, 1);
        array_splice($this->attachmentPreviews, $index, 1);
    }

    // ═══════════════════════════════════════
    //  REPOSITORY CONNECTION
    // ═══════════════════════════════════════
    public function connectRepo(): void
    {
        $url = trim($this->repoUrl);
        if (empty($url)) {
            $this->repoError = 'Please enter a repository URL.';
            return;
        }

        // Parse owner and repo name from URL
        $parsed = \App\Services\GitHubService::parseUrl($url);
        if (!$parsed) {
            $this->repoError = 'Only GitHub repository URLs are supported currently.';
            return;
        }

        $token = Auth::user()->github_token ?? null;
        $github = new \App\Services\GitHubService($token);

        // Optional: verify access
        $info = $github->getRepoInfo($parsed['owner'], $parsed['repo']);
        if (!$info) {
            $this->repoError = 'Repository not found or requires a GitHub token in Settings.';
            return;
        }

        // Fetch tree
        $tree = $github->fetchTree($parsed['owner'], $parsed['repo'], $info['default_branch']);
        
        // Transform the flat tree into depth-based list for rendering
        $this->repoTree = [];
        foreach ($tree as $item) {
            $depth = substr_count($item['path'], '/');
            $name = basename($item['path']);
            $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'txt';
            $this->repoTree[] = [
                'type' => $item['type'] === 'tree' ? 'dir' : 'file',
                'name' => $name,
                'path' => $item['path'],
                'depth' => $depth,
                'extra' => $item['type'] === 'tree' ? false : strtolower($ext) // dir expanded=false, file=extension
            ];
        }

        $this->repoConnected = $parsed['owner'] . '/' . $parsed['repo'];
        $this->repoModalOpen = false;
        $this->repoError     = '';
        $this->repoUrl       = '';

        if ($this->conversation) {
            $meta = $this->conversation->metadata ?? [];
            $meta['repo'] = $this->repoConnected;
            $meta['repoTree'] = $this->repoTree;
            $this->conversation->update(['metadata' => $meta]);
        }
    }

    public function disconnectRepo(): void
    {
        $this->repoConnected = '';
        $this->repoTree = [];
        $this->selectedFilesContext = [];

        if ($this->conversation) {
            $meta = $this->conversation->metadata ?? [];
            $meta['repo'] = '';
            $meta['repoTree'] = [];
            $meta['selectedFilesContext'] = [];
            $this->conversation->update(['metadata' => $meta]);
        }
    }

    public function loadFileFromRepo(string $path): void
    {
        if (!$this->repoConnected) return;

        // Check if already loaded
        foreach ($this->selectedFilesContext as $f) {
            if ($f['path'] === $path) return;
        }

        $parts = explode('/', $this->repoConnected);
        if (count($parts) !== 2) return;

        $token = Auth::user()->github_token ?? null;
        $github = new \App\Services\GitHubService($token);
        
        $content = $github->fetchFileContent($parts[0], $parts[1], $path);
        
        if ($content) {
            $this->selectedFilesContext[] = [
                'name' => basename($path),
                'path' => $path,
                'content' => $content,
            ];
            
            // Re-calculate token count just for UI display
            $this->sessionTokens += (int)(strlen($content) / 4);

            if ($this->conversation) {
                $meta = $this->conversation->metadata ?? [];
                $meta['selectedFilesContext'] = $this->selectedFilesContext;
                $this->conversation->update(['metadata' => $meta]);
            }
        }
    }

    // ═══════════════════════════════════════
    //  SEND MESSAGE
    // ═══════════════════════════════════════
    public function sendMessage(): void
    {
        if (!Auth::check()) {
            redirect()->route('login');
            return;
        }

        $text = trim($this->message);
        if (empty($text) && empty($this->attachments)) return;

        // Create conversation if needed
        if (!$this->conversation) {
            $this->conversation = Conversation::create([
                'user_id' => Auth::id(),
                'title'   => $this->generateTitle($text),
                'metadata' => [
                    'repo' => $this->repoConnected,
                    'repoTree' => $this->repoTree,
                    'selectedFilesContext' => $this->selectedFilesContext
                ]
            ]);
            $this->dispatch('chatCreated');
            $this->loadRecentSessions();
        } else {
            // Ensure metadata is updated on new messages if state changed
            $meta = $this->conversation->metadata ?? [];
            $meta['repo'] = $this->repoConnected;
            $meta['repoTree'] = $this->repoTree;
            $meta['selectedFilesContext'] = $this->selectedFilesContext;
            $this->conversation->update(['metadata' => $meta]);
        }

        // Persist user message
        $userMsg = Message::create([
            'conversation_id' => $this->conversation->id,
            'role'            => 'user',
            'content'         => $text,
        ]);

        // Handle file attachments
        $attachmentData = [];
        foreach ($this->attachments as $file) {
            $path = $file->store('code-attachments', 'public');
            MessageAttachment::create([
                'message_id' => $userMsg->id,
                'file_path'  => $path,
                'file_type'  => $file->getMimeType(),
                'file_name'  => $file->getClientOriginalName(),
            ]);
            $attachmentData[] = [
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_name' => $file->getClientOriginalName(),
            ];
        }

        // Append repo context to first message if connected
        $messageContent = $text;
        if ($this->repoConnected && count($this->messages) === 0) {
            $messageContent = "[Repository context: {$this->repoConnected}]\n\n{$text}";
        }

        $this->messages[] = [
            'role'        => 'user',
            'content'     => $messageContent,
            'attachments' => $attachmentData,
        ];

        $this->sessionTokens += (int)(strlen($messageContent) / 4);
        $this->message         = '';
        $this->attachments     = [];
        $this->attachmentPreviews = [];
        $this->isStarted       = true;
        $this->isStreaming     = true;

        $this->dispatch('message-added');
    }

    // ═══════════════════════════════════════
    //  STOP STREAMING
    // ═══════════════════════════════════════
    public function stopGeneration(): void
    {
        if ($this->conversation) {
            Cache::put('chat_stop_' . $this->conversation->id, true, 120);
        }
        $this->isStreaming = false;
    }

    // ═══════════════════════════════════════
    //  GENERATE AI RESPONSE
    // ═══════════════════════════════════════
    #[\Livewire\Attributes\On('generateResponse')]
    public function generateResponse(): void
    {
        set_time_limit(0);

        if (empty($this->messages) || end($this->messages)['role'] !== 'user') {
            $this->isStreaming = false;
            return;
        }

        // Clear any previous stop flag
        if ($this->conversation) {
            Cache::forget('chat_stop_' . $this->conversation->id);
        }

        // Build context window (last 20 messages)
        $userMessages = array_values(array_filter($this->messages, fn($m) => $m['role'] !== 'system'));
        $total        = count($userMessages);
        $messagesForAi = $total > 20
            ? array_merge([$userMessages[0]], array_slice($userMessages, -19))
            : $userMessages;

        // Build messages for AI — keep attachments so the provider can render
        // images/docs natively during the agent turn.
        $aiMessages = [];
        foreach ($messagesForAi as $m) {
            $aiMessages[] = array_filter([
                'role'        => $m['role'],
                'content'     => $m['content'],
                'attachments' => $m['attachments'] ?? null,
            ], fn ($v) => $v !== null);
        }

        // ── System prompt — mode-aware, deeply code-specific ──────────────
        $modeInstructions = match($this->selectedMode) {
            'plan' =>
                "CURRENT MODE: Plan Mode.\n"
                . "When the user asks you to implement or change anything, you MUST first output a structured implementation plan BEFORE writing any code. "
                . "Format the plan as numbered steps with file paths and a brief description of each change. "
                . "End the plan with a line: '---\nReady to implement. Reply **go** to proceed.' "
                . "Only write actual code after the user confirms. "
                . "If the user is just asking a question (not asking for implementation), answer directly without a plan.",

            'auto' =>
                "CURRENT MODE: Auto Mode (Autonomous).\n"
                . "You operate as a fully autonomous coding agent. "
                . "You DO NOT ask for confirmation before making changes. "
                . "When given a task, immediately produce all required code changes, shell commands, and file modifications. "
                . "Structure your output as a series of action blocks: first list what you're doing, then show the complete implementation. "
                . "Think step-by-step but execute all steps in one response without pausing for user input. "
                . "If something is ambiguous, make a reasonable assumption and state it clearly.",

            default => // 'accept'
                "CURRENT MODE: Accept Edits Mode.\n"
                . "Write code directly and completely. "
                . "When modifying a file, show the full relevant code block with the change applied (not just the diff). "
                . "Use code fences with the exact language. "
                . "After showing code, briefly explain what changed and why. "
                . "Do NOT ask 'shall I proceed?' — just do it.",
        };

        // ── Base persona — loaded verbatim from plan_rynudecode.md at runtime ──
        // The markdown file is the editable source of truth for the agent persona.
        // Fall back to the inline persona if the file is missing/empty (e.g. packaged
        // builds under RYNUDE_HOME where the markdown may not ship).
        $personaPath = base_path('plan_rynudecode.md');
        $persona = is_file($personaPath) ? trim(file_get_contents($personaPath)) : '';

        if ($persona === '') {
            $persona = "You are Rynude Code, an expert autonomous AI coding agent built into the Rynude platform.\n"
                . "You are NOT a general-purpose chatbot. You are a specialized coding agent.\n"
                . "Your purpose: help developers read, write, debug, refactor, and understand code across ALL languages and frameworks.\n\n"
                . "CORE BEHAVIOR:\n"
                . "- Always respond as an expert engineer, not as a helpful assistant.\n"
                . "- Be concise, precise, and technical. Skip pleasantries.\n"
                . "- Format ALL code in fenced code blocks with the language specified: ```php, ```js, ```bash, etc.\n"
                . "- Reference files using backtick paths: `app/Http/Controllers/UserController.php`\n"
                . "- Format shell commands with a $ prefix inside a ```bash block.\n"
                . "- When explaining architecture, use clear structure: numbered steps, bullet points, or tables.\n"
                . "- Assume the user is an experienced developer unless stated otherwise.\n"
                . "- If asked about the codebase structure, reference Laravel/PHP conventions as the default stack.";
        }

        $systemPrompt = $persona . "\n\n" . $modeInstructions;

        // ── Agentic tools guidance ────────────────────────────────────────
        $systemPrompt .= "\n\nAGENTIC TOOLS:\n"
            . "You can call tools to gather context yourself instead of guessing. "
            . "Before answering questions about the codebase, use `list_files` and `search_code` to locate "
            . "relevant files, then `read_file` to inspect their actual contents. Use `web_search` for "
            . "up-to-date external facts. Chain several tool calls as needed, then give a precise answer "
            . "grounded in what you read. Do NOT fabricate file paths or code — verify with tools first.";

        // ── Persistent conversation memory (durable across turns & models) ──
        $memoryService = app(\App\Services\AI\ConversationMemoryService::class);
        if ($this->conversation) {
            $systemPrompt .= $memoryService->formatForPrompt($this->conversation);
        }

        if ($this->repoConnected) {
            $systemPrompt .= "\n\nCONNECTED REPOSITORY: {$this->repoConnected}\n"
                . "Reference this repository when the user asks about code, files, or implementation details. "
                . "Assume the repo follows standard conventions for its detected tech stack.";
        }

        // Inject selected files content
        if (!empty($this->selectedFilesContext)) {
            $systemPrompt .= "\n\n=== SELECTED FILES CONTEXT ===\n";
            $systemPrompt .= "The user has selected the following files from the repository to be included in your context. "
                . "Use this actual source code to answer their questions or implement changes:\n\n";
            
            foreach ($this->selectedFilesContext as $f) {
                $systemPrompt .= "--- FILE: {$f['path']} ---\n";
                $systemPrompt .= "```\n" . $f['content'] . "\n```\n\n";
            }
        }

        // Apply active skills
        if (Auth::check()) {
            $skills = \App\Models\Skill::where('user_id', Auth::id())->where('is_active', true)->get();
            if ($skills->isNotEmpty()) {
                $systemPrompt .= "\n\nACTIVE SKILLS:";
                foreach ($skills as $skill) {
                    $systemPrompt .= "\n\n[Skill: {$skill->name}]\n{$skill->instructions}";
                }
            }
        }

        array_unshift($aiMessages, ['role' => 'system', 'content' => $systemPrompt]);

        // ── Agentic tool registry ──────────────────────────────────────────
        // Pre-seed already-loaded files so the agent can re-read them with no API call.
        $uploadedContents = [];
        foreach ($this->selectedFilesContext as $f) {
            $uploadedContents[$f['path']] = $f['content'];
        }
        $tools = new \App\Services\AI\AgentTools(
            $this->repoConnected,
            $this->repoTree,
            $this->localFilesTree,
            $uploadedContents,
            Auth::user()->github_token ?? null,
            env('RYNUDE_WORKSPACE', '')
        );

        // ── Run the agent loop, interleaving text + tool activity ──────────
        $runner   = new \App\Services\AI\AgentRunner(new \App\Services\AI\AiService());
        $rendered = '';   // finalized markdown shown to the user
        $pending  = '';   // transient "tool running…" line

        foreach ($runner->run($aiMessages, $this->selectedModel, $tools) as $event) {
            // Honour the stop button.
            if ($this->conversation && Cache::get('chat_stop_' . $this->conversation->id)) {
                Cache::forget('chat_stop_' . $this->conversation->id);
                break;
            }

            if ($event['type'] === 'reset') {
                // Runner is discarding a failed turn's output before a clean retry.
                $rendered = '';
                $pending  = '';
            } elseif ($event['type'] === 'text') {
                $rendered .= $event['text'];
            } elseif ($event['type'] === 'tool') {
                $summary = $this->toolInputSummary($event['input']);
                if ($event['status'] === 'running') {
                    $pending = "\n\n> ⏳ **{$event['name']}** `{$summary}`\n";
                } else { // done
                    $pending = '';
                    $rendered .= "\n\n> 🔧 **{$event['name']}** `{$summary}` — ✓ {$event['summary']}\n\n";
                    $this->toolCalls[] = [
                        'name'    => $event['name'],
                        'input'   => $summary,
                        'summary' => $event['summary'],
                    ];
                }
            }

            $this->stream(
                to: 'message-stream',
                content: \Illuminate\Support\Str::markdown($rendered . $pending),
                replace: true
            );
        }

        $fullResponse = trim($rendered);

        // Merge files the agent opened into the context + Files panel.
        foreach ($tools->openedFiles() as $file) {
            $already = false;
            foreach ($this->selectedFilesContext as $f) {
                if ($f['path'] === $file['path']) { $already = true; break; }
            }
            if (!$already) {
                $this->selectedFilesContext[] = $file;
            }
        }

        // Persist assistant response
        if (!empty($fullResponse) && $this->conversation) {
            Message::create([
                'conversation_id' => $this->conversation->id,
                'role'            => 'assistant',
                'content'         => $fullResponse,
            ]);

            $this->messages[] = ['role' => 'assistant', 'content' => $fullResponse];
            $this->sessionTokens += (int)(strlen($fullResponse) / 4);

            // Persist any newly opened files into the conversation metadata.
            $meta = $this->conversation->metadata ?? [];
            $meta['selectedFilesContext'] = $this->selectedFilesContext;
            $this->conversation->update(['metadata' => $meta]);

            // Update conversation title if still generic
            if (str_ends_with($this->conversation->title, '...') || $this->conversation->title === 'Untitled session') {
                $firstUserMsg = collect($this->messages)->firstWhere('role', 'user')['content'] ?? '';
                $this->conversation->update(['title' => $this->generateTitle($firstUserMsg)]);
            }

            // Refresh durable memory once enough new messages have accumulated.
            // Runs after streaming so it never delays output (failures are logged).
            if ($memoryService->shouldRefresh($this->conversation, count($this->messages))) {
                $memoryService->refresh($this->conversation, $this->messages, $this->selectedModel);
            }

            $this->loadRecentSessions();
        }

        $this->isStreaming = false;
    }

    /** Compact one-line summary of a tool's input, for the activity callout. */
    private function toolInputSummary(array $input): string
    {
        $parts = [];
        foreach ($input as $v) {
            $parts[] = is_scalar($v) ? (string) $v : json_encode($v);
        }
        return \Illuminate\Support\Str::limit(implode(' ', $parts), 80) ?: '…';
    }

    // ═══════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════
    private function generateTitle(string $text): string
    {
        $stopWords = ['i','can','you','please','the','a','an','is','are','was','were','be','been',
                      'have','has','had','do','does','did','will','would','should','could','may',
                      'might','shall','to','of','in','on','at','for','with','by','from','my','your'];

        $words = preg_split('/\s+/', strtolower(strip_tags($text)));
        $meaningful = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
        $title = implode(' ', array_slice(array_values($meaningful), 0, 5));
        return ucwords($title) ?: substr($text, 0, 40);
    }

    public function formattedTokens(): string
    {
        if ($this->sessionTokens >= 1000) {
            return round($this->sessionTokens / 1000, 1) . 'k';
        }
        return (string)$this->sessionTokens;
    }

    public function render()
    {
        return view('livewire.claude-code-app')
            ->layout('layouts.app')
            ->title('Rynude Code · ' . config('app.name', 'Rynude'));
    }
}
