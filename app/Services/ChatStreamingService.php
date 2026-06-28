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
        bool $webSearch = false
    ): \Generator {
        // Prevent PHP from killing the streaming process during long generations
        set_time_limit(0);

        if (empty($messages) || end($messages)['role'] !== 'user') {
            yield ['type' => 'error', 'data' => 'Last message must be from user'];
            return;
        }

        // Build the complete system prompt with all context
        $systemPrompt = $this->buildSystemPrompt($conversation, $messages, $webSearch);

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
        bool $webSearch
    ): string {
        $baseSystemPrompt = $this->getBaseArtifactInstructions();
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
        if ($webSearch && $lastUserText !== '') {
            $searchService = new \App\Services\WebSearchService();
            $results = $searchService->search($lastUserText, 5);
            if (!empty($results)) {
                $baseSystemPrompt .= $searchService->formatForPrompt($results);
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
        return "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, files, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a document, report, PDF, DOCX, or any text-based file, you MUST generate a well-structured Markdown document (language=\"markdown\") inside the <antArtifact> tag. DO NOT EVER generate raw file byte streams or PostScript code. The system will automatically convert your Markdown into downloadable files for the user. Focus only on writing excellent text content inside the <antArtifact> tag. Provide brief explanation outside the tag if needed.";
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
            . "- WARNING: The content inside the <antArtifact> block is exported directly to the final PDF/DOCX. DO NOT include any conversational text, meta-commentary, or formatting explanations (e.g., 'Berikut adalah laporannya...' or 'Penjelasan Format:') INSIDE the artifact. ALL conversational text MUST be placed OUTSIDE the <antArtifact> block.\n"
            . "- If the user asks you to continue a document (e.g., 'lanjut bab 2'), you MUST generate a NEW <antArtifact> block containing the continuation. DO NOT just reply with raw text.\n"
            . "- Diagrams, flowcharts, charts: output INLINE raw <svg>…</svg> (mPDF renders SVG natively). Wrap each figure as <figure><svg…>…</svg><figcaption>Gambar X.Y Caption text</figcaption></figure>.\n"
            . "- For FORMAL / ACADEMIC documents (skripsi, laporan, thesis): begin the artifact content with a YAML front-matter block to trigger the academic layout.\n"
            . "- CAPTION & NUMBERING RULES: figure captions below (Gambar X.Y), table captions above (Tabel X.Y), numbered by chapter.\n"
            . "- For casual/simple documents, OMIT the front-matter; the renderer applies a clean general layout.";
    }

    /**
     * Get response quality principles.
     */
    protected function getResponsePrinciples(): string
    {
        return "\n\nResponse principles (apply to every answer):\n"
            . "- For non-trivial questions, reason through the problem step by step before answering, then present a clear, well-structured response.\n"
            . "- Accuracy first: if you are unsure or lack the information, say so plainly instead of inventing facts.\n"
            . "- When web search results are provided below, ground your answer in them and cite the relevant source titles or links inline.\n"
            . "- Stay consistent with the Persistent Conversation Memory below when it is present.\n"
            . "- Keep answers focused — concise for simple asks, thorough for complex ones — and match the user's language.";
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
            $context .= "\n\nProject Knowledge Files:\n";
            foreach ($project->files as $file) {
                if (\Illuminate\Support\Facades\Storage::exists($file->file_path)) {
                    $content = \Illuminate\Support\Facades\Storage::get($file->file_path);
                    $truncated = substr($content, 0, 200000);
                    $note = strlen($content) > 200000
                        ? "\n[... file truncated at 200KB; total " . number_format(strlen($content)) . " bytes]"
                        : '';
                    $context .= "\n--- Document: {$file->file_name} ---\n" . $truncated . $note . "\n";
                }
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
                $context .= "\nThe user referenced **BAB {$requestedBab}**. Verbatim contents:\n\n";
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

        static $rom = ['i' => 1, 'v' => 5, 'x' => 10, 'l' => 50, 'c' => 100];
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
