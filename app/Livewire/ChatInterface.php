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

    public function loadConversation()
    {
        $conversation = Conversation::with(['messages.artifacts', 'messages.attachments'])->find($this->conversationId);
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
                'content' => $artifact->content,
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
        $historySize = 100;
        
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
        $baseSystemPrompt = "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, files, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a document, report, PDF, DOCX, or any text-based file, you MUST generate a well-structured Markdown document (language=\"markdown\") inside the <antArtifact> tag. DO NOT EVER generate raw file byte streams or PostScript code. The system will automatically convert your Markdown into downloadable files for the user. Focus only on writing excellent text content inside the <antArtifact> tag. Provide brief explanation outside the tag if needed.";

        // Document quality — produce print-ready PDFs (reports, papers, skripsi).
        $baseSystemPrompt .= "\n\nCRITICAL INSTRUCTION FOR PDF/DOCX REQUESTS:\n"
            . "When the user asks for a PDF, DOCX, or document, they are interacting with an external system that handles the file conversion. Therefore, you are STRICTLY FORBIDDEN from apologizing, claiming you cannot generate PDFs, or suggesting external tools like Word, Google Docs, Pandoc, or Typora. Your ONLY allowed response is to immediately generate the content as Markdown inside an <antArtifact> block. The system will seamlessly convert your Markdown artifact into the requested file format. Failure to use <antArtifact> or explaining your limitations will break the application.\n\n"
            . "DOCUMENT GENERATION (when the user asks for a document, report, paper, makalah, laporan, skripsi, jurnal, artikel, file, PDF, DOCX, etc., OR when they ask to 'continue' a previous chapter/document):\n"
            . "- Write a markdown artifact (language=\"markdown\"). The system renders it to a polished PDF or document for the user automatically.\n"
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
                            // Extract document text, increase limit for large context
                            $content = \Illuminate\Support\Facades\Storage::get($file->file_path);
                            $baseSystemPrompt .= "\n--- Document: {$file->file_name} ---\n" . substr($content, 0, 2000000) . "\n";
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
        foreach ($stream as $chunk) {
            // Stop generation requested by the user
            if (\Illuminate\Support\Facades\Cache::get($stopKey)) {
                \Illuminate\Support\Facades\Cache::forget($stopKey);
                $stopped = true;
                break;
            }

            $fullResponse .= $chunk;
            
            $displayContent = $fullResponse;
            $pattern = '/<(?:antA|a)rtifact\b[^>]*>([\s\S]*?)(?:<\/(?:antA|a)rtifact>|$)/i';
            
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

            if (preg_match('/<(?:antA|a)rtifact\b/i', $displayContent, $m, PREG_OFFSET_CAPTURE)) {
                $displayContent = substr($displayContent, 0, $m[0][1]) . $loadingHtml;
                if (!$artifactAnnounced) {
                    $this->streamActivity('Writing artifact…');
                    $artifactAnnounced = true;
                }
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
            ]);

            $artifactData = [
                'id' => $artModel->id,
                'type' => $artModel->type,
                'language' => $artModel->language,
                'title' => $artModel->title,
                'content' => $artModel->content,
            ];
            $fullResponse = $cleanResponse;
            $this->dispatch('openArtifact', artifact: $artifactData);
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
        // accumulated. Runs after the answer has streamed so it never delays output,
        // and uses the currently selected model so it always hits an available provider.
        if ($conversation && $memoryService->shouldRefresh($conversation, count($this->messages))) {
            $this->streamActivity('Updating memory…');
            $memoryService->refresh(
                $conversation,
                $this->messages,
                $this->selectedModel ?? 'claude-haiku-4-5'
            );
        }

        // Generate a title for new chats. This runs after the answer has already
        // streamed, so it doesn't block visible output. Done inline (not queued) so
        // the title — and the sidebar refresh — appear immediately without depending
        // on a running queue worker.
        if ($this->conversationId) {
            $conversation = \App\Models\Conversation::find($this->conversationId);
            if ($conversation && $conversation->title === 'New Chat') {
                $userMsg = collect($this->messages)->firstWhere('role', 'user');
                if ($userMsg) {
                    try {
                        $titleAi = app(AiService::class);
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
