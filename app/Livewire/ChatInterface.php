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
    public $attachments = [];
    public array $messages = [];
    public ?int $conversationId = null;
    public $models = [];
    public $moreModels = [];
    public $selectedModel = null;
    public ?int $activeProjectId = null;
    public bool $webSearch = false;

    // Memory viewer/editor state.
    public bool $showMemory = false;
    public string $memoryDraft = '';
    public ?string $memoryUpdatedAt = null;

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

        // Restore the user's last-used model so it survives a page refresh.
        // Fall back to the default (Haiku) only when nothing valid is stored.
        $validCodes = array_merge(
            array_map(fn ($m) => $m->code, $this->models),
            array_map(fn ($m) => $m->code, $this->moreModels)
        );
        $remembered = session('chat_selected_model');
        if ($remembered && in_array($remembered, $validCodes, true)) {
            $this->selectedModel = $remembered;
        } else {
            $this->selectedModel = count($this->models) > 0 ? $this->models[3]->code : 'claude-haiku-4-5';
        }

        if ($this->conversationId) {
            $this->loadConversation();
        }
    }

    /**
     * Remember the chosen model so it persists across page refreshes / re-mounts.
     */
    public function updatedSelectedModel($value)
    {
        if (!empty($value)) {
            session(['chat_selected_model' => $value]);
        }
    }

    /**
     * Persist the in-progress prompt to the active conversation every couple of
     * seconds (debounce lives on the wire:model in the view). Survives refresh,
     * accidental tab close, browser crash. Cleared on send / new chat.
     */
    public function updatedPrompt($value): void
    {
        if (! $this->conversationId) {
            return; // No conversation yet — nothing to attach a draft to.
        }
        $draft = trim((string) $value);
        Conversation::where('id', $this->conversationId)
            ->where('user_id', Auth::id())
            ->update(['draft_prompt' => $draft === '' ? null : $draft]);
    }

    public function loadConversation()
    {
        $conversation = Conversation::with(['messages.artifacts', 'messages.attachments'])->find($this->conversationId);
        if ($conversation && $conversation->user_id === Auth::id()) {
            // Restore the in-progress draft prompt (if any) so refreshing the
            // tab or switching chats doesn't lose what the user was typing.
            if (! empty($conversation->draft_prompt) && trim($this->prompt) === '') {
                $this->prompt = (string) $conversation->draft_prompt;
            }
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
                
                $attachmentData = [];
                if ($msg->attachments) {
                    foreach ($msg->attachments as $att) {
                        $attachmentData[] = [
                            'file_path' => $att->file_path,
                            'file_type' => $att->file_type,
                            'file_name' => $att->file_name,
                        ];
                    }
                }
                
                $this->messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'rating' => $msg->rating,
                    'artifact' => $artifactData,
                    'attachments' => $attachmentData,
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
        $this->attachments = [];
        $this->conversationId = null;
        $this->activeProjectId = null;
    }

    #[On('startProjectChat')]
    public function startProjectChat($projectId, $initialPrompt = null, $initialModel = null, $webSearch = false)
    {
        $this->resetChat();
        $this->activeProjectId = $projectId;
        
        if ($initialModel) {
            $this->selectedModel = $initialModel;
        }
        
        $this->webSearch = $webSearch;
        
        if (!empty($initialPrompt)) {
            $this->prompt = $initialPrompt;
            $this->sendMessage();
        }
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
        $artifact = \App\Models\MessageArtifact::whereHas(
            'message.conversation',
            fn ($q) => $q->where('user_id', Auth::id())
        )->find($id);
        if ($artifact) {
            $artifactData = [
                'id' => $artifact->id,
                'type' => $artifact->type,
                'language' => $artifact->language,
                'title' => $artifact->title,
            ];
            $this->dispatch('openArtifact', artifact: $artifactData);
        }
    }

    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
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

    /**
     * Open the memory panel for the current conversation, loading whatever durable
     * memory has been recorded so the user can read or edit it.
     */
    public function openMemory()
    {
        if (!$this->conversationId) {
            return;
        }
        $conv = Conversation::find($this->conversationId);
        if ($conv && $conv->user_id === Auth::id()) {
            $this->memoryDraft = (string) $conv->memory;
            $this->memoryUpdatedAt = $conv->memory_updated_at?->diffForHumans();
            $this->showMemory = true;
        }
    }

    /**
     * Persist the user's hand-edited memory. An empty draft clears the memory.
     */
    public function saveMemory()
    {
        if (!$this->conversationId) {
            return;
        }
        $conv = Conversation::find($this->conversationId);
        if ($conv && $conv->user_id === Auth::id()) {
            $memory = trim($this->memoryDraft);
            $conv->update([
                'memory' => $memory !== '' ? $memory : null,
                'memory_updated_at' => now(),
            ]);
            $this->memoryUpdatedAt = now()->diffForHumans();
            $this->showMemory = false;
        }
    }

    /**
     * Clear the draft in the editor (does not persist until Save is pressed).
     */
    public function clearMemory()
    {
        $this->memoryDraft = '';
    }

    /**
     * Build artifact-aware system-prompt context for the current turn.
     *
     * - Always includes a compact outline of the most recent skripsi-style
     *   artifact, so the AI knows the chapter structure without re-reading
     *   the whole body.
     * - If the user mentions a specific "Bab N" (Arabic or Roman), extracts
     *   that chapter's text from the artifact and includes it verbatim — so
     *   "perbaiki Bab 2" works without the user pasting Bab 2.
     */
    private function buildArtifactContext(\App\Models\Conversation $conversation, string $userText): string
    {
        $artifact = \App\Models\MessageArtifact::query()
            ->whereHas('message', fn ($q) => $q->where('conversation_id', $conversation->id))
            ->whereIn('language', ['markdown', 'md'])
            ->latest('id')
            ->first();

        if (! $artifact) {
            return '';
        }

        $context = "\n\n--- ACTIVE DOCUMENT CONTEXT ---\n"
            . "The user is currently working on this document (artifact id #{$artifact->id}, title: \"{$artifact->title}\"). "
            . "When the user asks to revise, continue, or expand a chapter, treat THIS document as the source of truth.\n";

        // Outline (always include — cheap).
        $outline = is_array($artifact->outline_json) ? $artifact->outline_json : \App\Models\MessageArtifact::extractOutline($artifact->content);
        if (! empty($outline)) {
            $context .= "\nDocument outline (heading tree):\n";
            foreach ($outline as $h) {
                $indent = str_repeat('  ', max(0, ($h['level'] ?? 1) - 1));
                $context .= $indent . '- ' . trim((string) ($h['text'] ?? '')) . "\n";
            }
        }

        // Detect Bab N reference.
        $requestedBab = $this->detectBabReference($userText);
        if ($requestedBab !== null) {
            $excerpt = $this->extractBab($artifact->content, $requestedBab);
            if ($excerpt !== null) {
                $context .= "\nThe user referenced **BAB {$requestedBab}**. Verbatim contents of that chapter from the active document (use this as the basis for your revision/continuation, do NOT ask the user what was there):\n\n";
                $context .= "```markdown\n" . $excerpt . "\n```\n";
            }
        }

        return $context;
    }

    /**
     * Extract a chapter number from "BAB 2", "Bab II", "perbaiki bab iii", etc.
     * Returns the chapter number as an integer, or null if no reference found.
     */
    private function detectBabReference(string $text): ?int
    {
        if (! preg_match('/\bbab\s+([ivxlcdm]+|\d{1,2})\b/i', $text, $m)) {
            return null;
        }
        $token = strtolower($m[1]);
        if (ctype_digit($token)) {
            $n = (int) $token;
            return ($n >= 1 && $n <= 99) ? $n : null;
        }
        // Roman numeral parser (good up to 99).
        static $rom = ['i' => 1, 'v' => 5, 'x' => 10, 'l' => 50, 'c' => 100, 'd' => 500, 'm' => 1000];
        $sum = 0;
        $len = strlen($token);
        for ($i = 0; $i < $len; $i++) {
            $cur = $rom[$token[$i]] ?? 0;
            $next = $i + 1 < $len ? ($rom[$token[$i + 1]] ?? 0) : 0;
            $sum += ($cur < $next) ? -$cur : $cur;
        }
        return ($sum >= 1 && $sum <= 99) ? $sum : null;
    }

    /**
     * Extract the markdown body of "BAB N …" (level-1 heading) from a document.
     * Returns the chapter text (capped at 6000 chars) or null when not found.
     */
    private function extractBab(?string $markdown, int $bab): ?string
    {
        if (empty($markdown)) {
            return null;
        }
        $roman = $this->intToRoman($bab);
        // Match a level-1 heading whose text starts with "BAB N" or "BAB <roman>".
        $pattern = '/^#\s+BAB\s+(?:' . $bab . '|' . preg_quote($roman, '/') . ')\b.*$/mi';
        if (! preg_match($pattern, $markdown, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $m[0][1];
        // End at the next level-1 heading, or EOF.
        $rest = substr($markdown, $start + strlen($m[0][0]));
        if (preg_match('/\n#\s+/', $rest, $nextM, PREG_OFFSET_CAPTURE)) {
            $end = $start + strlen($m[0][0]) + $nextM[0][1];
            $chapter = substr($markdown, $start, $end - $start);
        } else {
            $chapter = substr($markdown, $start);
        }

        $chapter = trim((string) $chapter);
        if (mb_strlen($chapter) > 6000) {
            $chapter = mb_substr($chapter, 0, 6000) . "\n\n[... chapter truncated]";
        }
        return $chapter;
    }

    private function intToRoman(int $n): string
    {
        $map = [1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC',
                50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
        $out = '';
        foreach ($map as $v => $r) {
            while ($n >= $v) { $out .= $r; $n -= $v; }
        }
        return $out;
    }

    /**
     * Build a compact, line-per-message textual digest of conversation messages
     * that fell out of the recent-window slice. Uses a heuristic (first
     * sentence per message, capped) — cheap, deterministic, no extra LLM call
     * (ConversationMemoryService handles the deep summarization separately).
     */
    private function buildMiddleDigest(array $messages): string
    {
        if (empty($messages)) {
            return '';
        }
        $out = [];
        foreach ($messages as $m) {
            $role = ($m['role'] ?? '') === 'user' ? 'User' : 'Assistant';
            $text = trim((string) ($m['content'] ?? ''));
            if ($text === '') continue;
            $text = preg_replace('/<antArtifact[^>]*>.*?<\/antArtifact>/is', '[artifact]', $text) ?? $text;
            $first = preg_split('/(?<=[.!?])\s/', $text, 2)[0] ?? $text;
            if (mb_strlen($first) > 240) {
                $first = mb_substr($first, 0, 240) . '…';
            }
            $out[] = $role . ': ' . $first;
            // Keep the digest itself bounded so we don't undo the savings.
            if (count($out) >= 80) {
                $out[] = '... (' . (count($messages) - count($out) + 1) . ' more earlier messages omitted)';
                break;
            }
        }
        return implode("\n", $out);
    }

    /**
     * Push a short "what the assistant is doing right now" status to the UI. Uses a
     * dedicated wire:stream target so it updates live, mid-request, independent of the
     * answer text. Keeps the user informed during web search, generation, etc.
     */
    private function streamActivity(string $label): void
    {
        $html = '<span class="inline-flex items-center gap-2">'
            . '<span class="w-1.5 h-1.5 rounded-full bg-[#D97757] animate-pulse"></span>'
            . e($label) . '</span>';
        $this->stream(to: 'activity-status', content: $html, replace: true);
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

        if (empty($text) && empty($this->attachments)) {
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

        $attachmentData = [];
        if (!empty($this->attachments)) {
            foreach ($this->attachments as $att) {
                $path = $att->store('attachments', 'public');
                $attModel = \App\Models\MessageAttachment::create([
                    'message_id' => $userMessage->id,
                    'file_path' => $path,
                    'file_type' => $att->getMimeType(),
                    'file_name' => $att->getClientOriginalName(),
                ]);

                $attachmentData[] = [
                    'file_path' => $path,
                    'file_type' => $att->getMimeType(),
                    'file_name' => $att->getClientOriginalName(),
                ];
            }
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $text,
            'attachments' => $attachmentData,
        ];

        $this->prompt = '';
        $this->attachments = [];

        // Clear the autosaved draft now that the message is on its way.
        if ($this->conversationId) {
            Conversation::where('id', $this->conversationId)
                ->where('user_id', Auth::id())
                ->update(['draft_prompt' => null]);
        }

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

        // Prepare sliding window context.
        //
        // Strategy: keep the first 2 messages (often: opening request setting
        // the task) + the last (historySize - 2) messages verbatim. The middle
        // chunk is condensed into a textual digest that gets folded into the
        // system prompt below so the AI still has peripheral awareness of what
        // was discussed without paying the token cost of every old message.
        //
        // Provider compatibility: we do NOT insert synthetic user/assistant
        // messages (Anthropic requires strict alternation); the digest rides
        // along in the system prompt instead. Persistent memory + per-chapter
        // outline (above) handle the deeper "what did we decide" questions.
        $historySize = 200;
        $keepFirst = 2;

        $userMessages = array_values(array_filter($this->messages, fn ($m) => $m['role'] !== 'system'));
        $totalMsgs = count($userMessages);
        $middleDigest = '';

        if ($totalMsgs > $historySize) {
            $firstMessages = array_slice($userMessages, 0, $keepFirst);
            $recentMessages = array_slice($userMessages, -($historySize - $keepFirst));
            $middleMessages = array_slice($userMessages, $keepFirst, $totalMsgs - $keepFirst - ($historySize - $keepFirst));
            $middleDigest = $this->buildMiddleDigest($middleMessages);
            $messagesForAi = array_merge($firstMessages, $recentMessages);
        } else {
            $messagesForAi = $userMessages;
        }

        // Prepend system prompt for Artifacts
        $baseSystemPrompt = "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, files, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a document, report, PDF, DOCX, or any text-based file, you MUST generate a well-structured Markdown document (language=\"markdown\") inside the <antArtifact> tag. DO NOT EVER generate raw file byte streams or PostScript code. The system will automatically convert your Markdown into downloadable files for the user. Focus only on writing excellent text content inside the <antArtifact> tag. Provide brief explanation outside the tag if needed.";

        // Document quality — produce print-ready PDFs (reports, papers, skripsi).
        $baseSystemPrompt .= "\n\nCRITICAL INSTRUCTION FOR PDF/DOCX REQUESTS:\n"
            . "When the user asks for a PDF, DOCX, or document, they are interacting with an external system that handles the file conversion. Therefore, you are STRICTLY FORBIDDEN from apologizing, claiming you cannot generate PDFs, or suggesting external tools like Word, Google Docs, Pandoc, or Typora. Your ONLY allowed response is to immediately generate the content as Markdown inside an <antArtifact> block. The system will seamlessly convert your Markdown artifact into the requested file format. Failure to use <antArtifact> or explaining your limitations will break the application.\n\n"
            . "DOCUMENT GENERATION (when the user asks for a document, report, paper, makalah, laporan, skripsi, jurnal, artikel, file, PDF, DOCX, etc., OR when they ask to 'continue' a previous chapter/document):\n"
            . "- Write a markdown artifact (language=\"markdown\"). The system renders it to a polished PDF or document for the user automatically.\n"
            . "- WARNING: The content inside the <antArtifact> block is exported directly to the final PDF/DOCX. DO NOT include any conversational text, meta-commentary, or formatting explanations (e.g., 'Berikut adalah laporannya...' or 'Penjelasan Format:') INSIDE the artifact. ALL conversational text MUST be placed OUTSIDE the <antArtifact> block.\n"
            . "- If the user asks you to continue a document (e.g., 'lanjut bab 2'), you MUST generate a NEW <antArtifact> block containing the continuation. DO NOT just reply with raw text.\n"
            . "- If the user asks you to merge, combine, or join multiple attached documents, ACT AS AN INTELLIGENT EDITOR: do NOT just blindly copy-paste text. Clean up the text by removing redundant page numbers, repeating headers/footers, and fixing broken sentences across page breaks. Smooth out transitions between documents.\n"
            . "- Diagrams, flowcharts, charts, org/structure figures: output INLINE raw <svg>…</svg> (mPDF renders SVG natively). Do NOT use ASCII diagrams or mermaid. Wrap each figure as <figure><svg…>…</svg><figcaption>Gambar X.Y Caption text</figcaption></figure>. If the extracted text mentions a diagram but it's missing, creatively generate an SVG diagram to replace it!\n"
            . "- To include an image the user uploaded, reference it with markdown: ![Keterangan](attachments/<filename>) using the path from the conversation; the renderer resolves local uploads automatically.\n"
            . "- For FORMAL / ACADEMIC documents (skripsi, laporan, thesis): begin the artifact content with a YAML front-matter block to trigger the academic layout (cover page, automatic DAFTAR ISI / Table of Contents, Roman→Arabic page numbering, 4-3-3-3 cm margins, Times New Roman 12pt, justified). Use exactly this shape (omit fields you don't know):\n"
            . "---\nmode: skripsi            # skripsi | laporan | jurnal | document\njudul: <full title>\npenulis: <author name>\nnim: <student id>\nprodi: <study program>\nfakultas: <faculty>\nuniversitas: <university>\nkota: <city>\ntahun: <year>\npembimbing: <advisor>\n---\n"
            . "Then structure chapters as level-1 headings (# BAB I PENDAHULUAN, # BAB II …) — each # heading starts a new page — with ## and ### for sub-sections (## 1.1 Latar Belakang). Headings are collected into the Table of Contents automatically.\n"
            . "- OPTIONAL FORMAT OVERRIDES: to mimic a specific format (e.g. a template the user uploaded or described), add any of these keys to the front-matter. Omit them to use the defaults (Times New Roman 12pt, 1.5 spacing, 4-3-3-3 cm, page number bottom-center):\n"
            . "font: <Times New Roman | Arial | Courier>   # body font\nfont_size: <11 | 12>                        # in pt (8–20)\nline_spacing: <1 | 1.15 | 1.5 | 2>          # 1=single, 2=double\nalign: <justify | left>\nmargin_top: <cm>\nmargin_right: <cm>\nmargin_bottom: <cm>\nmargin_left: <cm>\npage_number: <bottom-center | bottom-right | top-right | top-center | none>\n"
            . "- MATCHING AN UPLOADED TEMPLATE/EXAMPLE: if the user attaches or pastes a sample document (contoh/template) and asks you to follow its format, replicate BOTH its structure (chapter order, section/heading names, the exact cover fields and their labels, daftar isi style) AND its formatting (set the font/font_size/line_spacing/margins/page_number front-matter fields to match what the sample uses). The attachment is provided as extracted text, so infer the font/margins/spacing from what is stated or what is conventional for that institution, and reproduce the wording of section titles faithfully.\n"
            . "\n--- CAPTION & NUMBERING RULES (WAJIB untuk semua dokumen akademik) ---\n"
            . "- Penomoran gambar & tabel BERBASIS BAB: angka pertama = nomor bab. Contoh: Gambar 3.1, Tabel 2.4.\n"
            . "- GAMBAR (termasuk diagram, grafik, flowchart, screenshot): caption diletakkan DI BAWAH gambar, rata tengah. Gunakan format: <figure>...<figcaption>Gambar X.Y Keterangan</figcaption></figure>.\n"
            . "- TABEL: judul/caption diletakkan DI ATAS tabel, rata tengah. Gunakan format: <div class='table-caption'>Tabel X.Y Keterangan</div> diikuti <table>...</table>.\n"
            . "- Setiap gambar/tabel WAJIB dirujuk dalam teks SEBELUM kemunculannya. Contoh: '…seperti ditunjukkan pada Gambar 3.1.' atau '…dapat dilihat pada Tabel 2.1.'\n"
            . "\n--- BOLD & ITALIC RULES ---\n"
            . "- Bold: HANYA untuk judul bab, sub-bab, dan sub-sub-bab (heading). JANGAN bold teks biasa.\n"
            . "- Italic WAJIB untuk: istilah/kata asing yang BELUM diserap ke Bahasa Indonesia (contoh: *retrieval-augmented generation*, *prompt*, *dataset*, *framework*, *preprocessing*, *fine-tuning*, *overfitting*).\n"
            . "- Istilah asing yang sudah baku/diserap ditulis TEGAK (tanpa italic): internet, data, online, server, file, software.\n"
            . "\n--- KUTIPAN LANGSUNG ---\n"
            . "- Kutipan langsung > 40 kata: gunakan blockquote (> ...) dengan 1 spasi, menjorok dari margin kiri. Sertakan sumber sitasi.\n"
            . "- Kutipan langsung ≤ 40 kata: tulis inline dalam teks dengan tanda kutip.\n"
            . "\n--- DAFTAR PUSTAKA (IEEE Style default) ---\n"
            . "- Gunakan gaya IEEE: sitasi dalam teks berbentuk [1], [2], [3].\n"
            . "- Daftar pustaka diurutkan sesuai urutan kemunculan dalam teks (BUKAN alfabetis).\n"
            . "- Format entri IEEE: [n] Inisial. Nama, \"Judul artikel,\" *Nama Jurnal*, vol. X, no. Y, pp. A–B, Tahun.\n"
            . "- Utamakan sumber 5 tahun terakhir & terindeks (jurnal, prosiding).\n"
            . "- Bungkus seluruh daftar pustaka dalam <div class='daftar-pustaka'>...</div>.\n"
            . "\n--- LAMPIRAN ---\n"
            . "- Diletakkan di bagian akhir setelah Daftar Pustaka.\n"
            . "- Penomoran: # LAMPIRAN A Judul, # LAMPIRAN B Judul, dst.\n"
            . "- Isi: source code inti, kuesioner, hasil pengujian, surat izin, dsb.\n"
            . "\n--- MODE JURNAL / ARTIKEL (mode: jurnal) ---\n"
            . "- Untuk artikel jurnal ilmiah, gunakan front-matter dengan mode: jurnal.\n"
            . "- Layout otomatis 2 kolom, font 10pt, 1 spasi.\n"
            . "- Struktur: Abstrak → Pendahuluan → Metode → Hasil & Pembahasan → Kesimpulan → Referensi.\n"
            . "- TIDAK ada cover page dan Daftar Isi.\n"
            . "- Caption gambar/tabel ukuran lebih kecil (8–9pt).\n"
            . "- Panjang ringkas: 6–15 halaman.\n"
            . "- Abstrak ≤ 250 kata, disertai kata kunci.\n"
            . "- For casual/simple documents, OMIT the front-matter; the renderer applies a clean general layout. Choose academic vs. casual based on what the user actually asked for.";

        // Response-quality principles: structured reasoning, sourcing, and honesty.
        $baseSystemPrompt .= "\n\nResponse principles (apply to every answer):\n"
            . "- For non-trivial questions, reason through the problem step by step before answering, then present a clear, well-structured response (use short headings or lists when they aid clarity).\n"
            . "- Accuracy first: if you are unsure or lack the information, say so plainly instead of inventing facts, and separate what you know from what you are inferring.\n"
            . "- When web search results are provided below, ground your answer in them and cite the relevant source titles or links inline.\n"
            . "- Stay consistent with the Persistent Conversation Memory below when it is present.\n"
            . "- Keep answers focused — concise for simple asks, thorough for complex ones — and match the user's language.";

        if (Auth::check() && !empty(Auth::user()->custom_instructions)) {
            $baseSystemPrompt .= "\n\nUser Custom Instructions:\n" . Auth::user()->custom_instructions;
        }

        // Force the assistant to reply in the user's preferred language.
        if (Auth::check()) {
            $languageNames = [
                'en' => 'English',
                'id' => 'Bahasa Indonesia',
                'es' => 'Spanish (Español)',
                'fr' => 'French (Français)',
                'de' => 'German (Deutsch)',
                'ja' => 'Japanese (日本語)',
                'zh' => 'Chinese (中文)',
                'ar' => 'Arabic (العربية)',
            ];
            $lang = Auth::user()->preferences['language'] ?? 'en';
            if ($lang !== 'en' && isset($languageNames[$lang])) {
                $baseSystemPrompt .= "\n\nIMPORTANT: Always respond to the user in {$languageNames[$lang]}, regardless of the language the user writes in, unless the user explicitly asks for another language.";
            }
        }

        // Inject the user's active Skills so the behaviour configured in the
        // Customize panel actually shapes the assistant's responses.
        if (Auth::check()) {
            $activeSkills = \App\Models\Skill::where('user_id', Auth::id())
                ->where('is_active', true)
                ->get();
            if ($activeSkills->isNotEmpty()) {
                $baseSystemPrompt .= "\n\nActive Skills (apply these behaviours):";
                foreach ($activeSkills as $skill) {
                    $baseSystemPrompt .= "\n\n[Skill: {$skill->name}]\n" . $skill->instructions;
                }
            }
        }

        if ($this->activeProjectId) {
            $project = \App\Models\Project::with('files')->find($this->activeProjectId);
            if ($project) {
                if ($project->description) {
                    $baseSystemPrompt .= "\n\nProject Context (Memory):\n" . $project->description;
                }
                if ($project->custom_instructions) {
                    $baseSystemPrompt .= "\n\nProject Custom Instructions:\n" . $project->custom_instructions;
                }
                if ($project->files->count() > 0) {
                    $baseSystemPrompt .= "\n\nProject Knowledge Files:\n";
                    foreach ($project->files as $file) {
                        if (\Illuminate\Support\Facades\Storage::exists($file->file_path)) {
                            $content = \Illuminate\Support\Facades\Storage::get($file->file_path);
                            // Cap each file at 200KB. The old 2MB cap routinely blew the
                            // context window for cheap models (Haiku ~200K tokens) and
                            // wasted cost on long files where only the early sections
                            // ever mattered. If the user attaches huge corpora they
                            // should ask Rynude to summarize per-file first.
                            $truncated = substr($content, 0, 200000);
                            $note = strlen($content) > 200000
                                ? "\n[... file truncated at 200KB; total " . number_format(strlen($content)) . " bytes — ask user to split or summarize if more is needed]"
                                : '';
                            $baseSystemPrompt .= "\n--- Document: {$file->file_name} ---\n" . $truncated . $note . "\n";
                        }
                    }
                }
            }
        }

        // Persistent conversation memory: durable facts distilled from this chat,
        // injected every turn so context survives the sliding window above AND
        // survives switching models mid-conversation (memory lives in the DB, not
        // in any single model's context).
        $conversation = $this->conversationId
            ? Conversation::find($this->conversationId)
            : null;
        $memoryService = app(\App\Services\AI\ConversationMemoryService::class);
        if ($conversation) {
            $baseSystemPrompt .= $memoryService->formatForPrompt($conversation);
        }

        // Artifact-aware context: when the user references a chapter ("perbaiki
        // Bab 2", "lanjut bab III", "tambah ke BAB 4"), pull the matching
        // section from the most recent skripsi artifact in this chat so the
        // AI can revise it instead of asking what's there. Without this the
        // AI has only chat-message text — never the artifact body itself.
        if ($conversation) {
            $lastUserText = '';
            for ($i = count($messagesForAi) - 1; $i >= 0; $i--) {
                if (($messagesForAi[$i]['role'] ?? '') === 'user') {
                    $lastUserText = (string) $messagesForAi[$i]['content'];
                    break;
                }
            }
            $artifactContext = $this->buildArtifactContext($conversation, $lastUserText);
            if ($artifactContext !== '') {
                $baseSystemPrompt .= $artifactContext;
            }
        }

        // Middle-message digest from the sliding window (only present when the
        // conversation has overflowed the recent-window). Keeps the AI loosely
        // aware of what was discussed in the dropped middle without paying
        // per-token cost for the full messages.
        if ($middleDigest !== '') {
            $baseSystemPrompt .= "\n\n--- EARLIER CONVERSATION DIGEST ---\n"
                . "These exchanges fell outside the recent message window. They're summarised here so you stay aware of the thread; lean on PERSISTENT MEMORY (above) for durable facts.\n\n"
                . $middleDigest;
        }

        // Web search: ground the answer in current information when enabled.
        if ($this->webSearch) {
            $lastUserPrompt = '';
            for ($i = count($messagesForAi) - 1; $i >= 0; $i--) {
                if (($messagesForAi[$i]['role'] ?? '') === 'user') {
                    $lastUserPrompt = $messagesForAi[$i]['content'];
                    break;
                }
            }
            if ($lastUserPrompt !== '') {
                $this->streamActivity('Searching the web…');
                $searchService = new \App\Services\WebSearchService();
                $results = $searchService->search($lastUserPrompt, 5);
                if (!empty($results)) {
                    $baseSystemPrompt .= $searchService->formatForPrompt($results);
                }
            }
        }

        array_unshift($messagesForAi, [
            'role' => 'system',
            'content' => $baseSystemPrompt,
        ]);
        
        // Append a strict formatting reminder to the very last user message
        // This prevents the AI from "forgetting" the artifact tags or "apologizing" in long conversations
        if (!empty($messagesForAi)) {
            $lastIndex = count($messagesForAi) - 1;
            if ($messagesForAi[$lastIndex]['role'] === 'user') {
                $messagesForAi[$lastIndex]['content'] .= "\n\n[SYSTEM REMINDER: If the user asked for a PDF, DOCX, or document, you MUST NOT apologize. You MUST output your content EXCLUSIVELY inside an <antArtifact> block. The system will convert it.]";
            }
        }

        // Clear any stale stop flag before we begin streaming
        $stopKey = 'chat_stop_' . $this->conversationId;
        \Illuminate\Support\Facades\Cache::forget($stopKey);

        // Call AI Service (resolved from the container so it can be faked in tests)
        $aiService = app(AiService::class);
        $stream = $aiService->streamResponse($messagesForAi, $this->selectedModel ?? 'claude-haiku-4-5');

        $this->streamActivity('Generating response…');

        $fullResponse = '';
        $stopped = false;
        $artifactAnnounced = false;

        // Markdown re-parsing on every chunk is O(n²) over response length; for
        // a 50 KB markdown reply with hundreds of small chunks that turns into
        // megabytes of redundant parser work. We throttle: only re-render when
        // ≥120 new chars have accumulated OR 100 ms have passed. The final
        // chunk after the loop always flushes the latest state.
        $lastRenderedLen = 0;
        $lastRenderTs = microtime(true);
        $loadingHtml = '<div class="mt-3 inline-flex items-center gap-3 border border-[#E5E5E5] dark:border-stone-700 rounded-xl p-2 pr-4 bg-white dark:bg-stone-800 shadow-sm max-w-full">
                <div class="w-10 h-10 bg-[#F9F8F6] dark:bg-stone-700 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#D97757] animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-[14px] font-medium text-[#2D2825] dark:text-stone-200 truncate">Generating Artifact...</h4>
                    <p class="text-[12px] text-stone-500 mt-0.5 truncate">Writing content</p>
                </div>
            </div>';
        $pattern = '/<(?:antA|a)rtifact\b[^>]*>([\s\S]*?)(?:<\/(?:antA|a)rtifact>|$)/i';

        $renderFrame = function () use (&$fullResponse, $pattern, $loadingHtml, &$artifactAnnounced) {
            $displayContent = preg_replace_callback($pattern, fn () => $loadingHtml, $fullResponse) ?? $fullResponse;
            if (preg_match('/<(?:antA|a)rtifact\b/i', $displayContent, $m, PREG_OFFSET_CAPTURE)) {
                $displayContent = substr($displayContent, 0, $m[0][1]) . $loadingHtml;
                if (! $artifactAnnounced) {
                    $this->streamActivity('Writing artifact…');
                    $artifactAnnounced = true;
                }
            }
            $this->stream(
                to: 'message-stream',
                content: \Illuminate\Support\Str::markdown($displayContent),
                replace: true,
            );
        };

        // --- Observability Agent Pipeline ---
        // Subscribe to emit orchestrator events dynamically
        $emitter = app(\App\Contracts\EventEmitterInterface::class);
        $emitter->subscribe(function (\App\Domain\AgentEvent $event) {
            // Push events to frontend via Livewire stream
            // Need a sleep here to visibly show the UI updating, as the dummy orchestrator is instant
            usleep(500000); // 0.5 sec per event for testing visibility
            $this->stream(
                to: 'agent-stream-target',
                content: '<div x-data x-init="window.dispatchEvent(new CustomEvent(\'agent-stream-event\', { detail: ' . htmlspecialchars(json_encode($event->toArray()), ENT_QUOTES, 'UTF-8') . ' }))"></div>',
                replace: false
            );
        });

        $orchestrator = app(\App\Services\Orchestrator\AgentOrchestrator::class);
        $agentId = (string) \Illuminate\Support\Str::uuid();
        $workflowId = (string) \Illuminate\Support\Str::uuid();

        // Run the agent orchestrator pipeline (Understanding, Planning, Research, Writing, Reviewing)
        try {
            $orchestrator->execute((string)$this->conversationId, $agentId, $workflowId);
        } catch (\Throwable $e) {
            // Handle error in pipeline if necessary
        }

        // --- End Observability ---

        foreach ($stream as $chunk) {
            // Stop generation requested by the user
            if (\Illuminate\Support\Facades\Cache::get($stopKey)) {
                \Illuminate\Support\Facades\Cache::forget($stopKey);
                $stopped = true;
                break;
            }

            $fullResponse .= $chunk;
            $now = microtime(true);
            $delta = strlen($fullResponse) - $lastRenderedLen;
            $stale = ($now - $lastRenderTs) * 1000 >= 100; // ms
            if ($delta < 120 && ! $stale) {
                continue;
            }
            $lastRenderedLen = strlen($fullResponse);
            $lastRenderTs = $now;
            $renderFrame();
        }
        // Final flush so the user sees the very last bytes of the stream.
        if ($lastRenderedLen < strlen($fullResponse)) {
            $renderFrame();
        }

        // If the user stopped an empty generation, store a small placeholder
        if ($stopped && trim($fullResponse) === '') {
            $fullResponse = '_Generation stopped._';
        }

        // After stream is done, parse artifact if present
        $artifactData = null;
        $pattern = '/<(?:antA|a)rtifact\b([^>]*)>([\s\S]*?)(?:<\/(?:antA|a)rtifact>|$)/i';
        
        if (preg_match($pattern, $fullResponse, $matches)) {
            $attrString = $matches[1];
            $content = trim($matches[2]);
            
            $identifier = preg_match('/identifier="([^"]+)"/i', $attrString, $m) ? $m[1] : 'artifact-' . uniqid();
            $type = preg_match('/type="([^"]+)"/i', $attrString, $m) ? $m[1] : 'application/vnd.ant.code';
            $language = preg_match('/language="([^"]*)"/i', $attrString, $m) ? $m[1] : 'markdown';
            $title = preg_match('/title="([^"]+)"/i', $attrString, $m) ? $m[1] : 'Document';
            
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
                'type' => str_contains($type, 'code') ? 'code' : 'text',
                'language' => $language ?: 'text',
                'title' => $title,
                'content' => $content,
                'user_id' => Auth::id(),                        // denormalized owner for fast ownership checks
                'outline_json' => MessageArtifact::extractOutline($content), // heading tree for Bab-N lookup
            ]);

            $artifactData = [
                'id' => $artModel->id,
                'type' => $artModel->type,
                'language' => $artModel->language,
                'title' => $artModel->title,
                'content' => $artModel->content,
            ];
            $fullResponse = $cleanResponse;
            // Soft signal — ChatLayout decides whether to auto-open. If the user
            // has navigated to another panel, it stays closed; they can still
            // open it from the "View artifact" badge in the message.
            $this->dispatch('artifactReady', id: $artModel->id);
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

        // Refresh the durable conversation memory once enough new messages have
        // accumulated. Dispatched as a queued job so the user round-trip ends
        // immediately. With the default sync queue driver it still runs inline.
        if ($conversation && $memoryService->shouldRefresh($conversation, count($this->messages))) {
            \App\Jobs\RefreshConversationMemory::dispatch(
                $conversation->id,
                $this->selectedModel ?? 'claude-haiku-4-5'
            );
        }

        // Generate a title for new chats via a job (sync driver = inline). The
        // user-visible round-trip ends here regardless of how long the title
        // model takes. We refresh the sidebar optimistically; the title pops
        // in when the job finishes.
        if ($this->conversationId) {
            $conversationForTitle = \App\Models\Conversation::find($this->conversationId);
            if ($conversationForTitle && $conversationForTitle->title === 'New Chat') {
                $userMsg = collect($this->messages)->firstWhere('role', 'user');
                if ($userMsg) {
                    \App\Jobs\GenerateChatTitle::dispatch(
                        $conversationForTitle,
                        (string) $userMsg['content'],
                        $this->selectedModel ?? 'claude-haiku-4-5',
                        Auth::id(),
                    );
                    $this->dispatch('chatCreated');
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.chat-interface');
    }
}
