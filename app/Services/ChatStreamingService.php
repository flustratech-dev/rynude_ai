<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageArtifact;
use App\Services\AI\AiService;
use App\Services\AI\ConversationMemoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Handle AI response streaming for chat conversations.
 *
 * Extracted from ChatInterface::generateResponse() to support both Livewire
 * and REST API streaming endpoints without duplicating the complex business
 * logic (system prompt building, artifact parsing, memory integration, etc.).
 */
class ChatStreamingService
{
    public function __construct(
        protected AiService $aiService,
        protected ConversationMemoryService $memoryService,
    ) {}

    /**
     * Stream an AI response for the given conversation.
     *
     * @param Conversation $conversation The conversation context
     * @param array $messages The message history (user/assistant/system)
     * @param string $model The model identifier (e.g., 'claude-sonnet-4-6')
     * @param bool $webSearch Whether to perform web search before generating
     * @return \Generator Yields SSE-formatted chunks: ['type' => 'content'|'artifact'|'done', 'data' => ...]
     */
    public function stream(
        Conversation $conversation,
        array $messages,
        string $model,
        bool $webSearch = false,
        bool $researchMode = false
    ): \Generator {
        // Prevent PHP from killing the streaming process during long generations
        set_time_limit(0);

        if (empty($messages) || end($messages)['role'] !== 'user') {
            yield ['type' => 'error', 'data' => 'Last message must be from user'];
            return;
        }

        // Build the complete system prompt with all context
        $systemPrompt = $this->buildSystemPrompt($conversation, $messages, $webSearch, $researchMode);

        // Apply sliding window context strategy
        $messagesForAi = $this->applySlidingWindow($messages, $systemPrompt);

        // Clear any stale stop flag before we begin streaming
        $stopKey = 'chat_stop_' . $conversation->id;
        Cache::forget($stopKey);

        // Stream from AI service
        $stream = $this->aiService->streamResponse($messagesForAi, $model);

        $fullResponse = '';
        $stopped = false;

        foreach ($stream as $chunk) {
            // Check if user requested stop
            if (Cache::get($stopKey)) {
                Cache::forget($stopKey);
                $stopped = true;
                break;
            }

            // Providers may yield structured thinking/reasoning deltas as arrays;
            // forward them live but keep them out of the stored answer text.
            if (!is_string($chunk)) {
                if (is_array($chunk) && ($chunk['type'] ?? '') === 'thinking' && ($chunk['text'] ?? '') !== '') {
                    yield ['type' => 'thinking', 'data' => $chunk['text']];
                }
                continue;
            }

            $fullResponse .= $chunk;

            // Yield content chunk to client
            yield [
                'type' => 'content',
                'data' => $chunk,
            ];
        }

        // If the user stopped an empty generation, store a small placeholder
        if ($stopped && trim($fullResponse) === '') {
            $fullResponse = '_Generation stopped._';
        }

        // Parse artifact if present
        $artifactData = $this->parseArtifact($fullResponse);

        // Save assistant message to database
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $artifactData ? $artifactData['cleanResponse'] : $fullResponse,
        ]);

        // Create artifact and link to the actual message
        $artifact = null;
        if ($artifactData) {
            $artifact = MessageArtifact::create([
                'message_id' => $assistantMessage->id,
                'identifier' => $artifactData['identifier'],
                'type' => $artifactData['type'],
                'language' => $artifactData['language'],
                'title' => $artifactData['title'],
                'content' => $artifactData['content'],
                'user_id' => Auth::id(),
                'outline_json' => MessageArtifact::extractOutline($artifactData['content']),
            ]);
        }

        // Yield artifact metadata if one was created
        if ($artifact) {
            yield [
                'type' => 'artifact',
                'data' => [
                    'id' => $artifact->id,
                    'type' => $artifact->type,
                    'language' => $artifact->language,
                    'title' => $artifact->title,
                ],
            ];
        }

        // Refresh durable conversation memory if needed
        if ($this->memoryService->shouldRefresh($conversation, count($messages) + 1)) {
            \App\Jobs\RefreshConversationMemory::dispatch($conversation->id, $model);
        }

        // Generate title for new conversations
        if ($conversation->title === 'New Chat') {
            $userMsg = collect($messages)->firstWhere('role', 'user');
            if ($userMsg) {
                \App\Jobs\GenerateChatTitle::dispatch(
                    $conversation,
                    (string) $userMsg['content'],
                    $model,
                    Auth::id(),
                );
            }
        }

        // Signal completion
        yield [
            'type' => 'done',
            'data' => [
                'message_id' => $assistantMessage->id,
                'artifact_id' => $artifact ? $artifact->id : null,
                'stopped' => $stopped,
            ],
        ];
    }

    /**
     * Build the complete system prompt with all context layers.
     */
    protected function buildSystemPrompt(
        Conversation $conversation,
        array $messages,
        bool $webSearch,
        bool $researchMode = false
    ): string {
        $baseSystemPrompt = $this->getBaseArtifactInstructions();
        $baseSystemPrompt .= $this->getDiagramGenerationInstructions();
        $baseSystemPrompt .= $this->getDocumentQualityInstructions();
        $baseSystemPrompt .= $this->getResponsePrinciples();

        // Custom instructions from user settings
        if (Auth::check() && !empty(Auth::user()->custom_instructions)) {
            $baseSystemPrompt .= "\n\nUser Custom Instructions:\n" . Auth::user()->custom_instructions;
        }

        // Language preference
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

        // Active Skills
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

        // Project context
        if ($conversation->project_id) {
            $baseSystemPrompt .= $this->buildProjectContext($conversation->project_id);
        }

        // Persistent conversation memory
        $baseSystemPrompt .= $this->memoryService->formatForPrompt($conversation);

        // Artifact-aware context (chapter references)
        $lastUserText = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $lastUserText = (string) $messages[$i]['content'];
                break;
            }
        }
        $baseSystemPrompt .= $this->buildArtifactContext($conversation, $lastUserText);

        // Web search results
        if (($webSearch || $researchMode) && $lastUserText !== '') {
            $searchService = new \App\Services\WebSearchService();
            $limit = $researchMode ? 10 : 5;
            $results = $searchService->search($lastUserText, $limit);
            if (!empty($results)) {
                $baseSystemPrompt .= $searchService->formatForPrompt($results);
            }
            if ($researchMode) {
                $baseSystemPrompt .= "\n\nRESEARCH MODE INSTRUCTIONS:\nYou are in deep research mode. Formulate your answer by critically analyzing the search results. Highlight discrepancies, summarize key facts with links/urls directly in the text, and write in a detailed, academic or highly authoritative tone.";
            }
        }

        // Connected Repository (GitHub) Context
        $meta = $conversation->metadata ?? [];
        if (!empty($meta['repo'])) {
            $baseSystemPrompt .= "\n\nCONNECTED REPOSITORY: " . $meta['repo'] . "\n"
                . "Reference this repository when the user asks about code, files, or implementation details. "
                . "Assume the repo follows standard conventions for its detected tech stack.";
        }

        // Selected files context from repository
        if (!empty($meta['selectedFilesContext'])) {
            $baseSystemPrompt .= "\n\n=== SELECTED FILES CONTEXT ===\n"
                . "The user has selected the following files from the repository to be included in your context. "
                . "Use this actual source code to answer their questions or implement changes:\n\n";
            
            foreach ($meta['selectedFilesContext'] as $f) {
                if (isset($f['path'], $f['content'])) {
                    $baseSystemPrompt .= "--- FILE: " . $f['path'] . " ---\n"
                        . "```\n" . $f['content'] . "\n```\n\n";
                }
            }
        }

        return $baseSystemPrompt;
    }

    /**
     * Apply sliding window strategy to message history.
     * Keep first 2 messages + last N messages, digest the middle.
     */
    protected function applySlidingWindow(array $messages, string $systemPrompt): array
    {
        $historySize = 200;
        $keepFirst = 2;

        $userMessages = array_values(array_filter($messages, fn($m) => $m['role'] !== 'system'));
        $totalMsgs = count($userMessages);

        if ($totalMsgs > $historySize) {
            $firstMessages = array_slice($userMessages, 0, $keepFirst);
            $recentMessages = array_slice($userMessages, -($historySize - $keepFirst));
            $middleMessages = array_slice($userMessages, $keepFirst, $totalMsgs - $keepFirst - ($historySize - $keepFirst));

            $middleDigest = $this->buildMiddleDigest($middleMessages);
            if ($middleDigest !== '') {
                $systemPrompt .= "\n\n--- EARLIER CONVERSATION DIGEST ---\n"
                    . "These exchanges fell outside the recent message window. They're summarised here so you stay aware of the thread; lean on PERSISTENT MEMORY (above) for durable facts.\n\n"
                    . $middleDigest;
            }

            $messagesForAi = array_merge($firstMessages, $recentMessages);
        } else {
            $messagesForAi = $userMessages;
        }

        // Prepend system message
        array_unshift($messagesForAi, [
            'role' => 'system',
            'content' => $systemPrompt,
        ]);

        // Append formatting reminder to last user message
        if (!empty($messagesForAi)) {
            $lastIndex = count($messagesForAi) - 1;
            if ($messagesForAi[$lastIndex]['role'] === 'user') {
                $messagesForAi[$lastIndex]['content'] .= "\n\n[SYSTEM REMINDER: If the user asked for a PDF, DOCX, or document, you MUST NOT apologize. You MUST output your content EXCLUSIVELY inside an <antArtifact> block. The system will convert it.]";
            }
        }

        return $messagesForAi;
    }

    /**
     * Parse <antArtifact> tags from response and return parsed data.
     * Does NOT create the artifact in the database - caller must do that after creating the message.
     *
     * @return array|null ['identifier' => string, 'type' => string, 'language' => string, 'title' => string, 'content' => string, 'cleanResponse' => string] or null
     */
    protected function parseArtifact(string $fullResponse): ?array
    {
        $pattern = '/<(?:antA|a)rtifact\b([^>]*)>([\s\S]*?)(?:<\/(?:antA|a)rtifact>|$)/i';

        if (!preg_match($pattern, $fullResponse, $matches)) {
            return null;
        }

        $attrString = $matches[1];
        $content = trim($matches[2]);

        $identifier = preg_match('/identifier="([^"]+)"/i', $attrString, $m) ? $m[1] : 'artifact-' . uniqid();
        $type = preg_match('/type="([^"]+)"/i', $attrString, $m) ? $m[1] : 'application/vnd.ant.code';
        $language = preg_match('/language="([^"]*)"/i', $attrString, $m) ? $m[1] : 'markdown';
        $title = preg_match('/title="([^"]+)"/i', $attrString, $m) ? $m[1] : 'Document';

        // Remove the artifact block from the visible text
        $cleanResponse = preg_replace($pattern, '', $fullResponse);
        $cleanResponse = trim($cleanResponse);

        return [
            'identifier' => $identifier,
            'type' => str_contains($type, 'code') ? 'code' : 'text',
            'language' => $language ?: 'text',
            'title' => $title,
            'content' => $content,
            'cleanResponse' => $cleanResponse,
        ];
    }

    /**
     * Get base artifact generation instructions.
     */
    protected function getBaseArtifactInstructions(): string
    {
        return "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, files, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a document, report, PDF, DOCX, or any text-based file, you MUST generate a well-structured Markdown document (language=\"markdown\") inside the <antArtifact> tag. DO NOT EVER generate raw file byte streams or PostScript code. The system will automatically convert your Markdown into downloadable files for the user. Focus only on writing excellent text content inside the <antArtifact> tag. Provide detailed explanation OUTSIDE the tag describing your approach, structure, and key decisions.";
    }

    /**
     * Get document quality and formatting instructions.
     */
    protected function getDocumentQualityInstructions(): string
    {
        return "\n\nCRITICAL INSTRUCTION FOR PDF/DOCX REQUESTS:\n"
            . "When the user asks for a PDF, DOCX, or document, they are interacting with an external system that handles the file conversion. Therefore, you are STRICTLY FORBIDDEN from apologizing, claiming you cannot generate PDFs, or suggesting external tools like Word, Google Docs, Pandoc, or Typora. Your ONLY allowed response is to immediately generate the content as Markdown inside an <antArtifact> block. The system will seamlessly convert your Markdown artifact into the requested file format. Failure to use <antArtifact> or explaining your limitations will break the application.\n\n"
            . "DOCUMENT GENERATION (when the user asks for a document, report, paper, makalah, laporan, skripsi, jurnal, artikel, file, PDF, DOCX, etc., OR when they ask to 'continue' a previous chapter/document):\n"
            . "- Write a markdown artifact (language=\"markdown\"). The system renders it to a polished PDF or document for the user automatically.\n"
            . "- ⚠️ CRITICAL WARNING - ARTIFACT PURITY ⚠️:\n"
            . "  The <antArtifact> block is the FINAL PDF/DOCX content. It MUST contain ONLY the document itself — NOTHING ELSE.\n"
            . "  ❌ FORBIDDEN INSIDE <antArtifact>:\n"
            . "     • Conversational text: 'Berikut adalah laporannya...', 'Semoga bermanfaat', 'Terima kasih'\n"
            . "     • Meta-commentary: 'Penjelasan Format:', 'Catatan:', 'Format ini mengikuti...'\n"
            . "     • Instructions to user: 'Silakan download...', 'Anda dapat mengedit...'\n"
            . "     • Concluding remarks at the END: 'Demikian laporan ini dibuat...', 'Sekian dan terima kasih'\n"
            . "  ✅ ALLOWED: ONLY the actual document content (front-matter, headings, body text, tables, figures, references)\n"
            . "  📍 ALL conversational text MUST be placed OUTSIDE the <antArtifact> block (before or after).\n"
            . "\n--- CHAT RESPONSE PROCESS EXPLANATION (for academic/thesis/report requests) ---\n"
            . "When generating academic documents (skripsi, laporan, thesis, jurnal, makalah, research papers, reports):\n"
            . "- OUTSIDE the <antArtifact> block, provide a DETAILED chat response explaining your process:\n"
            . "  • Document structure overview: Explain the overall organization and why you structured it this way\n"
            . "  • Chapter/section approach: Describe what each major section covers and your reasoning\n"
            . "  • Content methodology: Explain how you developed the content (research approach, logical flow, argumentation strategy)\n"
            . "  • Academic standards applied: Detail which academic conventions you followed (citation style, formatting rules, structural requirements)\n"
            . "  • Design decisions: Explain key choices you made (topic organization, depth of coverage, source selection)\n"
            . "  • Quality considerations: Describe how you ensured academic rigor and completeness\n"
            . "- This process explanation should be EXTENSIVE (300-500 words minimum for full documents)\n"
            . "- Write this explanation in a clear, educational tone that helps the user understand what was created and why\n"
            . "- The goal is to provide transparency into your work process, NOT just to announce completion\n"
            . "- Example structure for your chat response:\n"
            . "  'I have created a comprehensive [document type] for you. Let me explain the approach and structure:\n\n"
            . "   **Document Structure:** [Explain overall organization...]\n\n"
            . "   **Content Development:** [Explain how you developed each section...]\n\n"
            . "   **Academic Standards:** [Explain conventions followed...]\n\n"
            . "   **Key Design Decisions:** [Explain important choices...]\n\n"
            . "   The complete document is in the artifact below, ready for download as PDF/DOCX.'\n"
            . "- REMEMBER: The artifact contains ONLY the clean document. The chat response contains ALL the meta-information and process explanation.\n"
            . "\n- If the user asks you to continue a document (e.g., 'lanjut bab 2'), you MUST generate a NEW <antArtifact> block containing the continuation. DO NOT just reply with raw text.\n"
            . "- If the user asks you to merge, combine, or join multiple attached documents, ACT AS AN INTELLIGENT EDITOR: do NOT just blindly copy-paste text. Clean up the text by removing redundant page numbers, repeating headers/footers, and fixing broken sentences across page breaks. Smooth out transitions between documents.\n"
            . "- Diagrams, flowcharts, charts, org/structure figures: output INLINE raw <svg>…</svg> (mPDF renders SVG natively). Do NOT use ASCII diagrams or mermaid. Wrap each figure as <figure><svg…>…</svg><figcaption>Gambar X.Y Caption</figcaption></figure>. If the source text mentions a diagram but it's missing, creatively generate an SVG diagram to replace it!\n"
            . "- To include an image the user uploaded, reference it with markdown: ![Keterangan](attachments/<filename>) using the path from the conversation; the renderer resolves local uploads automatically.\n"
            . "- For FORMAL / ACADEMIC documents (skripsi, laporan, thesis): begin the artifact content with a YAML front-matter block to trigger the academic layout (cover page, automatic DAFTAR ISI / Table of Contents, Roman→Arabic page numbering, 4-3-3-3 cm margins, Times New Roman 12pt, justified). Use exactly this shape (omit fields you don't know):\n"
            . "---\nmode: skripsi            # skripsi | laporan | jurnal | document\njudul: <full title>\npenulis: <author name>\nnim: <student id>\nprodi: <study program>\nfakultas: <faculty>\nuniversitas: <university>\nkota: <city>\ntahun: <year>\npembimbing: <advisor>\nlogo: <path/URL logo, mis. attachments/<filename> dari file yang diupload user — KOSONGKAN/hapus baris ini bila user tidak mengirim logo>\n---\n"
            . "- LOGO COVER: Jika user mengirim/melampirkan gambar logo (kampus/instansi), SET field `logo:` ke path lampiran tersebut (mis. `attachments/logo-unri.png`) agar logo tampil di cover. Jika user HANYA menyebut nama universitas tanpa mengirim file gambar, JANGAN mengarang path logo dan JANGAN menulis field `logo` — cukup isi `universitas:` dengan namanya (nama itu otomatis tampil sebagai teks di cover). Sistem TIDAK bisa membuat logo dari nama; logo hanya muncul jika ada file gambar yang dikirim user.\n"
            . "Then structure chapters as level-1 headings (# BAB I PENDAHULUAN, # BAB II …) — each # heading starts a new page — with ## and ### for sub-sections (## 1.1 Latar Belakang). Headings are collected into the Table of Contents automatically.\n"
            . "\n--- BAGIAN AWAL / FRONT MATTER (WAJIB untuk skripsi/laporan FULL, meski prompt user singkat) ---\n"
            . "- Urutan halaman final yang dihasilkan sistem: COVER (otomatis dari front-matter) → HALAMAN PENGESAHAN → DAFTAR ISI (otomatis) → ABSTRAK → ABSTRACT → BAB I dst. Karena itu, di dalam artifact tulis heading level-1 BERURUTAN: # HALAMAN PENGESAHAN → # ABSTRAK → # ABSTRACT → # BAB I PENDAHULUAN. Sistem otomatis menempatkan COVER paling depan dan menyisipkan DAFTAR ISI tepat setelah HALAMAN PENGESAHAN.\n"
            . "- Buat HALAMAN PENGESAHAN, ABSTRAK, dan ABSTRACT secara default; JANGAN menunggu user memintanya. TIDAK perlu KATA PENGANTAR.\n"
            . "- # HALAMAN PENGESAHAN: berisi judul, nama+NIM penulis, dan tabel tanda tangan pembimbing/penguji (gunakan tabel berisi 'Pembimbing'/'NIP.' agar dirender tanpa garis).\n"
            . "- # ABSTRAK: Bahasa Indonesia, 1 paragraf ≤ 250 kata (latar belakang singkat → tujuan → metode → hasil utama), diakhiri baris '**Kata Kunci:** kata1, kata2, kata3, kata4, kata5'. WAJIB ada walau user tidak menyebut abstrak.\n"
            . "- # ABSTRACT: terjemahan bahasa Inggris dari ABSTRAK, diakhiri baris '**Keywords:** word1, word2, …'. Tulis seluruh isinya *italic* sesuai konvensi.\n"
            . "- JANGAN menulis '# DAFTAR ISI', '# DAFTAR GAMBAR', '# DAFTAR TABEL', atau '# COVER' secara manual — COVER & DAFTAR ISI dibuat OTOMATIS oleh sistem dari front-matter & heading.\n"
            . "- Agar tidak ada yang keluar dari halaman: untuk tabel lebar batasi jumlah kolom seperlunya & gunakan teks ringkas per sel; jangan menaruh URL/teks tanpa spasi yang sangat panjang.\n"
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
            . "\n--- COMPLETENESS (WAJIB) ---\n"
            . "- Saat user minta skripsi/dokumen FULL, tulis dokumen LENGKAP dalam SATU artifact — front-matter (cover), seluruh bab berurutan (BAB I sampai bab penutup), lalu DAFTAR PUSTAKA. DILARANG berhenti separuh, DILARANG menulis placeholder seperti '[lanjutan]', '...dan seterusnya', atau '(bab berikutnya menyusul)', dan DILARANG meringkas bab yang diminta ditulis penuh. Jika dokumen benar-benar terlalu panjang untuk selesai dalam satu respons, tulis sebanyak mungkin konten yang lengkap & rapi, AKHIRI artifact dengan bersih di batas bab (tutup tag </antArtifact>), lalu DI LUAR artifact beri tahu user untuk mengetik 'lanjut bab berikutnya'.\n"
            . "- For casual/simple documents, OMIT the front-matter; the renderer applies a clean general layout. Choose academic vs. casual based on what the user actually asked for.";
    }

    /**
     * Get diagram and visual generation instructions.
     */
    protected function getDiagramGenerationInstructions(): string
    {
        return "\n\n=== DIAGRAM & VISUAL GENERATION (CRITICAL PRIORITY) ===\n"
            . "When the user requests ANY visual content (diagram, flowchart, chart, grafik, bagan, struktur, arsitektur, proses, alur, sequence diagram, class diagram, ERD, state diagram, gantt, pie chart, organizational chart, mind map, etc.), you MUST follow these rules:\n\n"
            . "**OUTPUT FORMAT (NON-NEGOTIABLE):**\n"
            . "- Use Mermaid syntax inside a markdown code block\n"
            . "- The ```mermaid block must contain ONLY valid Mermaid syntax code - NO other text, NO labels, NO comments, NO headers like 'text', 'Copy code', etc.\n"
            . "- Example of CORRECT format:\n"
            . "```mermaid\n"
            . "graph TD\n"
            . "    A[Start] --> B{Decision}\n"
            . "    B -->|Yes| C[Action]\n"
            . "```\n\n"
            . "**STRICT PROHIBITIONS:**\n"
            . "- ❌ NEVER generate HTML <div>, <svg>, or any HTML tags for diagrams\n"
            . "- ❌ NEVER generate ASCII art (using characters like |, -, +, etc.)\n"
            . "- ❌ NEVER describe diagrams in plain text without code\n"
            . "- ❌ NEVER use image URLs or external diagram tools\n"
            . "- ❌ NEVER apologize or claim you cannot create diagrams\n\n"
            . "**META-COMMENTARY RULES:**\n"
            . "- ❌ DO NOT write introductory text like 'Berikut adalah diagramnya...', 'Here is the flowchart...', 'I'll create a diagram...'\n"
            . "- ✅ Start DIRECTLY with the ```mermaid code block\n"
            . "- ✅ Explanation/discussion goes AFTER the diagram block, never before\n\n"
            . "**SUPPORTED MERMAID TYPES:**\n"
            . "1. Flowchart/Process Flow: `graph TD` (top-down) or `graph LR` (left-right)\n"
            . "2. Sequence Diagram: `sequenceDiagram`\n"
            . "3. Class Diagram: `classDiagram`\n"
            . "4. Entity Relationship Diagram: `erDiagram`\n"
            . "5. Gantt Chart: `gantt`\n"
            . "6. Pie Chart: `pie title Your Title`\n"
            . "7. State Diagram: `stateDiagram-v2`\n"
            . "8. User Journey: `journey`\n"
            . "9. Git Graph: `gitGraph`\n"
            . "10. Mindmap: `mindmap`\n\n"
            . "**PLACEMENT CONTEXT:**\n"
            . "- For STANDALONE diagrams (user only asks for diagram): Output the ```mermaid block directly in your response (NOT inside <antArtifact>)\n"
            . "- For DOCUMENT-EMBEDDED diagrams (inside skripsi/laporan/proposal): Place the ```mermaid block INSIDE the <antArtifact> markdown content where the diagram should appear\n\n"
            . "**LANGUAGE MATCHING:**\n"
            . "- Use Bahasa Indonesia labels for Indonesian requests\n"
            . "- Use English labels for English requests\n"
            . "- Match the user's language in node labels, descriptions, and any text in the diagram\n\n"
            . "**QUALITY STANDARDS:**\n"
            . "- Use clear, descriptive labels for all nodes\n"
            . "- AVOID special characters in labels: NO parentheses (), colons :, ampersands &, quotes \" inside node labels\n"
            . "- If you need to show multiple items, use commas or 'dan/and': Instead of 'Input (A, B, C)' use 'Input A, B, C' or 'Input A dan B dan C'\n"
            . "- Instead of 'System: Action' use 'System - Action' or 'System Action'\n"
            . "- Instead of 'A & B' use 'A dan B' or 'A and B'\n"
            . "- Include decision points with {curly braces} for diamonds\n"
            . "- Use [square brackets] for processes/actions\n"
            . "- Use (round brackets) or ([stadium shape]) for start/end points ONLY\n"
            . "- Add edge labels with |text| or -->|text| for clarity\n"
            . "- Keep diagrams organized and readable (avoid crossing lines when possible)\n\n"
            . "**EXAMPLE 1 (Standalone - User asks: 'buat flowchart proses login'):**\n"
            . "```mermaid\n"
            . "graph TD\n"
            . "    Start([Mulai]) --> Input[Input Username & Password]\n"
            . "    Input --> Validate{Validasi Data?}\n"
            . "    Validate -->|Valid| CheckDB[Cek Database]\n"
            . "    Validate -->|Invalid| Error[Tampilkan Error]\n"
            . "    CheckDB --> Auth{Autentikasi?}\n"
            . "    Auth -->|Berhasil| Dashboard[Redirect ke Dashboard]\n"
            . "    Auth -->|Gagal| Error\n"
            . "    Error --> Input\n"
            . "    Dashboard --> End([Selesai])\n"
            . "```\n\n"
            . "Flowchart di atas menunjukkan proses login dengan validasi data dan autentikasi database.\n\n"
            . "**EXAMPLE 2 (In Document - Skripsi BAB III):**\n"
            . "Inside the <antArtifact> markdown:\n"
            . "## 3.2 Arsitektur Sistem\n\n"
            . "Sistem ini menggunakan arsitektur client-server dengan komponen sebagai berikut:\n\n"
            . "```mermaid\n"
            . "graph LR\n"
            . "    Client[Web Browser] --> Server[Laravel Backend]\n"
            . "    Server --> DB[(MySQL Database)]\n"
            . "    Server --> API[External API]\n"
            . "```\n\n"
            . "Gambar 3.1 menunjukkan arsitektur sistem yang terdiri dari...\n\n"
            . "**CRITICAL REMINDER:**\n"
            . "Every visual request MUST result in a ```mermaid block. The system will automatically render it into a beautiful diagram on the client side. Your job is ONLY to generate the Mermaid code, NOT the final rendered diagram.";
    }

    /**
     * Get response quality principles.
     */
    protected function getResponsePrinciples(): string
    {
        return "\n\nResponse principles (apply to every answer):\n"
            . "- For non-trivial questions, reason through the problem step by step before answering, then present a clear, well-structured response (use short headings or lists when they aid clarity).\n"
            . "- Accuracy first: if you are unsure or lack the information, say so plainly instead of inventing facts, and separate what you know from what you are inferring.\n"
            . "- When web search results are provided below, ground your answer in them and cite the relevant source titles or links inline.\n"
            . "- Stay consistent with the Persistent Conversation Memory below when it is present.\n"
            . "- Provide detailed, comprehensive responses by default. For simple factual questions, be direct but still thorough. For complex tasks, provide extensive explanations including your reasoning, approach, alternatives considered, and decision rationale. Err on the side of being more detailed rather than too brief. Match the user's language.";
    }

    /**
     * Build project context from project files and description.
     */
    protected function buildProjectContext(int $projectId): string
    {
        $project = \App\Models\Project::with('files')->find($projectId);
        if (!$project) {
            return '';
        }

        $context = '';

        if ($project->description) {
            $context .= "\n\nProject Context (Memory):\n" . $project->description;
        }

        if ($project->custom_instructions) {
            $context .= "\n\nProject Custom Instructions:\n" . $project->custom_instructions;
        }

        if ($project->files->count() > 0) {
            $isBinaryExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'ico', 'webp',
                'docx', 'xlsx', 'pptx', 'doc', 'xls', 'ppt',
                'zip', 'rar', '7z', 'tar', 'gz',
                'mp3', 'mp4', 'avi', 'mkv', 'mov',
                'exe', 'dll', 'so', 'bin', 'o', 'a',
                'ttf', 'otf', 'woff', 'woff2', 'eot',
            ];

            $context .= "\n\nProject Knowledge Files:\n";
            foreach ($project->files as $file) {
                if (!\Illuminate\Support\Facades\Storage::exists($file->file_path)) {
                    continue;
                }

                $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                if (in_array($ext, $isBinaryExtensions, true)) {
                    $context .= "\n--- Document: {$file->file_name} ---\n[Skipped: binary file type not supported for inline context]\n";
                    continue;
                }

                $content = \Illuminate\Support\Facades\Storage::get($file->file_path);

                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $content = mb_scrub($content);

                $truncated = mb_substr($content, 0, 200000);
                $note = mb_strlen($content) > 200000
                    ? "\n[... file truncated at 200KB; total " . number_format(strlen($content)) . " bytes]"
                    : '';
                $context .= "\n--- Document: {$file->file_name} ---\n" . $truncated . $note . "\n";
            }
        }

        return $context;
    }

    /**
     * Build artifact-aware context (chapter references for academic documents).
     */
    protected function buildArtifactContext(Conversation $conversation, string $userText): string
    {
        $artifact = MessageArtifact::query()
            ->whereHas('message', fn($q) => $q->where('conversation_id', $conversation->id))
            ->whereIn('language', ['markdown', 'md'])
            ->latest('id')
            ->first();

        if (!$artifact) {
            return '';
        }

        $context = "\n\n--- ACTIVE DOCUMENT CONTEXT ---\n"
            . "The user is currently working on this document (artifact id #{$artifact->id}, title: \"{$artifact->title}\"). "
            . "When the user asks to revise, continue, or expand a chapter, treat THIS document as the source of truth.\n";

        $outline = is_array($artifact->outline_json) ? $artifact->outline_json : MessageArtifact::extractOutline($artifact->content);
        if (!empty($outline)) {
            $context .= "\nDocument outline (heading tree):\n";
            foreach ($outline as $h) {
                $indent = str_repeat('  ', max(0, ($h['level'] ?? 1) - 1));
                $context .= $indent . '- ' . trim((string)($h['text'] ?? '')) . "\n";
            }
        }

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
     * Build a digest of middle messages that fell out of the sliding window.
     */
    protected function buildMiddleDigest(array $messages): string
    {
        if (empty($messages)) {
            return '';
        }

        $out = [];
        foreach ($messages as $m) {
            $role = ($m['role'] ?? '') === 'user' ? 'User' : 'Assistant';
            $text = trim((string)($m['content'] ?? ''));
            if ($text === '') {
                continue;
            }

            $text = preg_replace('/<antArtifact[^>]*>.*?<\/antArtifact>/is', '[artifact]', $text) ?? $text;
            $first = preg_split('/(?<=[.!?])\s/', $text, 2)[0] ?? $text;
            if (mb_strlen($first) > 240) {
                $first = mb_substr($first, 0, 240) . '…';
            }

            $out[] = $role . ': ' . $first;

            if (count($out) >= 80) {
                $out[] = '... (' . (count($messages) - count($out) + 1) . ' more earlier messages omitted)';
                break;
            }
        }

        return implode("\n", $out);
    }

    /**
     * Detect chapter reference like "BAB 2" or "Bab III" from user text.
     */
    protected function detectBabReference(string $text): ?int
    {
        if (!preg_match('/\bbab\s+([ivxlcdm]+|\d{1,2})\b/i', $text, $m)) {
            return null;
        }

        $token = strtolower($m[1]);
        if (ctype_digit($token)) {
            $n = (int)$token;
            return ($n >= 1 && $n <= 99) ? $n : null;
        }

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
     * Extract chapter "BAB N" from markdown document.
     */
    protected function extractBab(?string $markdown, int $bab): ?string
    {
        if (empty($markdown)) {
            return null;
        }

        $roman = $this->intToRoman($bab);
        $pattern = '/^#\s+BAB\s+(?:' . $bab . '|' . preg_quote($roman, '/') . ')\b.*$/mi';

        if (!preg_match($pattern, $markdown, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $m[0][1];
        $rest = substr($markdown, $start + strlen($m[0][0]));

        if (preg_match('/\n#\s+/', $rest, $nextM, PREG_OFFSET_CAPTURE)) {
            $end = $start + strlen($m[0][0]) + $nextM[0][1];
            $chapter = substr($markdown, $start, $end - $start);
        } else {
            $chapter = substr($markdown, $start);
        }

        $chapter = trim((string)$chapter);
        if (mb_strlen($chapter) > 6000) {
            $chapter = mb_substr($chapter, 0, 6000) . "\n\n[... chapter truncated]";
        }

        return $chapter;
    }

    /**
     * Convert integer to Roman numeral.
     */
    protected function intToRoman(int $n): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'
        ];
        $out = '';
        foreach ($map as $v => $r) {
            while ($n >= $v) {
                $out .= $r;
                $n -= $v;
            }
        }
        return $out;
    }
}
