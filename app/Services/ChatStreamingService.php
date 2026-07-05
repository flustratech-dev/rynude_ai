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
        bool $researchMode = false,
        ?int $parentMessageId = null,
        bool $thinking = false,
        ?string $precomputed = null
    ): \Generator {
        // Prevent PHP from killing the streaming process during long generations
        set_time_limit(0);

        if (empty($messages) || end($messages)['role'] !== 'user') {
            yield ['type' => 'error', 'data' => 'Last message must be from user'];
            return;
        }

        $searchBlock = '';
        $citations = [];

        // Clear any stale stop flag before we begin streaming
        $stopKey = 'chat_stop_' . $conversation->id;
        Cache::forget($stopKey);

        if ($precomputed !== null) {
            // Precomputed path: the answer was already produced by the browser
            // extension (a real claude.ai tab, bypassing Cloudflare). Skip web
            // research, system-prompt building and the AI provider entirely —
            // just stream the text out and let the shared save/artifact/title/
            // done logic below run exactly as for a normal generation.
            $extractedThinking = '';
            if (preg_match('/<(?:thinking|sim_thinking|think)>(.*?)<\/(?:thinking|sim_thinking|think)>/is', $precomputed, $matches)) {
                $extractedThinking = trim($matches[1]);
                $precomputed = trim(preg_replace('/<(?:thinking|sim_thinking|think)>.*?<\/(?:thinking|sim_thinking|think)>/is', '', $precomputed));
            }
            $thinking = false;
            $simulateThinking = false;
            $stream = (function () use ($precomputed, $extractedThinking) {
                if ($extractedThinking !== '') {
                    foreach (str_split($extractedThinking, 400) as $piece) {
                        yield ['type' => 'thinking', 'text' => $piece];
                    }
                }
                // Chunk so a very long answer still yields cooperatively and the
                // client's typewriter has bites to reveal.
                foreach (str_split($precomputed, 400) as $piece) {
                    yield $piece;
                }
            })();
        } else {
            // Web research: the model plans the queries, pasted URLs are fetched,
            // and the numbered sources double as citations on the saved reply.
            if ($webSearch || $researchMode) {
                [$searchBlock, $citations] = $this->runWebResearch($messages, $model, $researchMode);
            }

            // Every model gets a thinking stream when the toggle is on: models
            // with native reasoning (direct Anthropic, Ollama reasoners such as
            // gemma4/deepseek-r1) stream their own deltas, everything else —
            // including small local models and kr/* proxy models whose upstream
            // reasoning never crosses 9Router — falls back to prompted
            // <sim_thinking> tags that we strip back out of the answer below.
            // Trade-off on CPU-only local models: the extra reasoning pass makes
            // the final answer arrive later.
            $simulateThinking = $thinking && !$this->modelHasNativeReasoning($model);

            // Build the complete system prompt with all context
            $systemPrompt = $this->buildSystemPrompt($conversation, $messages, $webSearch, $researchMode, $searchBlock, $simulateThinking);

            // Per-model adjustments (smaller/proxy models get stricter format rules)
            $systemPrompt = (new \App\Services\AI\Normalization\ModelAdapterRegistry())
                ->for($model)
                ->adaptSystemPrompt($systemPrompt);

            // Apply sliding window context strategy (token budget per model)
            $messagesForAi = $this->applySlidingWindow($messages, $systemPrompt, $model);

            // Stream from AI service
            $stream = $this->aiService->streamResponse($messagesForAi, $model);
        }

        $fullResponse = '';
        $thinkingText = '';
        $stopped = false;
        $truncated = false;
        // The tag scanner runs whenever thinking mode is on — not just when we
        // prompted for it — so models that natively emit inline <think>/<thinking>
        // blocks in their content (DeepSeek-style via HF/proxies) also get their
        // reasoning routed to the thinking panel instead of leaking raw tags.
        $simState = ['phase' => $thinking ? 'detect' : 'off', 'buf' => '', 'close' => null];

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
                    $thinkingText .= $chunk['text'];
                    yield ['type' => 'thinking', 'data' => $chunk['text']];
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'truncated') {
                    // Answer hit the provider's max_tokens ceiling — reported
                    // via the done event so the UI can offer "Continue".
                    $truncated = true;
                }
                continue;
            }

            // Route prompted <sim_thinking> blocks to the thinking stream and
            // keep them out of the saved answer. With the feature off this is
            // a plain passthrough.
            foreach ($this->splitSimThinking($chunk, $simState) as $piece) {
                if ($piece['type'] === 'thinking') {
                    $thinkingText .= $piece['text'];
                    yield ['type' => 'thinking', 'data' => $piece['text']];
                } else {
                    $fullResponse .= $piece['text'];
                    yield ['type' => 'content', 'data' => $piece['text']];
                }
            }
        }

        // Flush anything the sim-thinking scanner was still holding back
        foreach ($this->flushSimThinking($simState) as $piece) {
            if ($piece['type'] === 'thinking') {
                $thinkingText .= $piece['text'];
                yield ['type' => 'thinking', 'data' => $piece['text']];
            } else {
                $fullResponse .= $piece['text'];
                yield ['type' => 'content', 'data' => $piece['text']];
            }
        }

        // If the user stopped an empty generation, store a small placeholder
        if ($stopped && trim($fullResponse) === '') {
            $fullResponse = '_Generation stopped._';
        }

        // Parse artifacts if present (a reply may carry several)
        $parsedArtifacts = $this->parseArtifacts($fullResponse);

        // Save assistant message to database
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $parsedArtifacts ? $parsedArtifacts['cleanResponse'] : $fullResponse,
            'model' => $model,
            'parent_id' => $parentMessageId,
            'citations' => !empty($citations) ? $citations : null,
            'thinking' => trim($thinkingText) !== '' ? $thinkingText : null,
        ]);

        // When using "connect akun" (web browser extension mode, where precomputed !== null),
        // the response is generated client-side by the extension (claude.ai tab) and bypasses
        // the server-side AI provider where TokenUsage::record() is normally called.
        // We record estimated tokens here so the Billing dashboard reflects web account usage.
        if ($precomputed !== null && Auth::check()) {
            $outTokens = max(1, intdiv(strlen($fullResponse), 4));
            $threadMessages = Message::where('conversation_id', $conversation->id)
                ->where('id', '<', $assistantMessage->id)
                ->get(['content']);
            $inChars = 0;
            foreach ($threadMessages as $m) {
                $inChars += strlen((string) $m->content);
            }
            $inTokens = max(1, intdiv($inChars, 4));

            $providerLabel = str_starts_with($model, 'claude') ? 'anthropic' : (str_starts_with($model, 'gpt') ? 'openai' : (str_starts_with($model, 'gemini') ? 'google' : 'web'));
            [$rtkSaved, $rtkOriginal] = \App\Services\AI\RtkTracker::flushAndGet();

            \App\Models\TokenUsage::record(
                Auth::id(),
                $model,
                $providerLabel,
                $inTokens,
                $outTokens,
                $rtkSaved,
                $rtkOriginal
            );
        }

        // Create artifacts and link to the actual message. Reusing an earlier
        // identifier makes it a new VERSION; command="update" additionally
        // applies find/replace pairs onto the previous version instead of
        // requiring a full rewrite.
        $artifacts = [];
        foreach (($parsedArtifacts['items'] ?? []) as $artifactData) {
            $previous = MessageArtifact::where('identifier', $artifactData['identifier'])
                ->whereHas('message', fn ($q) => $q->where('conversation_id', $conversation->id))
                ->orderByDesc('id')
                ->first();

            $content = $artifactData['content'];
            $version = 1;
            if ($previous) {
                $version = (int) ($previous->version ?? 1) + 1;
                if (($artifactData['command'] ?? 'create') === 'update') {
                    $content = $this->applyArtifactUpdate($previous->content, $artifactData['content']);
                }
            }

            $artifacts[] = MessageArtifact::create([
                'message_id' => $assistantMessage->id,
                'identifier' => $artifactData['identifier'],
                'type' => $artifactData['type'],
                'language' => $artifactData['language'],
                'title' => $artifactData['title'] !== 'Document' || !$previous ? $artifactData['title'] : $previous->title,
                'content' => $content,
                'user_id' => Auth::id(),
                'outline_json' => MessageArtifact::extractOutline($content),
                'version' => $version,
            ]);
        }

        // Yield metadata for every artifact that was created
        foreach ($artifacts as $artifact) {
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
        $artifact = $artifacts[0] ?? null;

        // Web sources used for this reply (rendered as chips under the answer)
        if (!empty($citations)) {
            yield ['type' => 'citations', 'data' => $citations];
        }

        // Signal completion before dispatching housekeeping jobs: on a sync
        // queue those dispatches run inline (the title job makes an AI call),
        // and the client must not keep its loading state up while they run
        yield [
            'type' => 'done',
            'data' => [
                'message_id' => $assistantMessage->id,
                'artifact_id' => $artifact ? $artifact->id : null,
                'stopped' => $stopped,
                'truncated' => $truncated,
            ],
        ];

        // Housekeeping jobs make their own AI calls. They MUST NOT run inline
        // (QUEUE_CONNECTION defaults to sync): the single-worker `artisan serve`
        // keeps this SSE connection occupied until they finish, so the next
        // request — silent thread reload or the user's next message — queues
        // behind a second AI call and the chat feels frozen. Pin them to the
        // database queue, which the launcher's `queue:work` process drains.

        // Refresh durable conversation memory if needed
        if ($this->memoryService->shouldRefresh($conversation, count($messages) + 1)) {
            \App\Jobs\RefreshConversationMemory::dispatch($conversation->id, $model)
                ->onConnection('database');
        }

        // Refresh the cross-conversation user profile at most every 6 hours
        $user = Auth::user();
        if ($user && (!$user->ai_memory_synced_at || $user->ai_memory_synced_at->lt(now()->subHours(6)))) {
            \App\Jobs\RefreshUserMemory::dispatch($user->id, $model)
                ->onConnection('database');
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
                )->onConnection('database');
            }
        }
    }

    /**
     * Build the complete system prompt with all context layers.
     */
    protected function buildSystemPrompt(
        Conversation $conversation,
        array $messages,
        bool $webSearch,
        bool $researchMode = false,
        string $searchBlock = '',
        bool $simulateThinking = false
    ): string {
        $baseSystemPrompt = $this->getBaseArtifactInstructions();
        $baseSystemPrompt .= $this->getDiagramGenerationInstructions();
        $baseSystemPrompt .= $this->getDocumentQualityInstructions();
        $baseSystemPrompt .= $this->getResponsePrinciples();

        // Custom instructions from user settings
        if (Auth::check() && !empty(Auth::user()->custom_instructions)) {
            $baseSystemPrompt .= "\n\nUser Custom Instructions:\n" . Auth::user()->custom_instructions;
        }

        // Cross-conversation user memory (durable profile distilled from
        // earlier chats by RefreshUserMemory)
        if (Auth::check() && !empty(trim((string) Auth::user()->ai_memory))) {
            $baseSystemPrompt .= "\n\n--- USER MEMORY (facts about this user from previous conversations) ---\n"
                . trim((string) Auth::user()->ai_memory)
                . "\n--- END USER MEMORY ---";
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

        // Response style (per-conversation, like Claude's Styles)
        $styleMap = [
            'concise' => 'RESPONSE STYLE: Be concise and direct. Prefer short sentences and tight lists; skip preamble, recaps, and filler. Only elaborate when the user explicitly asks.',
            'explanatory' => 'RESPONSE STYLE: Be explanatory and educational. Walk through the reasoning step by step, define terms, and add short examples or analogies so a newcomer can follow.',
            'formal' => 'RESPONSE STYLE: Use formal, professional language (bahasa baku when responding in Indonesian). No slang, no emoji, structured paragraphs suitable for academic or business contexts.',
        ];
        if (!empty($conversation->style) && isset($styleMap[$conversation->style])) {
            $baseSystemPrompt .= "\n\n" . $styleMap[$conversation->style];
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

        // Web research context (planned queries + fetched URLs, built by
        // runWebResearch in stream() so the sources double as citations)
        if ($searchBlock !== '') {
            $baseSystemPrompt .= $searchBlock;
            if ($researchMode) {
                $baseSystemPrompt .= "\n\nRESEARCH MODE INSTRUCTIONS:\nYou are in deep research mode. Formulate your answer by critically analyzing the sources. Highlight discrepancies, cite sources inline as [n], and write in a detailed, academic or highly authoritative tone.";
            }
        }

        // Prompted reasoning for models without a native thinking mode
        if ($simulateThinking) {
            $baseSystemPrompt .= "\n\nEXTENDED THINKING MODE: Begin your response with your step-by-step reasoning wrapped EXACTLY in <sim_thinking> ... </sim_thinking> tags, then write the final answer AFTER the closing tag. The reasoning is hidden from the final answer, so never reference it there.";
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
     * Gather web sources for the user's question: URLs they pasted are fetched
     * directly, then the model plans the search queries (multi-query, not just
     * the raw prompt). Returns [$promptBlock, $citations].
     */
    protected function runWebResearch(array $messages, string $model, bool $researchMode): array
    {
        $lastUserText = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $lastUserText = (string) $messages[$i]['content'];
                break;
            }
        }
        if (trim($lastUserText) === '') {
            return ['', []];
        }

        $searchService = new \App\Services\WebSearchService();
        $sources = [];

        // URLs pasted by the user are first-class sources (max 2, full text).
        preg_match_all('#https?://[^\s<>"\')\]]+#i', $lastUserText, $m);
        foreach (array_slice(array_unique($m[0] ?? []), 0, 2) as $url) {
            $text = $searchService->fetchUrl($url, 12000);
            if ($text !== '') {
                $sources[] = [
                    'title' => parse_url($url, PHP_URL_HOST) ?: $url,
                    'url' => $url,
                    'snippet' => $text,
                    'full' => true,
                ];
            }
        }

        $maxSources = $researchMode ? 10 : 6;
        $seen = array_column($sources, 'url');
        foreach ($this->planSearchQueries($lastUserText, $model, $researchMode ? 3 : 2) as $query) {
            foreach ($searchService->search($query, $researchMode ? 4 : 3) as $r) {
                if (in_array($r['url'], $seen, true)) {
                    continue;
                }
                $seen[] = $r['url'];
                $sources[] = $r + ['full' => false];
                if (count($sources) >= $maxSources) {
                    break 2;
                }
            }
        }

        if (empty($sources)) {
            return ['', []];
        }

        $block = "\n\n=== WEB SOURCES ===\nUse these sources for up-to-date facts and cite them inline as [n] (e.g. \"... menurut laporan terbaru [2].\"). Only cite numbers that exist below.\n";
        $citations = [];
        foreach ($sources as $i => $s) {
            $n = $i + 1;
            $body = $s['full'] ? \Illuminate\Support\Str::limit($s['snippet'], 6000) : $s['snippet'];
            $block .= "\n[{$n}] {$s['title']}\nURL: {$s['url']}\n{$body}\n";
            $citations[] = ['n' => $n, 'title' => $s['title'], 'url' => $s['url']];
        }
        $block .= "=== END WEB SOURCES ===\n";

        return [$block, $citations];
    }

    /**
     * Ask the model for effective search queries instead of searching the raw
     * prompt verbatim. Falls back to the raw prompt on any failure; an explicit
     * NO_SEARCH verdict skips searching entirely.
     */
    protected function planSearchQueries(string $userText, string $model, int $max): array
    {
        $fallback = [\Illuminate\Support\Str::limit($userText, 200, '')];

        try {
            $prompt = "Buat maksimal {$max} query pencarian web yang paling efektif untuk menjawab pertanyaan berikut. "
                . "Balas HANYA daftar query, satu per baris, tanpa nomor dan tanpa penjelasan. "
                . "Jika pencarian web tidak diperlukan untuk menjawab, balas persis: NO_SEARCH\n\n"
                . "Pertanyaan: " . \Illuminate\Support\Str::limit($userText, 1500);

            $out = '';
            foreach ($this->aiService->streamResponse([['role' => 'user', 'content' => $prompt]], $model) as $chunk) {
                if (is_string($chunk)) {
                    $out .= $chunk;
                }
                if (strlen($out) > 600) {
                    break;
                }
            }

            $out = trim($out);
            if ($out === '' || str_starts_with($out, '[Error')) {
                return $fallback;
            }
            if (stripos($out, 'NO_SEARCH') !== false) {
                return [];
            }

            $queries = array_values(array_filter(
                array_map(fn ($l) => trim(trim($l), "-*0123456789. \t\"'"), explode("\n", $out)),
                fn ($l) => strlen($l) > 3
            ));

            return array_slice($queries, 0, $max) ?: $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Does the model reason natively (so prompted sim-thinking is redundant)?
     * "Natively" here means reasoning deltas actually reach us as structured
     * thinking chunks — not just that the underlying model can reason. Only
     * two paths deliver those: AnthropicProvider (`thinking_delta`, non-haiku
     * direct models) and OpenAI-compatible endpoints that stream
     * `delta.reasoning(_content)` (Ollama reasoners, DeepSeek-style APIs).
     * OpenAI (o1/o3/gpt-5), Google and Mistral never surface reasoning in
     * their streams, so those get the prompted fallback despite being
     * "reasoning models" upstream.
     */
    protected function modelHasNativeReasoning(string $model): bool
    {
        // 9Router (kr/*) never forwards reasoning deltas, even for models
        // that reason natively upstream — treat them all as non-native so
        // the prompted fallback kicks in (same approach as the ReAct
        // fallback for native tools).
        if (str_starts_with($model, 'kr/')) {
            return false;
        }

        // Direct Anthropic haiku models have no extended thinking mode
        // (mirrors AnthropicProvider::modelSupportsThinking).
        if (str_contains($model, 'haiku')) {
            return false;
        }

        return (bool) preg_match('/claude|fable|deepseek|r1|qwen3|qwq|gemma-?4|gpt-oss/i', $model);
    }

    /**
     * Incremental scanner that lifts a leading reasoning block out of the
     * content stream. Recognizes our prompted <sim_thinking> tag as well as
     * the <think>/<thinking> tags that DeepSeek-style models emit natively
     * inline. Tags may arrive split across chunks, so partial-tag tails are
     * held back until resolvable. Phase 'off' = plain passthrough (zero
     * overhead when the feature is disabled).
     *
     * @return array<int, array{type: 'content'|'thinking', text: string}>
     */
    protected function splitSimThinking(string $chunk, array &$state): array
    {
        if ($state['phase'] === 'off') {
            return $chunk === '' ? [] : [['type' => 'content', 'text' => $chunk]];
        }

        // Longer tags first so <thinking> wins over its prefix <think>.
        $tags = [
            '<sim_thinking>' => '</sim_thinking>',
            '<thinking>' => '</thinking>',
            '<think>' => '</think>',
        ];

        $out = [];
        $state['buf'] .= $chunk;

        while ($state['buf'] !== '') {
            if ($state['phase'] === 'detect') {
                $lt = ltrim($state['buf']);
                if ($lt === '') {
                    break; // whitespace only — keep waiting
                }
                $opened = false;
                foreach ($tags as $open => $close) {
                    if (str_starts_with($lt, $open)) {
                        $state['buf'] = substr($lt, strlen($open));
                        $state['close'] = $close;
                        $state['phase'] = 'inside';
                        $opened = true;
                        break;
                    }
                }
                if ($opened) {
                    continue;
                }
                $couldForm = false;
                foreach ($tags as $open => $close) {
                    if (str_starts_with($open, $lt)) {
                        $couldForm = true;
                        break;
                    }
                }
                if ($couldForm) {
                    break; // could still become an opening tag — wait
                }
                // No reasoning block: answer starts normally.
                $out[] = ['type' => 'content', 'text' => $state['buf']];
                $state['buf'] = '';
                $state['phase'] = 'off';
                break;
            }

            // phase === 'inside'
            $close = $state['close'] ?? '</sim_thinking>';
            $pos = strpos($state['buf'], $close);
            if ($pos !== false) {
                if ($pos > 0) {
                    $out[] = ['type' => 'thinking', 'text' => substr($state['buf'], 0, $pos)];
                }
                $rest = ltrim(substr($state['buf'], $pos + strlen($close)), "\r\n");
                $state['buf'] = '';
                $state['phase'] = 'off';
                if ($rest !== '') {
                    $out[] = ['type' => 'content', 'text' => $rest];
                }
                break;
            }
            // Hold back a possible partial closing tag at the tail.
            $safe = strlen($state['buf']) - (strlen($close) - 1);
            if ($safe > 0) {
                $out[] = ['type' => 'thinking', 'text' => substr($state['buf'], 0, $safe)];
                $state['buf'] = substr($state['buf'], $safe);
            }
            break;
        }

        return $out;
    }

    /**
     * Emit whatever the sim-thinking scanner still holds when the stream ends.
     */
    protected function flushSimThinking(array &$state): array
    {
        if ($state['buf'] === '') {
            return [];
        }
        $piece = [[
            'type' => $state['phase'] === 'inside' ? 'thinking' : 'content',
            'text' => $state['buf'],
        ]];
        $state['buf'] = '';
        $state['phase'] = 'off';

        return $piece;
    }

    /**
     * Apply sliding window strategy to message history.
     * Keep first 2 messages + as many recent messages as the model's token
     * budget allows (was: a fixed count of 200, which overflowed small-context
     * models on long messages and wasted budget on short ones); the middle is
     * digested into the system prompt.
     */
    protected function applySlidingWindow(array $messages, string $systemPrompt, string $model = ''): array
    {
        $historySize = 200;
        $keepFirst = 2;
        $keepRecentMin = 4;

        // Rough estimate: 4 chars ≈ 1 token, plus per-message overhead.
        $estimate = fn (array $m): int => intdiv(strlen((string) ($m['content'] ?? '')), 4) + 8;

        // Reserve 40% of the context window for the reply + provider overhead.
        $maxCtx = 128000;
        try {
            if ($model !== '') {
                $maxCtx = (new \App\Services\AI\Normalization\ModelAdapterRegistry())
                    ->for($model)->capabilities()->maxContextTokens ?: 128000;
            }
        } catch (\Throwable $e) {
            // Unknown model — keep the default.
        }
        $budget = max(4000, (int) ($maxCtx * 0.6) - intdiv(strlen($systemPrompt), 4));

        $userMessages = array_values(array_filter($messages, fn($m) => $m['role'] !== 'system'));
        $totalMsgs = count($userMessages);

        // Walk backwards collecting recent messages until the budget runs out
        // (always keep at least the last few so the thread stays coherent).
        $firstMessages = array_slice($userMessages, 0, $keepFirst);
        $used = array_sum(array_map($estimate, $firstMessages));
        $recentCount = 0;
        for ($i = $totalMsgs - 1; $i >= $keepFirst; $i--) {
            $cost = $estimate($userMessages[$i]);
            if ($recentCount >= $keepRecentMin && ($used + $cost) > $budget) {
                break;
            }
            if ($recentCount >= $historySize - $keepFirst) {
                break;
            }
            $used += $cost;
            $recentCount++;
        }

        if ($totalMsgs > $keepFirst + $recentCount) {
            $recentMessages = $recentCount > 0 ? array_slice($userMessages, -$recentCount) : [];
            $middleMessages = array_slice($userMessages, $keepFirst, $totalMsgs - $keepFirst - $recentCount);

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
     * Parse ALL <antArtifact> blocks from the response. Earlier code kept only
     * the first block while stripping every block from the visible text — any
     * additional artifact was silently lost.
     *
     * @return array|null ['items' => array<int, array>, 'cleanResponse' => string] or null
     */
    protected function parseArtifacts(string $fullResponse): ?array
    {
        $pattern = '/<(?:antA|a)rtifact\b([^>]*)>([\s\S]*?)(?:<\/(?:antA|a)rtifact>|$)/i';

        if (!preg_match_all($pattern, $fullResponse, $matches, PREG_SET_ORDER)) {
            return null;
        }

        // Remove every artifact block from the visible text
        $cleanResponse = trim((string) preg_replace($pattern, '', $fullResponse));

        $items = [];
        foreach ($matches as $match) {
            $attrString = $match[1];
            $content = trim($match[2]);

            [$content, $leaked] = $this->stripConversationalLeaks($content);

            // Leaked conversational text belongs in the chat, not the document
            if ($leaked !== '') {
                $cleanResponse = trim($cleanResponse . "\n\n" . $leaked);
            }

            $identifier = preg_match('/identifier="([^"]+)"/i', $attrString, $m) ? $m[1] : 'artifact-' . uniqid();
            $type = preg_match('/type="([^"]+)"/i', $attrString, $m) ? $m[1] : 'application/vnd.ant.code';
            $language = preg_match('/language="([^"]*)"/i', $attrString, $m) ? $m[1] : 'markdown';
            $title = preg_match('/title="([^"]+)"/i', $attrString, $m) ? $m[1] : 'Document';
            $command = preg_match('/command="([^"]+)"/i', $attrString, $m) ? strtolower($m[1]) : 'create';

            $items[] = [
                'identifier' => $identifier,
                'type' => str_contains($type, 'code') ? 'code' : 'text',
                'language' => $language ?: 'text',
                'title' => $title,
                'content' => $content,
                'command' => $command,
            ];
        }

        return ['items' => $items, 'cleanResponse' => $cleanResponse];
    }

    /**
     * Apply a targeted artifact update: the model sends find/replace pairs
     * (<antOldContent>…</antOldContent><antNewContent>…</antNewContent>)
     * instead of rewriting the whole document. Each pair replaces the FIRST
     * occurrence. If no pairs are found, the payload is a full rewrite.
     */
    protected function applyArtifactUpdate(string $baseContent, string $updatePayload): string
    {
        $pairPattern = '/<antOldContent>([\s\S]*?)<\/antOldContent>\s*<antNewContent>([\s\S]*?)<\/antNewContent>/i';

        if (!preg_match_all($pairPattern, $updatePayload, $pairs, PREG_SET_ORDER)) {
            return $updatePayload;
        }

        $patched = $baseContent;
        foreach ($pairs as $pair) {
            $old = trim($pair[1], "\r\n");
            $new = trim($pair[2], "\r\n");
            if ($old === '') {
                continue;
            }
            $pos = strpos($patched, $old);
            if ($pos !== false) {
                $patched = substr_replace($patched, $new, $pos, strlen($old));
            }
        }

        return $patched;
    }

    /**
     * Strip conversational text the model leaked inside the artifact — greetings
     * before the document and closing remarks after it (e.g. "silakan beri tahu
     * saya", "semoga bermanfaat"). Cues are deliberately conservative so real
     * document sentences (surat penutup, kata pengantar) are never removed.
     *
     * @return array{0: string, 1: string} [cleanContent, leakedText] — leakedText is moved back to the chat response
     */
    protected function stripConversationalLeaks(string $content): array
    {
        $trailingCue = '/dokumen (di ?atas|ini|tersebut) (disusun|telah|sudah|siap)'
            . '|beri ?tahu saya|jangan ragu|semoga (bermanfaat|membantu)'
            . '|silakan (unduh|download|beri|hubungi|ketik|klik)'
            . '|anda dapat (mengunduh|men-?download|mengedit|melanjutkan|mengetik)'
            . '|siap (diunduh|di-?download|dikonversi)|ketik [\'"]?lanjut'
            . '|let me know|feel free|hope this (helps|is helpful)|\bartifact\b/iu';
        $leadingCue = '/^(tentu|baik,|oke|berikut|saya (akan|telah|sudah)'
            . '|here (is|\'s)|sure|certainly|i(\'ve| have|\'ll| will))\b/iu';

        // Front-matter, headings, tables, HTML, lists, hrs, citations = document content
        $isDocLike = fn (string $p): bool => preg_match('/^(#|\||<|>|-{3,}|\d+\.|[-*+] |\[\d+\])/', $p) === 1;

        $leaked = [];

        // A "Sistematika Penulisan" section is a per-chapter rundown that belongs in
        // the chat explanation, never in the rendered document — cut the whole section
        // (its heading up to the next heading) and hand it back to the chat response.
        $sistematika = '/^#{1,3}[^\n]*sistematika penulisan[^\n]*\n[\s\S]*?(?=^#{1,3} |\z)/im';
        if (preg_match_all($sistematika, $content, $mm)) {
            foreach ($mm[0] as $section) {
                $leaked[] = trim($section);
            }
            $content = preg_replace($sistematika, '', $content) ?? $content;
        }

        $paragraphs = preg_split('/\n{2,}/', $content) ?: [$content];

        while ($paragraphs) {
            $last = trim((string) end($paragraphs));
            if ($last === '') {
                array_pop($paragraphs);
                continue;
            }
            if (!$isDocLike($last) && preg_match($trailingCue, $last)) {
                array_unshift($leaked, $last);
                array_pop($paragraphs);
                // Drop a horizontal rule the model left dangling before its remark
                if ($paragraphs && preg_match('/^-{3,}$/', trim((string) end($paragraphs)))) {
                    array_pop($paragraphs);
                }
                continue;
            }
            // Same case but the hr is glued to the last document paragraph with a single newline
            if ($leaked && preg_match('/\n-{3,}\s*$/', rtrim((string) end($paragraphs)))) {
                $trimmed = preg_replace('/\n-{3,}\s*$/', '', rtrim((string) end($paragraphs)));
                if ($trimmed !== null) {
                    $paragraphs[count($paragraphs) - 1] = $trimmed;
                }
            }
            break;
        }

        while ($paragraphs) {
            $first = trim((string) $paragraphs[0]);
            if ($first === '') {
                array_shift($paragraphs);
                continue;
            }
            if (!$isDocLike($first) && preg_match($leadingCue, $first)) {
                $leaked[] = $first;
                array_shift($paragraphs);
                continue;
            }
            break;
        }

        return [trim(implode("\n\n", $paragraphs)), trim(implode("\n\n", $leaked))];
    }

    /**
     * Get base artifact generation instructions.
     */
    protected function getBaseArtifactInstructions(): string
    {
        return "You are an AI assistant. You MUST NEVER use standard markdown code blocks (```) for code. ANY time you write code, snippets, documents, files, or structured content, you MUST encapsulate it within an <antArtifact> block. Use the following format:\n<antArtifact identifier=\"unique-id\" type=\"application/vnd.ant.code\" language=\"language-name\" title=\"Title\">\nContent here\n</antArtifact>\nIf the user asks to generate a document, report, PDF, DOCX, or any text-based file, you MUST generate a well-structured Markdown document (language=\"markdown\") inside the <antArtifact> tag. DO NOT EVER generate raw file byte streams or PostScript code. The system will automatically convert your Markdown into downloadable files for the user. Focus only on writing excellent text content inside the <antArtifact> tag. Provide detailed explanation OUTSIDE the tag describing your approach, structure, and key decisions."
            . "\n\nUPDATING AN EXISTING ARTIFACT (small revisions): when the user asks to revise/fix/change PART of a document or code you already produced, DO NOT rewrite the whole artifact. Reuse the SAME identifier and add command=\"update\", then provide one or more find/replace pairs:\n"
            . "<antArtifact identifier=\"same-id-as-before\" type=\"text/markdown\" title=\"Same Title\" command=\"update\">\n"
            . "<antOldContent>exact text currently in the artifact (copy it verbatim, 1-10 lines)</antOldContent>\n"
            . "<antNewContent>the replacement text</antNewContent>\n"
            . "</antArtifact>\n"
            . "Rules: the <antOldContent> text MUST match the current artifact exactly (character for character); use multiple old/new pairs for multiple spots; for BIG rewrites (restructuring, adding whole chapters) output the full document again WITHOUT command=\"update\" but with the same identifier.";
    }

    /**
     * Get document quality and formatting instructions.
     */
    protected function getDocumentQualityInstructions(): string
    {
        return "\n\nCRITICAL INSTRUCTION FOR PDF/DOCX REQUESTS:\n"
            . "RESPONSE ORDER: write your process explanation FIRST in plain chat text, THEN open the <antArtifact> block containing ONLY the document, then close it. Do NOT write anything after </antArtifact>.\n"
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
            . "     • Per-chapter rundown / uraian isi bab: 'BAB I — Bab ini menguraikan...', 'BAB II — Bab ini menyajikan...' dst. JANGAN menulis sub-bab 'Sistematika Penulisan' atau daftar ringkasan isi tiap bab di dalam artifact — uraian struktur per-bab HANYA boleh ada di chat response, di luar artifact.\n"
            . "  ✅ ALLOWED: ONLY the actual document content (front-matter, headings, body text, tables, figures, references)\n"
            . "  📍 ALL conversational text MUST be placed OUTSIDE the <antArtifact> block, and always BEFORE the opening <antArtifact> tag (never after it, so nothing leaks into the document if the response is cut off).\n"
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
            . "\n--- STRUKTUR & ISI BAB (STANDAR AKADEMIK — ikuti pola ini kecuali user/template minta lain) ---\n"
            . "- DILARANG KERAS membuat sub-bab 'Sistematika Penulisan' atau daftar rangkuman 'BAB I berisi... BAB II berisi...' DI DALAM artifact. Rangkuman isi tiap bab HANYA ditulis di chat response (di luar artifact) sebagai penjelasan proses.\n"
            . "- Pembuka bab: boleh SATU paragraf pengantar singkat tentang cakupan bab ITU SENDIRI (contoh: 'Bab ini menguraikan landasan ilmiah yang menopang penelitian...'), TAPI dilarang merangkum isi bab-bab lain di situ.\n"
            . "- BAB I PENDAHULUAN (skripsi/proposal): ## 1.1 Latar Belakang → ## 1.2 Perumusan Masalah → ## 1.3 Tujuan → ## 1.4 Batasan Masalah → (## 1.5 Hipotesis jika relevan) → ## 1.6 Rencana Kegiatan dengan sub-sub-bab tahapan (### 1.6.1 Tahap Kajian Pustaka, ### 1.6.2 Pengumpulan Data, ### 1.6.3 Perancangan dan Implementasi Sistem, ### 1.6.4 Pengujian dan Evaluasi) → ## 1.7 Jadwal Kegiatan berisi TABEL jadwal (Tabel 1.1: kolom = Kegiatan lalu bulan 1-6, isi sel pakai tanda ✓).\n"
            . "- BAB II KAJIAN PUSTAKA / TINJAUAN PUSTAKA: ## 2.1 Penelitian Terdahulu WAJIB berisi tabel perbandingan (Tabel 2.1: Peneliti | Tahun | Judul/Fokus | Metode | Hasil | Perbedaan dengan Penelitian Ini; minimal 5 baris, semua dirujuk sitasi [n]) lalu paragraf sintesis yang menegaskan research gap → ## 2.2 Landasan Teori dengan sub-sub-bab bernomor per konsep inti (### 2.2.1, ### 2.2.2, ...), gunakan tabel ringkasan bila konsep bertahap/berkomponen (mis. Tabel 2.2 Tahapan, Tabel 2.3 Perbandingan Pendekatan) → ## 2.3 Kerangka Pemikiran dengan diagram SVG (Gambar 2.1) dan tabel Masukan-Proses-Luaran.\n"
            . "- BAB III PERANCANGAN/METODOLOGI: ## 3.1 Gambaran Umum → ## 3.2 Arsitektur Sistem dengan diagram berlapis (Gambar 3.1) dan uraian per lapisan → perancangan metode dengan sub-sub-bab bernomor → ## Flowchart Sistem (Gambar 3.x) diikuti narasi alurnya → ## Skenario Pengujian dan Evaluasi dengan tabel distribusi pengujian & tabel metrik.\n"
            . "- Setiap konsep/pemetaan yang berbentuk daftar berpasangan (kategori→contoh→sumber, teknik→prinsip→peran, metrik→definisi→indikator) sajikan sebagai TABEL bernomor, bukan bullet panjang.\n"
            . "- Isi setiap sub-bab harus paragraf substantif utuh (bukan outline satu kalimat), dengan sitasi [n] pada klaim yang bersumber.\n"
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
            . "- Provide detailed, comprehensive responses by default. For simple factual questions, be direct but still thorough. For complex tasks, provide extensive explanations including your reasoning, approach, alternatives considered, and decision rationale. Err on the side of being more detailed rather than too brief. Match the user's language.\n"
            . "- Choose the right format for the content. Use a Markdown table ONLY when the data is genuinely tabular: several items compared across the same attributes (comparisons, specifications, schedules, price lists, pros/cons of multiple options). One table like that per answer is usually enough. Do NOT use tables for narrative explanations, definitions, concepts, a single item's description, or step-by-step instructions — those read better as prose or lists. This guidance applies both to chat answers and to documents inside <antArtifact>.\n"
            . "- For enumerations and step-by-step explanations, use NUMBERED lists (1. 2. 3.), each item starting with a short bold label followed by the explanation — not a wall of plain dots.\n"
            . "- RUNNABLE ANALYSIS CODE: when the user needs a calculation, statistic, or data analysis whose OUTPUT matters (not the code itself), provide ONE self-contained JavaScript snippet inside a ```js-run fenced block that prints its results with console.log(...). The app shows a Run button and executes it safely for the user. This fence is an explicit exception to the artifact rule — every other kind of code still goes inside <antArtifact>.";
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
