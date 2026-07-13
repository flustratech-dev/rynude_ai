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
        ?string $precomputed = null,
        bool $quality = false
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

            // Tiny local GGUF models (0.5B–3B) cannot follow the full heavyweight
            // artifact/skripsi prompt — they parrot the skeleton example verbatim
            // ("Judul Dokumen"/"…isi lengkap…") and loop. Give them a slim prompt
            // and skip the strict-format skeleton from adaptSystemPrompt.
            $llamaService = app(\App\Services\LlamaServerService::class);
            $ggufTier = 'small';
            if ($llamaService->isGgufModel($model)) {
                $ggufTier = $llamaService->tierFor($model);
                [$systemPrompt, $isGgufDocRequest] = $this->buildLocalModelSystemPrompt($conversation, $messages, $searchBlock, $simulateThinking, $ggufTier);
            } else {
                $isGgufDocRequest = false;
                // Build the complete system prompt with all context
                $systemPrompt = $this->buildSystemPrompt($conversation, $messages, $webSearch, $researchMode, $searchBlock, $simulateThinking);

                // Per-model adjustments (smaller/proxy models get stricter format rules)
                $systemPrompt = (new \App\Services\AI\Normalization\ModelAdapterRegistry())
                    ->for($model)
                    ->adaptSystemPrompt($systemPrompt);
            }

            // Apply sliding window context strategy (token budget per model)
            $messagesForAi = $this->applySlidingWindow($messages, $systemPrompt, $model);

            // Per-chapter skripsi pipeline (perubahan.md #2): a full skripsi never
            // fits one local generation, so skripsi-shaped requests are written
            // chapter by chapter and stitched into one artifact.
            // Two triggers: (a) the turn itself asks for a skripsi, or (b) the
            // turn ANSWERS the pipeline's clarify question — a chip answer like
            // "Metode kualitatif" carries no skripsi keyword, so the last-message
            // doc detector ($isGgufDocRequest) misses it by design.
            $useChapterPipeline = false;
            $babLimit = null; // max BAB for a scoped skripsi ("sampai bab N")
            $fullSkripsiIntent = false; // "susun/lanjut FULL skripsi" → pipeline, not revision
            if ($llamaService->isGgufModel($model)) {
                $lastAssistantText = '';
                $latestUserForScope = '';
                for ($i = count($messages) - 1; $i >= 0; $i--) {
                    if ($lastAssistantText === '' && ($messages[$i]['role'] ?? '') === 'assistant') {
                        $lastAssistantText = (string) ($messages[$i]['content'] ?? '');
                    }
                    if ($latestUserForScope === '' && ($messages[$i]['role'] ?? '') === 'user') {
                        $latestUserForScope = is_array($messages[$i]['content'])
                            ? collect($messages[$i]['content'])->where('type', 'text')->pluck('text')->implode(' ')
                            : (string) ($messages[$i]['content'] ?? '');
                    }
                }
                // A scoped skripsi ("full sampai bab 1", "bab 1 saja") ALSO goes
                // through the per-chapter pipeline — so it gets the clarify question,
                // per-stage thinking + explanation, and REAL chapter content — just
                // LIMITED to the requested BAB. The old single-shot path produced
                // empty outlines with placeholder text ("isi bagian ini ditulis…").
                // The scope ("sampai bab N") often lives one or more turns BACK: after
                // the clarify chip the newest user turn is just "Metode kualitatif…",
                // which carries no scope. Read the joined recent user turns so the
                // original "…sampai bab 1" is still seen — otherwise babLimit resets to
                // null on the write turn and the whole BAB I–V gets produced.
                $scopeText = $this->recentUserRequestText($messages, 4);
                $singleBab = false;
                $babTarget = null;
                $babLimit = null;

                if (preg_match('/\b(sampai\s+bab|s\/d\s+bab|hingga\s+bab)\s*([ivx0-9]+)\b/i', $scopeText, $m)) {
                    $babLimit = $this->detectBabReference('bab ' . $m[2]);
                } elseif (preg_match('/\b(bab\s*[ivx0-9]+\s*(saja|dulu|aja)|cuma\s+bab|hanya\s+bab|khusus\s+bab)\b/i', $scopeText)) {
                    $singleBab = true;
                    $babTarget = $this->detectBabReference($scopeText);
                } elseif (preg_match('/\bbab\s+([ivx0-9]+)\b/i', $scopeText, $m) && !preg_match('/\b(full|lengkap|semua\s+bab)\b/i', $scopeText)) {
                    $singleBab = true;
                    $babTarget = $this->detectBabReference('bab ' . $m[1]);
                }

                $chapterScoped = ($babLimit !== null || ($singleBab && $babTarget !== null));

                $isSkripsiCreate = $this->isSkripsiPipelineRequest($messages);

                // Fix #3 (Stanza test Q6): "lanjut penyusunan FULL skripsi" must write
                // the WHOLE skripsi via the pipeline — NOT be caught by the revision
                // router (which revised the last stray artifact, a Daftar Pustaka, →
                // ngaco). Detected standalone (isSkripsiPipelineRequest misses the
                // nominal "penyusunan"/bare "lanjut"): mentions skripsi + a build/whole
                // word, NOT pointing at one specific BAB, and NOT a plain question.
                $curTurn = trim($latestUserForScope);
                $isPlainQuestion = (bool) preg_match('/^(apa|apakah|bagaimana|gimana|kenapa|mengapa|adakah|bisakah|apa kah)\b/i', $curTurn)
                    && str_ends_with($curTurn, '?');
                $fullSkripsiIntent = (bool) preg_match('/\b(skripsi|tesis|tugas akhir)\b/i', $curTurn)
                    && (bool) preg_match('/\b(full|penuh|lengkap|seluruh|keseluruhan|semua\s+bab|penyusunan|penulisan|pembuatan|menyusun|susun(?:kan)?|menulis|tulis(?:kan)?|kerjakan|selesaikan|lanjut(?:kan)?|teruskan)\b/i', $curTurn)
                    && $this->detectBabReference($curTurn) === null
                    && !$isPlainQuestion;

                $useChapterPipeline = ($isGgufDocRequest && $isSkripsiCreate)
                    // A scoped skripsi ("sampai bab N") must ALWAYS enter the pipeline,
                    // never the single-shot skeleton path — whose full BAB I–V outline
                    // would otherwise leak into a "bab 1 only" document.
                    || ($chapterScoped && $isSkripsiCreate)
                    || $fullSkripsiIntent
                    || str_contains($lastAssistantText, 'Metode penelitian apa yang ingin Anda pakai');
            }

            // ── Revision / upload-continuation routing (issues #5 & #6) ───────
            // A follow-up action on an existing document must UPDATE that document
            // (not dribble plain text into chat, #5); and a turn that uploads a
            // document or continues one must NEVER regenerate from scratch via the
            // skripsi pipeline (#6). Both force a document turn and bypass the
            // pipeline.
            $isRevisionTurn = false;
            $revisionArtifact = null;
            $isUploadContinuation = false;
            $isSkripsiContinuation = false;
            $continuationRange = null; // [fromBab, toBab] when appending new chapters
            if ($llamaService->isGgufModel($model)) {
                $latestUserText = '';
                $latestUserHasDoc = false;
                for ($i = count($messages) - 1; $i >= 0; $i--) {
                    if (($messages[$i]['role'] ?? '') === 'user') {
                        $latestUserText = is_array($messages[$i]['content'])
                            ? collect($messages[$i]['content'])->where('type', 'text')->pluck('text')->implode(' ')
                            : (string) ($messages[$i]['content'] ?? '');
                        foreach (($messages[$i]['attachments'] ?? []) as $att) {
                            $mime = strtolower((string) ($att['file_type'] ?? ''));
                            $ext = strtolower(pathinfo((string) ($att['file_name'] ?? ''), PATHINFO_EXTENSION));
                            if (str_contains($mime, 'pdf') || str_contains($mime, 'word')
                                || in_array($ext, ['pdf', 'docx', 'doc', 'txt', 'md'], true)) {
                                $latestUserHasDoc = true;
                            }
                        }
                        break;
                    }
                }

                $revisionPattern = '/\b(perpanjang|perdalam|perbanyak|tambah(?:kan)?\s+(?:bab|isi|sub ?bab)|lanjut(?:kan)?|teruskan|sambung|revisi|perbaiki\s+(?:struktur|format|isi)|kembangkan|lengkapi)\b/i';

                // #5: follow-up action on the room's active markdown artifact.
                // Fix #3: a FULL-skripsi request is NOT a revision — let it hit the
                // pipeline even though it contains "lanjut"/"susun".
                if (!$latestUserHasDoc && !$fullSkripsiIntent && preg_match($revisionPattern, $latestUserText)) {
                    $revisionArtifact = $this->latestMarkdownArtifact($conversation);
                    if ($revisionArtifact) {
                        $isRevisionTurn = true;

                        // #4: "lanjutkan/teruskan/sambung/tambah BAB N" on an active
                        // SKRIPSI must APPEND the next chapter(s) to the SAME document
                        // — not regenerate the whole thing (which produced a new,
                        // ngaco doc). Only when the target chapter isn't there yet;
                        // "perdalam BAB IV" on an existing BAB IV stays a normal revision.
                        $content = (string) $revisionArtifact->content;
                        $isSkripsiDoc = (bool) preg_match('/^\s*---[\s\S]*?\bmode:\s*(skripsi|tesis)\b/i', $content)
                            || (bool) preg_match('/^#\s+BAB\s+[IVX]/mi', $content);
                        $continueVerb = (bool) preg_match('/\b(lanjut(?:kan)?|teruskan|sambung|tambah(?:kan)?\s+bab)\b/i', $latestUserText);
                        if ($isSkripsiDoc && $continueVerb) {
                            $highest = $this->highestBabInDocument($content);
                            $target = $this->detectBabReference($latestUserText) ?? ($highest + 1);
                            if ($target > $highest && $highest >= 1 && $target <= 5) {
                                $isSkripsiContinuation = true;
                                $continuationRange = [$highest + 1, $target];
                            }
                        }
                    }
                }

                // #6: uploaded a document and asked to build/continue from it.
                if ($latestUserHasDoc && $this->wantsDocumentCreation($latestUserText)) {
                    $isUploadContinuation = true;
                }

                if ($isRevisionTurn || $isUploadContinuation) {
                    $isGgufDocRequest = true;   // force artifact output + grammar
                    $useChapterPipeline = false; // never write from scratch
                }

                // Fix #2 (Stanza test Q4): a QUESTION ABOUT a document ("apakah daftar
                // pustaka valid?", "bisa dibuka?", "akurat?") must be answered in CHAT,
                // not re-emitted as a new artifact. Only override when this isn't a
                // real build/revision/continuation turn.
                if ($isGgufDocRequest && !$useChapterPipeline && !$isRevisionTurn
                    && !$isUploadContinuation && !$isSkripsiContinuation) {
                    
                    if ($this->isDocumentQuestion($latestUserText)) {
                        $isGgufDocRequest = false; // → plain chat answer
                    } elseif ($this->isTitleSuggestionRequest($latestUserText)) {
                        $isGgufDocRequest = false; // → plain chat answer
                        
                        // Sisipkan reminder ringan agar model menjawab dengan 5–8 opsi judul
                        $lastIdx = count($messagesForAi) - 1;
                        if (!empty($messagesForAi) && ($messagesForAi[$lastIdx]['role'] ?? '') === 'user') {
                            $messagesForAi[$lastIdx]['content'] .= "\n\n[SISTEM: Berikan 5-8 saran judul skripsi dalam format daftar bernomor langsung di chat. JANGAN membuat dokumen/artifact.]";
                        }
                    }
                }
            }

            // For GGUF models on a document request, inject a targeted SYSTEM REMINDER
            // into the last user message — same mechanism used by the cloud path but
            // only fired when actually needed (prevents spurious artifacts on greetings).
            if ($isGgufDocRequest && !$useChapterPipeline && !$isSkripsiContinuation && !empty($messagesForAi)) {
                $lastIdx = count($messagesForAi) - 1;
                if ($messagesForAi[$lastIdx]['role'] === 'user') {
                    if ($isRevisionTurn && $revisionArtifact) {
                        // #5: revise the active artifact in full, reusing structure.
                        $messagesForAi[$lastIdx]['content'] .= $this->docRevisionReminder($revisionArtifact, $ggufTier);
                    } elseif ($isUploadContinuation) {
                        // handled by streamUploadSkripsiContinuation
                    } else {
                        // Per-type skeleton (issue #2): makalah/laporan/proposal/jurnal
                        // no longer get a hardcoded 5-chapter skripsi skeleton + a
                        // skripsi cover. detectDocType picks the right mode + structure.
                        $docType = $this->detectDocTypeFromMessages($messages);
                        $messagesForAi[$lastIdx]['content'] .= $this->docArtifactReminder($docType, $ggufTier);
                    }
                }
            }

            // Stream from AI service
            if ($useChapterPipeline) {
                $stream = $this->streamSkripsiPerChapter($conversation, $messages, $model, $ggufTier, $stopKey, $babLimit, $singleBab ? $babTarget : null);
            } elseif ($isSkripsiContinuation && $revisionArtifact && $continuationRange) {
                // #4: append the next chapter(s) to the SAME skripsi document.
                $stream = $this->streamSkripsiContinuation(
                    $conversation, $model, $ggufTier, $stopKey, $revisionArtifact,
                    $continuationRange[0], $continuationRange[1], $this->recentUserRequestText($messages)
                );
            } elseif ($isUploadContinuation) {
                $stream = $this->streamUploadSkripsiContinuation($conversation, $messages, $model, $ggufTier, $stopKey);
            } else {
                // Constrained output (perubahan.md #3): on local GGUF document
                // requests a GBNF grammar physically forces the reply shape —
                // optional reasoning, short preamble, then ONE <antArtifact>
                // block. The "document stuck in chat" failure mode disappears.
                $genOptions = $isGgufDocRequest ? ['grammar' => $this->docArtifactGrammar()] : [];

                $lastUserPlain = $this->recentUserRequestText($messages, 1);
                $lastUserHasFiles = false;
                for ($i = count($messages) - 1; $i >= 0; $i--) {
                    if (($messages[$i]['role'] ?? '') === 'user') {
                        $lastUserHasFiles = !empty($messages[$i]['attachments']);
                        break;
                    }
                }

                // Document Q&A on local models: small Qwen3 drowns in its
                // native thinking on these turns (finishes reasoning, then
                // emits NO answer) and tends to wrap the answer in a spurious
                // artifact. Fix both at the source: answer directly in chat,
                // and disable thinking via Qwen3's official /no_think switch.
                if ($llamaService->isGgufModel($model) && $lastUserHasFiles
                    && !$isGgufDocRequest && !$useChapterPipeline && !empty($messagesForAi)) {
                    $lastIdx = count($messagesForAi) - 1;
                    if ($messagesForAi[$lastIdx]['role'] === 'user') {
                        $messagesForAi[$lastIdx]['content'] .=
                            "\n\n[SISTEM: Jawab pertanyaan tentang dokumen ini LANGSUNG di chat sebagai teks Markdown biasa. "
                            . "JANGAN membuat <antArtifact> dan jangan membuat dokumen baru — user bertanya, bukan minta dibuatkan dokumen. "
                            . "Jawab berdasarkan kutipan dokumen di atas.] /no_think";
                    }
                }

                if (!$isGgufDocRequest && !$quality && !$webSearch && !$researchMode
                    && $llamaService->isGgufModel($model) && !$lastUserHasFiles
                    && $this->needsFreshInfo($lastUserPlain)) {
                    // Deterministic fresh-info route (perubahan.md #8): questions
                    // about prices/news/"terbaru" go STRAIGHT to search — small
                    // models can't be trusted to ask for the tool themselves.
                    $stream = $this->searchThenReanswer(
                        \Illuminate\Support\Str::limit($lastUserPlain, 120, ''),
                        $messagesForAi,
                        $model,
                        $stopKey
                    );
                } elseif ($quality) {
                    // Self-critique (perubahan.md #5): draft silently, review,
                    // then stream the improved rewrite as the visible answer.
                    $stream = $this->streamWithSelfCritique($messagesForAi, $model, $genOptions, $stopKey);
                } else {
                    $stream = $this->aiService->streamResponse($messagesForAi, $model, $genOptions);

                    // Local tool use (perubahan.md #8): the model may still ask
                    // via <antSearch>; ALSO intercept "I have no access/offline"
                    // surrender answers and turn them into a real search.
                    // NEVER on attachment turns — document Q&A must stay on the
                    // document, not get hijacked into a web search.
                    if (!$isGgufDocRequest && !$webSearch && !$researchMode && !$lastUserHasFiles
                        && $llamaService->isGgufModel($model)) {
                        $stream = $this->interceptLocalSearch($stream, $messagesForAi, $model, $stopKey, $lastUserPlain);
                    }
                }

                // Language watchdog for local plain chat: when the user wrote
                // Indonesian, sniff the first ~250 chars of the answer; if the
                // model drifted into English, scrap that answer BEFORE it is
                // shown and regenerate with a hard Indonesian-only directive.
                if (!$isGgufDocRequest && $llamaService->isGgufModel($model)
                    && !$this->looksEnglish($this->recentUserRequestText($messages, 1))) {
                    $stream = $this->guardIndonesianAnswer($stream, $messagesForAi, $model);
                }
            }
        }

        $fullResponse = '';
        $thinkingText = '';
        $stopped = false;
        $truncated = false;
        // Option chips (Claude-style buttons above the composer): clarifying
        // answer choices or follow-up actions. Filled either by the skripsi
        // pipeline (structured chunks) or by an <antOptions> tag in the reply.
        $suggestions = [];
        // The tag scanner runs whenever thinking mode is on — not just when we
        // prompted for it — so models that natively emit inline <think>/<thinking>
        // blocks in their content (DeepSeek-style via HF/proxies) also get their
        // reasoning routed to the thinking panel instead of leaking raw tags.
        // Local GGUF models (Qwen3 generation) reason natively on EVERY turn,
        // so for them the scanner is always on regardless of the toggle.
        $isLocalGgufModel = app(\App\Services\LlamaServerService::class)->isGgufModel($model);
        $simState = ['phase' => ($thinking || $isLocalGgufModel) ? 'detect' : 'off', 'buf' => '', 'close' => null];

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
                    // 'transient' thinking (skripsi pipeline) is shown live but
                    // NOT merged into the final message — each stage's reasoning
                    // is stored on its own report bubble instead.
                    if (empty($chunk['transient'])) {
                        $thinkingText .= $chunk['text'];
                    }
                    yield ['type' => 'thinking', 'data' => $chunk['text']];
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'truncated') {
                    // Answer hit the provider's max_tokens ceiling — reported
                    // via the done event so the UI can offer "Continue".
                    $truncated = true;
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'progress') {
                    // Per-stage report from the skripsi pipeline: already saved
                    // as its own Message row — forward so the client renders a
                    // separate bubble immediately, without stopping the run.
                    yield $chunk;
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'suggestions') {
                    $suggestions = is_array($chunk['data'] ?? null) ? $chunk['data'] : [];
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'citations') {
                    // Sources gathered mid-stream by the local search intercept —
                    // saved on the message and emitted as chips after the answer.
                    $citations = is_array($chunk['data'] ?? null) ? $chunk['data'] : $citations;
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

        // ── Auto-continue for cut-off documents (local GGUF) ─────────────────
        // Local models routinely stop mid-document (early EOS or the output
        // token cap), leaving an unclosed <antArtifact> and half a skripsi.
        // Detect that and ask the model to continue — up to 2 rounds — then
        // stitch the pieces into ONE complete document before artifact parsing.
        if (($isGgufDocRequest ?? false) && !($useChapterPipeline ?? false) && !$stopped && isset($messagesForAi)) {
            for ($round = 0; $round < 2; $round++) {
                $unfinished = $truncated
                    || (str_contains($fullResponse, '<antArtifact') && !str_contains($fullResponse, '</antArtifact>'));
                if (!$unfinished) {
                    break;
                }
                $truncated = false;

                $tail = substr($fullResponse, -600);
                $continueMessages = array_merge($messagesForAi, [
                    ['role' => 'assistant', 'content' => $fullResponse],
                    ['role' => 'user', 'content' => "[LANJUTKAN — output Anda terpotong sebelum dokumen selesai.\n"
                        . "Lanjutkan PERSIS dari titik terakhir (potongan terakhir ada di bawah). "
                        . "JANGAN mengulang teks yang sudah ditulis, JANGAN menulis kalimat pembuka, JANGAN memakai <thinking>. "
                        . "Langsung sambung isinya, selesaikan seluruh bab sampai DAFTAR PUSTAKA, lalu tutup dengan </antArtifact>.\n"
                        . "Potongan terakhir:\n...{$tail}]"],
                ]);

                $contText = '';
                // Continuation grammar: the model may only continue the document
                // body and close the </antArtifact> tag — no restarting, no chat.
                foreach ($this->aiService->streamResponse($continueMessages, $model, ['grammar' => $this->docContinuationGrammar()]) as $chunk) {
                    if (Cache::get($stopKey)) {
                        Cache::forget($stopKey);
                        $stopped = true;
                        break;
                    }
                    if (!is_string($chunk)) {
                        if (is_array($chunk) && ($chunk['type'] ?? '') === 'truncated') {
                            $truncated = true;
                        }
                        continue;
                    }
                    $contText .= $chunk;
                }

                // Strip stray reasoning tags and any overlap with what was
                // already written, then splice + stream the remainder out.
                $contText = trim(preg_replace('/<(?:thinking|sim_thinking|think)>.*?(?:<\/(?:thinking|sim_thinking|think)>|$)/is', '', $contText));
                for ($k = min(300, strlen($contText), strlen($fullResponse)); $k > 20; $k--) {
                    if (substr($fullResponse, -$k) === substr($contText, 0, $k)) {
                        $contText = substr($contText, $k);
                        break;
                    }
                }
                if ($contText === '') {
                    break; // model had nothing to add — avoid an infinite loop
                }
                $fullResponse .= $contText;
                foreach (str_split($contText, 400) as $piece) {
                    yield ['type' => 'content', 'data' => $piece];
                }
                if ($stopped) {
                    break;
                }
            }
        }

        // If the user stopped an empty generation, store a small placeholder
        if ($stopped && trim($fullResponse) === '') {
            $fullResponse = '_Generation stopped._';
        }

        // Diagnostic trail for "thinking only, no answer" reports: one line per
        // turn with exactly what came out of the stream.
        \Illuminate\Support\Facades\Log::info(sprintf(
            'Chat stream finished: model=%s content=%dB thinking=%dB truncated=%s stopped=%s quality=%s pipeline=%s',
            $model,
            strlen($fullResponse),
            strlen($thinkingText),
            $truncated ? 'y' : 'n',
            $stopped ? 'y' : 'n',
            $quality ? 'y' : 'n',
            ($useChapterPipeline ?? false) ? 'y' : 'n'
        ));

        // Safety net: the model reasoned but delivered no visible answer
        // (Qwen3-small failure mode). AUTO-RETRY once with thinking disabled
        // (/no_think) instead of bothering the user to resend manually.
        if (trim($fullResponse) === '' && trim($thinkingText) !== '' && !$stopped
            && $precomputed === null && isset($messagesForAi)
            && app(\App\Services\LlamaServerService::class)->isGgufModel($model)) {
            yield ['type' => 'thinking', 'data' => "\n⚠️ Jawaban kosong terdeteksi — mengulang otomatis tanpa mode berpikir…\n"];

            $retryMsgs = $messagesForAi;
            $li = count($retryMsgs) - 1;
            if (($retryMsgs[$li]['role'] ?? '') === 'user') {
                $retryMsgs[$li]['content'] .= "\n\n[SISTEM: Tulis jawaban akhirnya SEKARANG, langsung dan lengkap.] /no_think";
            }

            $retryText = '';
            foreach ($this->aiService->streamResponse($retryMsgs, $model) as $chunk) {
                if (Cache::get($stopKey)) {
                    break;
                }
                if (is_string($chunk)) {
                    $retryText .= $chunk;
                }
            }
            $retryText = trim((string) preg_replace('/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $retryText));

            if ($retryText !== '') {
                $fullResponse = $retryText;
                foreach (str_split($retryText, 400) as $piece) {
                    yield ['type' => 'content', 'data' => $piece];
                }
            }
        }

        // Still empty after the retry (or non-local model): honest notice
        // instead of a silent empty bubble.
        if (trim($fullResponse) === '' && trim($thinkingText) !== '' && !$stopped) {
            $notice = "_Maaf, jawaban tidak sempat tersusun — seluruh keluaran terpakai untuk proses berpikir"
                . ($truncated ? ' sampai batas token habis' : '')
                . ". Coba kirim ulang pertanyaannya (atau matikan mode thinking 💡 untuk pertanyaan ini)._";
            $fullResponse = $notice;
            foreach (str_split($notice, 400) as $piece) {
                yield ['type' => 'content', 'data' => $piece];
            }
        }

        // Safety net for GGUF local models: if the model wrapped the document
        // in a ```markdown ... ``` code block instead of <antArtifact> tags
        // (common failure mode for tiny models), convert it automatically so
        // the artifact panel still opens correctly in the UI.
        $isGgufModel = app(\App\Services\LlamaServerService::class)->isGgufModel($model);

        // Stranded <antSearch> tag: the model asked for a web search on a path
        // where no interceptor was armed (e.g. the globe toggle path, whose
        // research had already run, or quality mode). HONOR the request instead
        // of silently stripping it — stripping left the visible answer EMPTY.
        if ($isGgufModel && !$stopped && $precomputed === null && isset($messagesForAi)
            && preg_match('/<antSearch>([\s\S]{2,200}?)<\/antSearch>/i', $fullResponse, $searchTag)) {
            $fullResponse = trim((string) preg_replace('/<antSearch>[\s\S]*?(?:<\/antSearch>|$)/i', '', $fullResponse));
            foreach ($this->searchThenReanswer(trim($searchTag[1]), $messagesForAi, $model, $stopKey) as $chunk) {
                if (!is_string($chunk)) {
                    if (is_array($chunk) && ($chunk['type'] ?? '') === 'thinking' && ($chunk['text'] ?? '') !== '') {
                        if (empty($chunk['transient'])) {
                            $thinkingText .= $chunk['text'];
                        }
                        yield ['type' => 'thinking', 'data' => $chunk['text']];
                    } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'citations') {
                        $citations = is_array($chunk['data'] ?? null) ? $chunk['data'] : $citations;
                    }
                    continue;
                }
                $fullResponse .= $chunk;
                yield ['type' => 'content', 'data' => $chunk];
            }
        }
        if ($isGgufModel && !str_contains($fullResponse, '<antArtifact') && !str_contains($fullResponse, '<artifact')) {
            $fullResponse = $this->normalizeGgufCodeBlockToArtifact($fullResponse);
        }

        // Option chips embedded by the model: <antOptions>A | B | C</antOptions>
        // at the end of the reply (clarifying answer choices or follow-up
        // actions). Strip the tag from the visible/stored text and surface the
        // options as buttons. Pipeline-provided suggestions take precedence.
        if (preg_match('/<antOptions>([\s\S]*?)<\/antOptions>/i', $fullResponse, $optMatch)) {
            $parsedOptions = array_values(array_filter(
                array_map(fn ($o) => trim(strip_tags($o)), preg_split('/\||\n/', $optMatch[1])),
                fn ($o) => $o !== '' && mb_strlen($o) <= 120
            ));
            if (empty($suggestions) && !empty($parsedOptions)) {
                $suggestions = array_slice($parsedOptions, 0, 4);
            }
        }
        $fullResponse = trim((string) preg_replace('/<antOptions>[\s\S]*?(?:<\/antOptions>|$)/i', '', $fullResponse));
        // Stray search tags must never reach the saved answer (the intercept
        // normally consumes them; this covers mid-answer or malformed ones).
        $fullResponse = trim((string) preg_replace('/<antSearch>[\s\S]*?(?:<\/antSearch>|$)/i', '', $fullResponse));

        // Parse artifacts if present (a reply may carry several)
        $parsedArtifacts = $this->parseArtifacts($fullResponse);

        // #5: on a revision turn, pin the produced artifact to the SAME identifier
        // (and title) as the document being revised, so the save logic below turns
        // it into a NEW VERSION of that document instead of a brand-new artifact —
        // regardless of what identifier the small model did or didn't emit.
        if (($isRevisionTurn ?? false) && ($revisionArtifact ?? null) && !empty($parsedArtifacts['items'])) {
            $parsedArtifacts['items'][0]['identifier'] = $revisionArtifact->identifier;
            if (($parsedArtifacts['items'][0]['title'] ?? 'Document') === 'Document') {
                $parsedArtifacts['items'][0]['title'] = $revisionArtifact->title;
            }
            // Small local models routinely DROP the YAML front-matter when
            // rewriting, which would strip the document's academic cover/mode
            // (e.g. makalah → plain document) on export. If the revision didn't
            // reproduce a front-matter, carry over the most recent one that
            // exists ANYWHERE in this document's version history (the immediate
            // base may itself have lost it on an earlier revision, so we can't
            // rely on it alone) so the cover/layout survives across revisions.
            $newContent = ltrim((string) $parsedArtifacts['items'][0]['content']);
            if (!str_starts_with($newContent, '---')) {
                $fmSource = MessageArtifact::where('identifier', $revisionArtifact->identifier)
                    ->orderByDesc('id')
                    ->get(['content'])
                    ->first(fn ($a) => preg_match('/^\s*---\r?\n.*?\r?\n---\r?\n/s', (string) $a->content));
                if ($fmSource && preg_match('/^\s*(---\r?\n.*?\r?\n---\r?\n)/s', (string) $fmSource->content, $fm)) {
                    $parsedArtifacts['items'][0]['content'] = $fm[1] . "\n" . $newContent;
                }
            }
        }

        // Academic documents must carry YAML front-matter so the renderer applies
        // the cover/TOC layout. Small local models sometimes emit the body but
        // forget the front-matter on a single-shot document turn (seen live: a
        // skripsi came out as a plain document, no cover). Synthesize a minimal
        // front-matter from the artifact title when it's missing, so the correct
        // cover still renders. The per-chapter pipeline already builds its own.
        if (($isGgufDocRequest ?? false) && !($isRevisionTurn ?? false)
            && !($useChapterPipeline ?? false) && !empty($parsedArtifacts['items'])) {
            $docType = $this->detectDocTypeFromMessages($messages);
            if (in_array($docType, ['skripsi', 'tesis', 'makalah', 'laporan', 'proposal', 'jurnal'], true)) {
                $body = ltrim((string) $parsedArtifacts['items'][0]['content']);
                if (!str_starts_with($body, '---')) {
                    $judul = trim(str_replace(["\n", "\r"], ' ', (string) ($parsedArtifacts['items'][0]['title'] ?? 'Dokumen')));
                    $fm = "---\nmode: {$docType}\njudul: {$judul}\ntahun: " . date('Y') . "\n---\n\n";
                    $parsedArtifacts['items'][0]['content'] = $fm . $body;
                }
            }
        }

        // Fix #1 (Stanza test Q3/Q4 + user's hard requirement): a turn that produces
        // a DOCUMENT must ALSO have a chat explanation — a local model routinely dumps
        // everything into the artifact and leaves the chat empty (seen live: chat_len=0).
        // If the chat is empty/too short while an artifact exists, synthesize a short
        // deterministic debrief from the doc's title + heading tree, stream it live,
        // and store it. (The skripsi pipeline already fills chat, so it won't trigger.)
        if (!empty($parsedArtifacts['items'])) {
            $chatText = trim((string) ($parsedArtifacts['cleanResponse'] ?? ''));
            if (mb_strlen($chatText) < 40) {
                $explain = $this->buildDocChatExplanation(
                    (string) ($parsedArtifacts['items'][0]['title'] ?? 'Dokumen'),
                    (string) ($parsedArtifacts['items'][0]['content'] ?? '')
                );
                if ($explain !== '') {
                    foreach (str_split($explain, 400) as $piece) {
                        yield ['type' => 'content', 'data' => $piece];
                    }
                    $parsedArtifacts['cleanResponse'] = trim($chatText === '' ? $explain : $chatText . "\n\n" . $explain);
                }
            }
        }

        // A document artifact always deserves "what's next" chips — fall back
        // to sensible defaults when neither pipeline nor model supplied any.
        if (empty($suggestions) && !empty($parsedArtifacts['items'])) {
            $suggestions = [
                'Perpanjang dan perdalam isinya',
                'Perbaiki struktur/format dokumen',
                'Buat versi PDF/DOCX-nya',
            ];
        }

        // Save assistant message to database
        $assistantMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $parsedArtifacts ? $parsedArtifacts['cleanResponse'] : $fullResponse,
            'model' => $model,
            'parent_id' => $parentMessageId,
            'citations' => !empty($citations) ? $citations : null,
            'thinking' => trim($thinkingText) !== '' ? $thinkingText : null,
            'suggestions' => !empty($suggestions) ? $suggestions : null,
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

        // Option chips (clarifying choices / follow-up actions) — rendered as
        // buttons right above the composer, one tap sends the choice.
        if (!empty($suggestions)) {
            yield ['type' => 'suggestions', 'data' => array_values($suggestions)];
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
        $baseSystemPrompt .= $this->getOptionChipsInstructions();

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
            $baseSystemPrompt .= "\n\nEXTENDED THINKING MODE: Begin your response with your step-by-step reasoning wrapped EXACTLY in <thinking> ... </thinking> tags, then write the final answer AFTER the closing tag. The reasoning is hidden from the final answer, so never reference it there.";
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
     * System prompt for local GGUF models (Vignette 0.5B → Magnum 14B).
     *
     * Returns [string $prompt, bool $isDocumentRequest] so the caller can
     * inject a targeted SYSTEM REMINDER into the last user message when needed.
     *
     * For plain chat the prompt stays minimal (prevents looping / parroting).
     * For document requests the YAML front-matter example is intentionally kept
     * OUT of the system prompt — small models confuse prose YAML examples with
     * a cue to wrap output in ```markdown blocks. The full structural example is
     * delivered instead via the SYSTEM REMINDER in-message injection.
     */
    protected function buildLocalModelSystemPrompt(
        Conversation $conversation,
        array $messages = [],
        string $searchBlock = '',
        bool $simulateThinking = false,
        string $tier = 'small'
    ): array {
        // ── Detect whether this turn is a document / skripsi request ──────────
        $lastUserText = '';
        foreach (array_reverse($messages) as $msg) {
            if (($msg['role'] ?? '') === 'user') {
                $lastUserText = strtolower(is_array($msg['content'])
                    ? collect($msg['content'])->where('type', 'text')->pluck('text')->implode(' ')
                    : ($msg['content'] ?? ''));
                break;
            }
        }
        // A document is only PRODUCED when the user actually asks to create one.
        // Merely mentioning "skripsi"/"makalah" ("apa itu skripsi?", "jelaskan
        // makalah") must NOT force an artifact (issue #1) — that used to fire on
        // the bare noun. Now the rule is: a document SIGNAL + a creation-intent verb.
        //
        // A "document signal" is either an explicit document noun, OR an academic
        // structure term (BAB, sub-bab, section names). The latter matters because
        // build/continue turns often carry no noun — e.g. "dari judul ini buatkan
        // saya full sampai bab 1" — yet clearly ask for a document. Requiring a
        // bare noun there was a regression: the chapter got written as plain chat
        // text instead of an artifact.
        $docSignalRe = '/\b(skripsi|makalah|laporan|proposal|tesis|thesis|tugas akhir|jurnal|artikel ilmiah|karya ilmiah|paper|dokumen|document|report|pdf|docx|word'
            . '|bab\s*[ivxlcdm0-9]+|pendahuluan|latar belakang|rumusan masalah|tinjauan pustaka|landasan teori|metodolog|metode penelitian|daftar pustaka|abstrak|kata pengantar|pembahasan)\b/i';
        $hasDocSignalNow = (bool) preg_match($docSignalRe, $lastUserText);

        // Continuation of a document already established earlier in the room: the
        // current turn is a build/continue instruction and a recent turn set up a
        // document (e.g. the user was handed a skripsi title, now says "buatkan
        // full sampai bab 1"). Uses the joined recent turns, not just this one.
        $recentDocCtx = $this->recentUserRequestText($messages, 4);
        $isDocContinuation = (bool) preg_match('/\b(bab|full|lengkap|sampai\s+bab|lanjut|teruskan|sambung|buatkan|buatlah|tuliskan|susun)\b/i', $lastUserText)
            && (bool) preg_match($docSignalRe, $recentDocCtx);

        $isDocumentRequest = ($hasDocSignalNow || $isDocContinuation) && $this->wantsDocumentCreation($lastUserText);

        // ── Base identity & thinking rules (always injected) ──────────────────
        // Two prompt tiers: 'small' (0.5B–3B) keeps the deliberately slim prompt
        // that prevents looping/parroting; 'large' (7B–14B) gets a near-cloud
        // prompt — these models follow long instructions reliably, and the slim
        // prompt was artificially capping their answer depth and artifact quality.
        if ($tier === 'large') {
            $prompt = "You are Rynude, a highly capable, analytical AI assistant running fully offline on the user's computer. Aim for the answer quality of top cloud assistants.\n\n"
                . "=== UNDERSTANDING THE USER ===\n"
                . "Before answering, identify what the user actually needs: the task, the implied depth, the language, and any constraints. If the request is ambiguous, choose the most useful interpretation and state your assumption in one short sentence — do not stall with clarifying questions for simple requests. Follow-up messages refer to the previous topic unless clearly a new subject.\n\n"
                . "=== REASONING PROCESS ===\n"
                . "Before answering, wrap step-by-step internal reasoning inside <thinking>...</thinking> at the very start of your response: break the problem down, consider edge cases, plan the structure of your answer. Write your final answer AFTER </thinking>. Never mention the thinking block in your answer.\n\n"
                . "=== ANSWER QUALITY BAR ===\n"
                . "- Be thorough and substantive: explain the why, not just the what; add concrete examples, numbers, or code where they help.\n"
                . "- Match depth to the question: simple question → direct answer; complex question → structured, multi-section answer.\n"
                . "- Never give lazy one-line answers to substantive questions, and never pad simple answers with filler.\n"
                . "- For coding: give complete, runnable code with brief explanation, not fragments.\n\n"
                . "=== FORMATTING ===\n"
                . "Reply in the user's language. Use clean Markdown: headings (##/###), bullet lists (-), numbered lists, tables when comparing, fenced code blocks with language tags, **bold** for key terms. Never repeat the same line or paragraph.\n\n"
                . "=== DOCUMENT & ARTIFACT RULE ===\n"
                . "When the user asks for a document, skripsi, makalah, laporan, proposal, PDF, or DOCX:\n"
                . "1. NEVER apologize, NEVER claim you cannot create files offline, NEVER suggest Google Docs or Microsoft Word.\n"
                . "2. Write 2-3 sentences in chat explaining your structure (in the user's language).\n"
                . "3. Then output the COMPLETE document inside ONE <antArtifact type=\"text/markdown\" title=\"Document Title\"> ... </antArtifact> block.\n"
                . "4. NEVER use ```markdown code blocks for the document content — always use <antArtifact> tags.\n"
                . "5. Do NOT wrap a plain chat answer in <antArtifact>.\n"
                . "6. Academic documents (skripsi/tesis/makalah) must start with YAML front-matter between --- lines (mode, judul, penulis, nim, prodi, fakultas, universitas, kota, tahun, pembimbing), then full chapters with # / ## headings. Every sub-section gets real, complete paragraphs (minimum 3 substantial paragraphs per sub-bab) — never placeholder text like '...isi...' and never one-sentence sections. End with DAFTAR PUSTAKA containing at least 10 plausible, properly formatted references.\n"
                . "7. Substantial content the user will reuse or edit (documents, long reports, full code files) belongs in an artifact; short explanations and answers stay in chat.\n\n"
                . "=== OPTION CHIPS ===\n"
                . "For a plain chat answer (NOT when producing an <antArtifact> document), you may end the reply with ONE tag: <antOptions>Opsi A | Opsi B | Opsi C</antOptions> (max 4 options, ≤ 60 chars each, user's language). The system renders it as one-tap buttons. Use it only to (a) ask ONE clarifying question when the request is genuinely ambiguous, or (b) offer up to 3 concrete follow-up actions after a substantial answer. Never mention the tag in your prose.\n\n"
                . "=== WEB SEARCH TOOL ===\n"
                . "The system can search the web FOR you — you are NOT cut off from current information. When the question needs fresh or specific facts (news, prices, versions, dates, people, products, statistics), reply with ONLY this single tag and nothing else: <antSearch>concise search keywords</antSearch> — the system will fetch sources and ask you to answer again using them. NEVER claim you \"have no access\", \"cannot access data\", or \"operate offline\" — that is FORBIDDEN; request a search instead. For stable general knowledge, answer directly without the tag. Never use the tag more than once.";
        } else {
            $prompt = "You are Rynude, an intelligent and analytical AI assistant running offline on the user's computer.\n\n"
                . "=== REASONING PROCESS ===\n"
                . "Before answering, wrap step-by-step internal reasoning inside <thinking>...</thinking> at the very start of your response. Write your final answer AFTER </thinking>. Never mention the thinking block in your answer.\n\n"
                . "=== CONVERSATION STYLE ===\n"
                . "- Greetings/small talk → reply warmly in 1-2 sentences, then offer help. No speeches.\n"
                . "- Simple requests → just do/answer them directly. Ask AT MOST one short clarifying question, and only when truly necessary — never interrogate the user with a list of questions.\n"
                . "- Never talk about yourself, your abilities, or your limitations as an AI unless directly asked.\n\n"
                . "=== UNDERSTANDING THE USER ===\n"
                . "First identify what the user is really asking, then answer that directly in the user's language. Follow-up messages refer to the previous topic.\n\n"
                . "=== ANSWER LENGTH ===\n"
                . "Give complete, developed answers: for substantive questions write several full paragraphs or structured sections, never a one-line reply. For documents, write EVERY section in full prose — never summarize, never stop after the first sections.\n\n"
                . "=== FORMATTING ===\n"
                . "Reply in the user's language. Use clean Markdown: headings (##/###), bullet lists (-), numbered lists, fenced code blocks, **bold** for key terms. Never repeat the same line or paragraph.\n\n"
                . "=== DOCUMENT & ARTIFACT RULE ===\n"
                . "When the user asks for a document, skripsi, makalah, laporan, proposal, PDF, or DOCX:\n"
                . "1. NEVER apologize, NEVER claim you cannot create files offline, NEVER suggest Google Docs or Microsoft Word.\n"
                . "2. Write 2-3 sentences in chat explaining your structure (in the user's language).\n"
                . "3. Then output the COMPLETE document inside ONE <antArtifact type=\"text/markdown\" title=\"Document Title\"> ... </antArtifact> block.\n"
                . "4. NEVER use ```markdown code blocks for the document content — always use <antArtifact> tags.\n"
                . "5. Do NOT wrap a plain chat answer in <antArtifact>.\n\n"
                . "=== WEB SEARCH TOOL ===\n"
                . "The system can search the web FOR you — you are NOT cut off from current information. When the question needs fresh or specific facts (news, prices, versions, dates, people, statistics), reply with ONLY this single tag and nothing else: <antSearch>concise search keywords</antSearch> — the system will fetch sources and ask you again. NEVER claim you \"have no access\" or \"operate offline\" — request a search instead. For stable general knowledge, answer directly without the tag.";
        }

        // ── Shared capability block (tier-independent) ───────────────────────
        // Added to BOTH tiers so Lyric 4.6 (tier 'small') still gets diagrams,
        // document-type awareness and the offer-before-building behaviour, while
        // its loop-safe sampling stays untouched ("kasih yang terbaik").

        // (#1) Offer, don't auto-build. Only injected when the turn mentions a
        // document type WITHOUT a clear create request — then the model answers
        // the question and offers via option chips instead of dumping a document.
        if ($isDocumentRequest === false && preg_match('/\b(skripsi|makalah|laporan|proposal|tesis|jurnal|karya ilmiah|dokumen)\b/i', $lastUserText)) {
            $prompt .= "\n\n=== JANGAN LANGSUNG MEMBUAT DOKUMEN ===\n"
                . "Pengguna MENYEBUT jenis dokumen tapi belum jelas meminta dibuatkan. JANGAN langsung menghasilkan <antArtifact> dan JANGAN menanyakan template. "
                . "Jawab/ bahas dulu pertanyaannya secara singkat, lalu TAWARKAN lewat satu tag di akhir: "
                . "<antOptions>Ya, buatkan dokumennya | Jelaskan dulu poin-poinnya | Bantu susun kerangkanya</antOptions>. "
                . "Baru buat dokumen penuh jika pengguna memang memintanya.";
        }

        // (#2) Document-type awareness: the produced document's front-matter
        // `mode:` and chapter structure must match the requested type — a makalah
        // is NOT a 5-chapter skripsi. Only injected on an actual create request.
        if ($isDocumentRequest) {
            $prompt .= "\n\n" . $this->docTypeStructureInstructions($this->detectDocTypeFromMessages($messages));
        }

        // (#3) Diagram generation (Mermaid) — the cloud prompt has this but the
        // local prompt never did, so Lyric never drew diagrams. Slim version:
        $prompt .= "\n\n=== DIAGRAM (MERMAID) ===\n"
            . "Untuk SETIAP permintaan visual (diagram, flowchart, bagan, alur, struktur, arsitektur, ERD, sequence, mindmap, gantt, pie), keluarkan diagram sebagai blok ```mermaid berisi HANYA sintaks Mermaid yang valid — tanpa teks lain di dalam blok. "
            . "Diagram berdiri sendiri: taruh blok ```mermaid langsung di chat. Diagram di dalam dokumen (skripsi/laporan): taruh blok ```mermaid DI DALAM <antArtifact>. "
            . "DILARANG memakai ASCII art, tag HTML/SVG, atau gambar. Hindari tanda ( ) : & \" di dalam label node. Cocokkan bahasa label dengan bahasa pengguna. Sistem otomatis merender blok ```mermaid menjadi diagram.";

        // Hard per-turn language pin: small local models reason in English and
        // drift into answering in English mid-conversation. When THIS turn is
        // written in Indonesian, make the answer language non-negotiable.
        if ($lastUserText !== '' && !$this->looksEnglish($lastUserText)) {
            $prompt .= "\n\n=== ATURAN BAHASA (MUTLAK) ===\n"
                . "Pengguna menulis dalam Bahasa Indonesia. SELURUH jawaban Anda WAJIB dalam Bahasa Indonesia — dari kata pertama sampai kata terakhir. "
                . "Menjawab dalam bahasa Inggris adalah KESALAHAN FATAL. (Proses berpikir internal boleh bahasa apa pun, tapi jawaban final 100% Bahasa Indonesia.)";
        }

        // Custom instructions / language preference still apply.
        if (Auth::check() && !empty(Auth::user()->custom_instructions)) {
            $prompt .= "\n\nUser Custom Instructions:\n" . Auth::user()->custom_instructions;
        }
        if (Auth::check()) {
            $languageNames = [
                'id' => 'Bahasa Indonesia', 'es' => 'Spanish (Español)', 'fr' => 'French (Français)',
                'de' => 'German (Deutsch)', 'ja' => 'Japanese (日本語)', 'zh' => 'Chinese (中文)', 'ar' => 'Arabic (العربية)',
            ];
            $lang = Auth::user()->preferences['language'] ?? 'en';
            if ($lang !== 'en' && isset($languageNames[$lang])) {
                $prompt .= "\n\nAlways respond in {$languageNames[$lang]} unless the user asks otherwise.";
            }
        }

        if (!empty($conversation->style) && $conversation->style === 'concise') {
            $prompt .= "\n\nBe concise and direct.";
        }

        if (trim($searchBlock) !== '') {
            $prompt .= "\n\n" . $searchBlock;
            // The research already ran — the model must answer from these
            // sources NOW, not emit another <antSearch> request (that tag has
            // no interceptor on this path and used to strand the answer).
            $prompt .= "\n\n[PENTING: Sumber web di atas SUDAH disediakan untuk pertanyaan ini. JANGAN memakai tag <antSearch>. Jawab SEKARANG berdasarkan sumber-sumber itu dan kutip nomornya seperti [1].]";
        }

        if ($simulateThinking) {
            $prompt .= "\n\nREMINDER - EXTENDED THINKING MODE: You MUST begin your response with step-by-step reasoning wrapped EXACTLY in <thinking> ... </thinking> tags, then write your structured final answer AFTER closing the </thinking> tag.";
        }

        return [$prompt, $isDocumentRequest];
    }

    /**
     * Safety net for GGUF local models: if the model emitted the document
     * inside a ```markdown ... ``` fenced code block instead of proper
     * <antArtifact> tags, convert it so the artifact panel opens correctly.
     * Only fires when no <antArtifact> tags were found in the response.
     */
    protected function normalizeGgufCodeBlockToArtifact(string $response): string
    {
        // Match a top-level ```markdown ... ``` block (possibly with leading chat text)
        if (!preg_match('/^(.*?)```(?:markdown)?\s*\n([\s\S]*?)```\s*$/s', $response, $m)) {
            return $response; // nothing to convert
        }

        $chatPart    = trim($m[1]);
        $docContent  = trim($m[2]);

        // Extract a title from the document (first # heading or YAML judul field)
        $title = 'Dokumen';
        if (preg_match('/^judul:\s*(.+)$/im', $docContent, $tm)) {
            $title = trim($tm[1]);
        } elseif (preg_match('/^#\s+(.+)$/m', $docContent, $tm)) {
            $title = trim($tm[1]);
        }

        $artifact = "<antArtifact type=\"text/markdown\" title=\"" . htmlspecialchars($title, ENT_QUOTES) . "\">\n"
            . $docContent . "\n</antArtifact>";

        return $chatPart !== '' ? $chatPart . "\n\n" . $artifact : $artifact;
    }

    /**
     * True when the latest user turn asks for a full skripsi/thesis — the case
     * the per-chapter pipeline (perubahan.md #2) is built for. Shorter document
     * types (makalah/laporan/proposal) stay on the single-shot + grammar path.
     */
    protected function isSkripsiPipelineRequest(array $messages): bool
    {
        // Scan the last few user turns, not just the latest: after a clarify
        // chip the newest message is only the answer ("Metode kuantitatif"),
        // while the skripsi ask lives one turn earlier.
        $recent = $this->recentUserRequestText($messages);
        // Must both NAME a skripsi and actually ask to CREATE one (issue #1):
        // "apa itu skripsi?" is a question, not a build request. The joined
        // recent-turns text still carries the original "buatkan skripsi …" when
        // the newest turn is only a chip answer.
        return (bool) preg_match('/\b(skripsi|tesis|thesis|tugas akhir)\b/i', $recent)
            && $this->wantsDocumentCreation($recent);
    }

    /**
     * True when the user's text expresses intent to CREATE/produce a document,
     * not merely mention or ask about one. Gates every document/skripsi trigger
     * (issue #1). Bare "buat" is excluded — in Indonesian it also means "for"
     * (buat kamu = for you) — so it only counts right before a document noun.
     */
    protected function wantsDocumentCreation(string $text): bool
    {
        // Explicit creation / revision verbs (Indonesian + English). Revision and
        // continuation verbs are included so follow-up/continuation turns
        // (issues #5, #6) also resolve to document mode.
        if (preg_match('/\b(buatkan|buatlah|buatin|bikinkan|bikin|membuat|dibuatkan|tuliskan|tulis|menulis|susunkan|susun|menyusun|rancang|merancang|generate|kerjakan|selesaikan|lengkapi|kembangkan|perpanjang|perdalam|perbanyak|revisi|perbaiki|lanjutkan|teruskan|sambung|create|make|write|draft|compose)\b/i', $text)) {
            return true;
        }
        // Bare "buat"/"buatkan" immediately before a document noun.
        if (preg_match('/\bbuat\w*\s+(?:\w+\s+){0,2}(skripsi|makalah|laporan|proposal|tesis|jurnal|artikel|karya ilmiah|dokumen|paper|pdf|docx|word)\b/i', $text)) {
            return true;
        }
        return false;
    }

    /**
     * True when the turn is a QUESTION ABOUT an existing document rather than a
     * request to build/revise one (Stanza test Q4: "apakah daftar pustaka valid
     * dan bisa dibuka?"). Such turns must be answered in chat, never re-emitted as
     * a fresh artifact. Guarded so it only fires when there is NO creation verb.
     */
    protected function isDocumentQuestion(string $text): bool
    {
        $t = trim($text);
        if ($t === '' || $this->wantsDocumentCreation($t)) {
            return false;
        }
        $interrogative = (bool) preg_match('/\?\s*$/', $t)
            || (bool) preg_match('/^(apakah|apa|apa kah|apa saja|adakah|bisakah|bisa kah|bolehkah|benarkah|kenapa|mengapa|bagaimana|gimana|siapa|kapan|di ?mana|berapa)\b/i', $t);
        $aboutDoc = (bool) preg_match('/\b(valid|akurat|benar|betul|aman|asli|nyata|real|bisa\s+dibuka|dapat\s+dibuka|sumber(nya)?|referensi(nya)?|daftar\s+pustaka|isi(nya)?|bab\b|dokumen(nya)?|maksud|arti(nya)?)\b/i', $t);

        return $interrogative && $aboutDoc;
    }

    /**
     * Patch 2: True when the user asks for title suggestions (e.g., "minta saran judul skripsi")
     * rather than asking to build the document.
     */
    protected function isTitleSuggestionRequest(string $text): bool
    {
        $hasSuggestionWord = (bool) preg_match('/\b(saran|sarankan|rekomendasi|rekomendasikan|ide|contoh|usul(?:kan)?|beri(?:kan)?|kasih|minta)\b/i', $text);
        $hasJudulWord = (bool) preg_match('/\bjudul\b/i', $text);
        $hasCreationVerb = (bool) preg_match('/\b(buatkan|buat|susun|tulis(?:kan)?|outline)\b/i', $text);

        return $hasSuggestionWord && $hasJudulWord && !$hasCreationVerb;
    }

    /**
     * Short deterministic chat debrief for a NON-skripsi document turn — used when
     * the model produced an artifact but left the chat empty (Fix #1). Built from
     * the document title + heading tree so it is always accurate and cheap (no
     * extra model call).
     */
    protected function buildDocChatExplanation(string $title, string $content): string
    {
        $title = trim(str_replace(["\n", "\r"], ' ', $title)) ?: 'Dokumen';
        $outline = MessageArtifact::extractOutline($content);
        $lines = [];
        foreach ($outline as $h) {
            $text = trim((string) ($h['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $lines[] = '- ' . $text;
            if (count($lines) >= 8) {
                break;
            }
        }

        $out = "Saya sudah menyusun dokumen **\"{$title}\"** — silakan buka di panel artifact di sebelah kanan.";
        if ($lines) {
            $out .= "\n\nStruktur/isi ringkasnya:\n" . implode("\n", $lines);
        }
        $out .= "\n\nKalau ada yang ingin diperdalam, diubah, atau dibuatkan versi PDF/DOCX-nya, tinggal beri tahu saya.";

        return $out;
    }

    /**
     * Classify the requested academic document type from the user's text so the
     * document gets the RIGHT cover + chapter structure (issue #2). A makalah is
     * not a 5-chapter skripsi; a proposal has no results chapter. Order matters —
     * more specific types are checked first. Falls back to 'umum' (plain doc).
     */
    protected function detectDocType(string $text): string
    {
        $t = mb_strtolower($text);
        // "proposal skripsi" → proposal (checked before skripsi).
        if (preg_match('/\bproposal\b/', $t)) {
            return 'proposal';
        }
        if (preg_match('/\b(tesis|thesis|disertasi)\b/', $t)) {
            return 'tesis';
        }
        if (preg_match('/\b(skripsi|tugas akhir)\b/', $t)) {
            return 'skripsi';
        }
        if (preg_match('/\b(jurnal|artikel ilmiah|paper ilmiah)\b/', $t)) {
            return 'jurnal';
        }
        if (preg_match('/\bmakalah\b/', $t)) {
            return 'makalah';
        }
        if (preg_match('/\blaporan\b/', $t)) {
            return 'laporan';
        }
        return 'umum';
    }

    /**
     * Document type for the CURRENT build turn, falling back to the room's recent
     * context. Needed because a build/continue turn often names no type — e.g. a
     * skripsi brainstorm where the final ask is only "buatkan full sampai bab 1".
     * The current turn wins if it names a type; otherwise the last several user
     * turns are scanned so the document still gets the right cover/structure.
     */
    protected function detectDocTypeFromMessages(array $messages): string
    {
        $last = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $last = is_array($messages[$i]['content'])
                    ? collect($messages[$i]['content'])->where('type', 'text')->pluck('text')->implode(' ')
                    : (string) ($messages[$i]['content'] ?? '');
                break;
            }
        }
        $type = $this->detectDocType($last);
        if ($type !== 'umum') {
            return $type;
        }
        return $this->detectDocType($this->recentUserRequestText($messages, 8));
    }

    /**
     * Per-type structural guidance appended to the local system prompt on a
     * document-creation turn (issue #2). Tells the model the front-matter `mode:`
     * and the chapter skeleton that matches the requested type, so the renderer
     * produces the correct cover (MAKALAH vs SKRIPSI) and the body is not a
     * skripsi skeleton for every request.
     */
    protected function docTypeStructureInstructions(string $type): string
    {
        $head = "=== STRUKTUR DOKUMEN ({$type}) ===\n"
            . "Dokumen WAJIB diawali front-matter YAML di antara garis --- dengan `mode: {$type}` lalu isi bab dengan heading # / ##. Isi setiap sub-bagian dengan paragraf utuh (bukan placeholder). ";
        switch ($type) {
            case 'skripsi':
            case 'tesis':
                return $head
                    . "Front-matter: mode, judul, penulis, nim, prodi, fakultas, universitas, kota, tahun, pembimbing. "
                    . "Struktur: # HALAMAN PENGESAHAN, # ABSTRAK, # ABSTRACT, # BAB I PENDAHULUAN (Latar Belakang, Rumusan Masalah, Tujuan, Batasan, Manfaat), # BAB II TINJAUAN PUSTAKA, # BAB III METODOLOGI PENELITIAN, # BAB IV HASIL DAN PEMBAHASAN, # BAB V PENUTUP, # DAFTAR PUSTAKA (≥10 referensi).";
            case 'proposal':
                return $head
                    . "Front-matter: mode, judul, penulis, nim, prodi, fakultas, universitas, kota, tahun, pembimbing. "
                    . "Proposal penelitian TIDAK memuat bab hasil. Struktur: # BAB I PENDAHULUAN (Latar Belakang, Rumusan Masalah, Tujuan, Manfaat), # BAB II TINJAUAN PUSTAKA, # BAB III METODOLOGI PENELITIAN (jadwal & rancangan), # DAFTAR PUSTAKA.";
            case 'makalah':
                return $head
                    . "Front-matter: mode, judul, penulis, nim (jika ada), prodi, fakultas, universitas, kota, tahun, dosen (mata kuliah / dosen pengampu). "
                    . "Makalah LEBIH RINGKAS dari skripsi — JANGAN pakai 5 bab skripsi. Struktur: # KATA PENGANTAR, # BAB I PENDAHULUAN (Latar Belakang, Rumusan Masalah, Tujuan), # BAB II PEMBAHASAN (boleh beberapa sub-bab sesuai topik), # BAB III PENUTUP (Kesimpulan, Saran), # DAFTAR PUSTAKA.";
            case 'laporan':
                return $head
                    . "Front-matter: mode, judul, penulis, nim, prodi, fakultas, universitas, kota, tahun, pembimbing (jika ada). "
                    . "Struktur laporan: # KATA PENGANTAR, # BAB I PENDAHULUAN (Latar Belakang, Tujuan, Manfaat), # BAB II LANDASAN TEORI, # BAB III PELAKSANAAN / PEMBAHASAN, # BAB IV PENUTUP (Kesimpulan, Saran), # DAFTAR PUSTAKA.";
            case 'jurnal':
                return $head
                    . "Front-matter: mode, judul, penulis, prodi, universitas, tahun. "
                    . "Artikel jurnal TIDAK memakai penomoran BAB. Struktur: # Abstrak (+ Kata Kunci), # Pendahuluan, # Metode, # Hasil dan Pembahasan, # Kesimpulan, # Daftar Pustaka.";
            default:
                return "=== STRUKTUR DOKUMEN (umum) ===\n"
                    . "Dokumen umum: awali dengan front-matter `mode: document` (judul, penulis opsional), lalu susun dengan heading # / ## sesuai kebutuhan topik. Tidak perlu struktur bab akademik kecuali diminta.";
        }
    }

    /**
     * In-message SYSTEM REMINDER injected into the last user turn on a single-shot
     * GGUF document request. Produces a per-type artifact skeleton (issue #2) so a
     * makalah/laporan/proposal/jurnal is NOT forced into a 5-chapter skripsi shape
     * with a skripsi cover. Skripsi itself uses the per-chapter pipeline elsewhere.
     */
    protected function docArtifactReminder(string $type, string $tier): string
    {
        // [render mode, example title, front-matter body, heading skeleton]
        $meta = "judul: Judul Lengkap\npenulis: Nama Penulis\nnim: NIM-12345\nprodi: Program Studi\nfakultas: Nama Fakultas\nuniversitas: Nama Universitas\nkota: Kota\ntahun: 2024\npembimbing: Nama Pembimbing";
        switch ($type) {
            case 'tesis':
            case 'skripsi':
                $mode = $type; $title = 'Judul ' . ucfirst($type) . ' Anda';
                $body = $meta;
                $skeleton = "# HALAMAN PENGESAHAN\n# ABSTRAK\n# ABSTRACT\n# BAB I PENDAHULUAN\n## 1.1 Latar Belakang\n## 1.2 Perumusan Masalah\n## 1.3 Tujuan\n## 1.4 Batasan Masalah\n## 1.5 Manfaat\n# BAB II TINJAUAN PUSTAKA\n# BAB III METODOLOGI PENELITIAN\n# BAB IV HASIL DAN PEMBAHASAN\n# BAB V PENUTUP\n# DAFTAR PUSTAKA";
                break;
            case 'proposal':
                $mode = 'proposal'; $title = 'Proposal Penelitian Anda';
                $body = $meta;
                $skeleton = "# BAB I PENDAHULUAN\n## 1.1 Latar Belakang\n## 1.2 Perumusan Masalah\n## 1.3 Tujuan\n## 1.4 Manfaat\n# BAB II TINJAUAN PUSTAKA\n# BAB III METODOLOGI PENELITIAN\n# DAFTAR PUSTAKA";
                break;
            case 'laporan':
                $mode = 'laporan'; $title = 'Judul Laporan Anda';
                $body = $meta;
                $skeleton = "# KATA PENGANTAR\n# BAB I PENDAHULUAN\n## 1.1 Latar Belakang\n## 1.2 Tujuan\n## 1.3 Manfaat\n# BAB II LANDASAN TEORI\n# BAB III PELAKSANAAN DAN PEMBAHASAN\n# BAB IV PENUTUP\n## 4.1 Kesimpulan\n## 4.2 Saran\n# DAFTAR PUSTAKA";
                break;
            case 'jurnal':
                $mode = 'jurnal'; $title = 'Judul Artikel Anda';
                $body = "judul: Judul Artikel\npenulis: Nama Penulis\nprodi: Program Studi\nuniversitas: Nama Universitas\ntahun: 2024";
                $skeleton = "# Abstrak\n# Pendahuluan\n# Metode\n# Hasil dan Pembahasan\n# Kesimpulan\n# Daftar Pustaka";
                break;
            case 'makalah':
                $mode = 'makalah'; $title = 'Judul Makalah Anda';
                $body = "judul: Judul Makalah\npenulis: Nama Penulis\nnim: NIM-12345\nprodi: Program Studi\nfakultas: Nama Fakultas\nuniversitas: Nama Universitas\nkota: Kota\ntahun: 2024\ndosen: Nama Dosen Pengampu";
                $skeleton = "# KATA PENGANTAR\n# BAB I PENDAHULUAN\n## 1.1 Latar Belakang\n## 1.2 Rumusan Masalah\n## 1.3 Tujuan\n# BAB II PEMBAHASAN\n# BAB III PENUTUP\n## 3.1 Kesimpulan\n## 3.2 Saran\n# DAFTAR PUSTAKA";
                break;
            default:
                $mode = 'document'; $title = 'Judul Dokumen Anda';
                $body = "judul: Judul Dokumen\npenulis: Nama Penulis\ntahun: 2024";
                $skeleton = "# Pendahuluan\n# Isi\n# Penutup";
                break;
        }

        $depth = $tier === 'large'
            ? "DEPTH REQUIREMENTS (mandatory):\n"
                . "- Setiap sub-bagian (## heading) berisi minimal 3 paragraf prosa nyata.\n"
                . "- Bagian teori/pustaka mengutip sumber bernama + tahun, mis. (Sugiyono, 2019).\n"
                . "- DAFTAR PUSTAKA (bila ada) berisi minimal 8 referensi berformat konsisten.\n"
                . "- Tulis sampai dokumen LENGKAP — jangan berhenti dini atau meringkas bab sisa.\n"
            : '';

        return "\n\n[SYSTEM REMINDER — ARTIFACT OUTPUT REQUIRED:\n"
            . "You MUST output your document EXCLUSIVELY inside an <antArtifact> block. "
            . "NEVER use ```markdown code blocks for the document.\n"
            . "Format your response exactly as:\n"
            . "<antArtifact type=\"text/markdown\" title=\"{$title}\">\n"
            . "---\n"
            . "mode: {$mode}\n"
            . $body . "\n"
            . "---\n"
            . $skeleton . "\n"
            . "</antArtifact>\n"
            . "Tulis paragraf UTUH di setiap bagian — tanpa placeholder, tanpa ringkasan satu kalimat.\n"
            . $depth
            . "Sebelum tag <antArtifact>, tulis 2-3 kalimat yang menjelaskan struktur dokumen dalam bahasa pengguna.]";
    }

    /**
     * The room's active markdown artifact (latest version). Shared by
     * buildArtifactContext() and the revision routing (issue #5).
     */
    protected function latestMarkdownArtifact(Conversation $conversation): ?MessageArtifact
    {
        return MessageArtifact::query()
            ->whereHas('message', fn ($q) => $q->where('conversation_id', $conversation->id))
            ->whereIn('language', ['markdown', 'md'])
            ->latest('id')
            ->first();
    }

    /**
     * In-message reminder for a revision turn (issue #5): the model receives the
     * FULL current document as the base and must return the COMPLETE revised
     * document in one <antArtifact> — applying the requested change to the whole
     * document, never shrinking it, never replying with loose chat text. The
     * artifact identifier is pinned server-side after parsing, so versioning is
     * guaranteed even if the model omits it.
     */
    protected function docRevisionReminder(MessageArtifact $artifact, string $tier): string
    {
        $base = (string) $artifact->content;
        // 32K window comfortably holds a bounded base + the rewrite.
        if (mb_strlen($base) > 16000) {
            $base = mb_substr($base, 0, 16000) . "\n\n[... dokumen dipotong untuk konteks; pertahankan seluruh isi aslinya di keluaran Anda]";
        }

        return "\n\n[SYSTEM REMINDER — REVISI DOKUMEN (WAJIB ARTIFACT):\n"
            . "Ini permintaan MEREVISI dokumen yang SUDAH ADA (judul: \"{$artifact->title}\"), bukan membuat dokumen baru dari nol dan BUKAN jawaban chat biasa.\n"
            . "Terapkan permintaan pengguna pada dokumen di bawah, lalu keluarkan SELURUH dokumen versi baru (LENGKAP dari awal sampai akhir) di dalam SATU blok:\n"
            . "<antArtifact type=\"text/markdown\" title=\"{$artifact->title}\"> ... </antArtifact>\n"
            . "ATURAN: JANGAN memangkas bagian yang tidak diminta diubah — salin utuh. JANGAN membalas hanya potongan/teks lepas. Pertahankan front-matter YAML di awal. "
            . ($tier === 'large' ? "Perdalam dengan paragraf nyata; jangan meringkas bab yang sudah ada.\n" : "")
            . "\n=== ISI DOKUMEN SAAT INI (BASIS REVISI) ===\n"
            . $base
            . "\n=== AKHIR ISI DOKUMEN ===]";
    }

    /**
     * In-message reminder for continuing/building from an uploaded document
     * (issue #6): the attachment content + grounding are already injected by the
     * provider (ResolvesAttachments); this tells the model to base its work on
     * THAT document and emit the result as an artifact, not regenerate from zero.
     */
    protected function docUploadContinuationReminder(string $tier): string
    {
        return "\n\n[SYSTEM REMINDER — LANJUTKAN DARI DOKUMEN YANG DIUNGGAH (WAJIB ARTIFACT):\n"
            . "Pengguna mengunggah sebuah dokumen (isinya ada di konteks di atas) dan meminta Anda melanjutkan/menyusun bagian darinya. "
            . "DASARKAN pekerjaan Anda pada isi dokumen itu — ikuti judul, gaya, penomoran bab, dan istilahnya. JANGAN menulis ulang dari nol dan JANGAN mengarang isi yang bertentangan dengan dokumen. "
            . "Jika pengguna meminta bab tertentu (mis. BAB IV), tulis bab itu secara LENGKAP agar nyambung dengan bab sebelumnya di dokumen. "
            . "Keluarkan hasilnya di dalam SATU blok <antArtifact type=\"text/markdown\" title=\"Judul Sesuai Dokumen\"> ... </antArtifact>. "
            . ($tier === 'large' ? "Tulis paragraf akademik yang utuh dan mendalam.\n" : "")
            . "Jika informasi yang diminta tidak ada di dokumen, nyatakan dengan jujur di chat (di luar artifact).]";
    }

    /**
     * The last few user turns joined oldest→newest — the working "request
     * context" for the skripsi pipeline, so chip answers and follow-up details
     * are read together with the original ask.
     */
    protected function recentUserRequestText(array $messages, int $take = 3, int $cap = 1500): string
    {
        $texts = [];
        foreach (array_reverse($messages) as $msg) {
            if (($msg['role'] ?? '') !== 'user') {
                continue;
            }
            $t = is_array($msg['content'])
                ? collect($msg['content'])->where('type', 'text')->pluck('text')->implode(' ')
                : (string) ($msg['content'] ?? '');
            if (trim($t) !== '') {
                $texts[] = trim($t);
            }
            if (count($texts) >= $take) {
                break;
            }
        }

        return mb_substr(implode("\n", array_reverse($texts)), 0, $cap);
    }

    /**
     * Should the pipeline ask its one clarifying question (research method +
     * cover data) before writing? Never asks twice in a conversation, and
     * skips entirely when the user already stated a method or opted out.
     */
    protected function needsSkripsiClarification(array $messages): bool
    {
        $recent = $this->recentUserRequestText($messages);
        if (preg_match('/kuantitatif|kualitatif|campuran|mixed ?method|eksperimen|deskriptif|surv[ea]i|survey|studi kasus|etnografi|fenomenologi|\br ?& ?d\b|\bptk\b|asumsi terbaik|langsung tulis/i', $recent)) {
            return false;
        }
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'assistant'
                && str_contains((string) ($msg['content'] ?? ''), 'Metode penelitian apa yang ingin Anda pakai')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Persist a per-stage pipeline report as its OWN assistant message and
     * return the SSE-shaped event the client turns into a separate bubble.
     * The run does not pause — reports are informational.
     */
    protected function progressEvent(Conversation $conversation, string $model, string $content, ?string $thinking = null): array
    {
        $thinking = ($thinking !== null && trim($thinking) !== '') ? trim($thinking) : null;
        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $content,
            'model' => $model,
            // Each stage keeps ITS OWN reasoning, openable on its report bubble
            // forever — nothing is merged into one giant panel at the end.
            'thinking' => $thinking,
        ]);

        return ['type' => 'progress', 'data' => [
            'message_id' => $msg->id,
            'content' => $content,
            'model' => $model,
            'thinking' => $thinking,
        ]];
    }

    /**
     * GBNF grammar (perubahan.md #3) for single-shot document requests on the
     * local GGUF engine: optional reasoning block, a short chat preamble, then
     * EXACTLY ONE <antArtifact type="text/markdown"> block, nothing after it.
     * Sampling physically cannot put the document anywhere else — the classic
     * small-model failures (```markdown fences, document stuck in chat) become
     * impossible instead of merely discouraged.
     * "</" is disallowed inside free text so the closing tags stay unambiguous;
     * Markdown documents don't need closing HTML tags.
     */
    protected function docArtifactGrammar(): string
    {
        return <<<'GBNF'
root ::= think? pre artifact ws
think ::= ("<think>" | "<thinking>" | "<sim_thinking>") free ("</think>" | "</thinking>" | "</sim_thinking>") ws
pre ::= [^<]*
artifact ::= "<antArtifact type=\"text/markdown\" title=\"" title "\">" free "</antArtifact>"
title ::= [^"\n]+
free ::= fchar*
fchar ::= [^<] | "<" [^/]
ws ::= [ \t\r\n]*
GBNF;
    }

    /**
     * Grammar for auto-continue rounds: the model may only extend the document
     * body and close </antArtifact> — no restarting the artifact, no chatting.
     */
    protected function docContinuationGrammar(): string
    {
        return <<<'GBNF'
root ::= free "</antArtifact>" ws
free ::= fchar*
fchar ::= [^<] | "<" [^/]
ws ::= [ \t\r\n]*
GBNF;
    }

    /**
     * Grammar for the skripsi metadata stage: forces exactly the nine YAML
     * lines the academic front-matter needs, in order, so even a 0.6B model
     * produces parseable output.
     */
    protected function skripsiMetaGrammar(): string
    {
        return <<<'GBNF'
root ::= "judul: " line "penulis: " line "nim: " line "prodi: " line "fakultas: " line "universitas: " line "kota: " line "tahun: " [0-9] [0-9] [0-9] [0-9] "\n" "pembimbing: " line
line ::= [^\r\n]+ "\n"
GBNF;
    }

    /**
     * The canonical skripsi chapter plan: [label, opening heading, content guide]
     * for each section. Index 0 = Pengesahan/Abstrak, index 1..5 = BAB I..V,
     * index 6 = Daftar Pustaka. Shared by the fresh per-chapter pipeline and the
     * "lanjutkan BAB N" continuation so both produce identical chapter shapes.
     */
    protected function skripsiChapterPlan(): array
    {
        return [
            ['Halaman Pengesahan & Abstrak', 'HALAMAN PENGESAHAN',
                "Tulis tiga bagian berurutan: '# HALAMAN PENGESAHAN' (judul, nama+NIM, tabel tanda tangan Pembimbing/Penguji), '# ABSTRAK' (1 paragraf ≤250 kata: latar belakang singkat → tujuan → metode → hasil, diakhiri baris '**Kata Kunci:** kata1, kata2, kata3'), dan '# ABSTRACT' (terjemahan Inggris ABSTRAK ditulis *italic*, diakhiri '**Keywords:** ...')."],
            ['BAB I', 'BAB I PENDAHULUAN',
                "Sub-bab: ## 1.1 Latar Belakang (minimal 4 paragraf), ## 1.2 Rumusan Masalah, ## 1.3 Tujuan Penelitian, ## 1.4 Batasan Masalah — WAJIB bentuk paragraf mengalir (BUKAN daftar/bullet/poin), 2–4 paragraf, ## 1.5 Manfaat Penelitian, ## 1.6 Metodologi Penelitian (ringkas), ## 1.7 Sistematika Penulisan. Setiap sub-bab minimal 4-6 paragraf akademik yang tebal & spesifik (kecuali 1.7 boleh lebih ringkas)."],
            ['BAB II', 'BAB II TINJAUAN PUSTAKA',
                "Sub-bab: ## 2.1 Penelitian Terdahulu (bahas minimal 5 penelitian dengan nama penulis dan tahun), ## 2.2 Landasan Teori dengan sub-sub-bab bernomor (### 2.2.1 dst) per konsep inti, ## 2.3 Kerangka Pemikiran. Kutip teori dari penulis bernama dengan tahun, contoh: (Sugiyono, 2019). WAJIB menyertakan minimal 2 tabel Markdown pada bab ini (misal tabel perbandingan penelitian terdahulu dan tabel definisi). WAJIB menyertakan minimal 1 diagram dalam blok ```mermaid (misal flowchart Kerangka Pemikiran)."],
            ['BAB III', 'BAB III METODOLOGI PENELITIAN',
                "Sub-bab: ## 3.1 Jenis Penelitian, ## 3.2 Populasi dan Sampel / Sumber Data, ## 3.3 Teknik Pengumpulan Data, ## 3.4 Instrumen Penelitian, ## 3.5 Teknik Analisis Data. Jelaskan metode secara konkret dan operasional, bukan definisi umum saja. WAJIB menyertakan minimal 2 tabel Markdown pada bab ini (misal tabel populasi/sampel dan kisi-kisi instrumen). WAJIB menyertakan minimal 1 diagram dalam blok ```mermaid (misal alur penelitian, atau arsitektur sistem/use-case/ERD jika relevan)."],
            ['BAB IV', 'BAB IV HASIL DAN PEMBAHASAN',
                "Sub-bab: ## 4.1 Gambaran Umum Objek Penelitian, ## 4.2 Hasil Penelitian, ## 4.3 Pembahasan (analisis yang mengaitkan hasil dengan teori BAB II). Ini bab terpanjang — tulis analisis nyata, bukan pengulangan BAB I. WAJIB menyertakan minimal 2 tabel Markdown pada bab ini (data hasil temuan). Tambahkan diagram dalam blok ```mermaid jika relevan untuk visualisasi hasil."],
            ['BAB V', 'BAB V PENUTUP',
                "Sub-bab: ## 5.1 Kesimpulan (menjawab rumusan masalah poin demi poin), ## 5.2 Saran (untuk praktisi dan untuk penelitian selanjutnya)."],
            ['Daftar Pustaka', 'DAFTAR PUSTAKA',
                "Tulis '# DAFTAR PUSTAKA' berisi minimal 12 referensi berformat konsisten dan diurutkan alfabetis, selaras dengan penulis/tahun yang dikutip di bab-bab sebelumnya."],
        ];
    }

    /**
     * Highest BAB number present in a document's markdown (0 if none). Used to
     * decide which chapter a "lanjutkan" turn should ADD next.
     */
    protected function highestBabInDocument(string $content): int
    {
        if (!preg_match_all('/^#\s+BAB\s+([IVXLCDM]+|\d{1,2})\b/mi', $content, $mm)) {
            return 0;
        }
        $max = 0;
        foreach ($mm[1] as $tok) {
            $n = ctype_digit($tok) ? (int) $tok : $this->detectBabReference('bab ' . $tok);
            if ($n !== null && $n > $max) {
                $max = $n;
            }
        }
        return $max;
    }

    /**
     * Patch 8: Melanjutkan draf skripsi yang diunggah pengguna.
     * Mengekstrak teks, membuang front-matter lama, dan melanjutkan penulisan
     * dalam satu artifact Markdown baru.
     */
    protected function streamUploadSkripsiContinuation(Conversation $conversation, array $messages, string $model, string $tier, string $stopKey): \Generator
    {
        $latestUserMsg = null;
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $latestUserMsg = $messages[$i];
                break;
            }
        }
        
        $requestText = $this->recentUserRequestText($messages);
        $attachments = $latestUserMsg['attachments'] ?? [];
        
        // Resolve attachment parts to get full text
        $parts = $this->resolveAttachmentParts($attachments, $requestText, 100000);
        $uploadText = '';
        foreach ($parts as $part) {
            if ($part['kind'] === 'text') {
                $uploadText .= $part['text'] . "\n\n";
            }
        }
        $uploadText = trim($uploadText);
        
        if ($uploadText === '') {
             yield "\n\n(Mohon maaf, saya tidak dapat membaca teks dari dokumen yang Anda unggah. Pastikan formatnya PDF, DOCX, TXT, atau Markdown.)";
             return;
        }

        $isSkripsiDoc = (bool) preg_match('/^\s*---[\s\S]*?\bmode:\s*(skripsi|tesis)\b/i', $uploadText)
                        || (bool) preg_match('/^#\s+BAB\s+[IVX]/mi', $uploadText);

        if (!$isSkripsiDoc) {
             // Fallback to normal upload continuation if not skripsi
             $genOptions = ['grammar' => $this->docArtifactGrammar()];
             $messagesForAi = array_merge($messages, [
                 ['role' => 'system', 'content' => $this->docUploadContinuationReminder($tier)]
             ]);
             foreach ($this->aiService->streamResponse($messagesForAi, $model, $genOptions) as $chunk) {
                 if (Cache::get($stopKey)) break;
                 if (is_array($chunk) && ($chunk['type'] ?? '') === 'thinking') yield $chunk;
                 elseif (is_string($chunk)) yield $chunk;
             }
             return;
        }

        $highest = $this->highestBabInDocument($uploadText);
        $targetBab = $highest + 1;
        
        if ($targetBab > 5) {
             yield "\n\nDokumen sudah memiliki kelima BAB inti (I–V). Anda bisa meminta revisi bab tertentu.";
             return;
        }

        $plan = $this->skripsiChapterPlan();
        $targetPlan = null;
        $ciIndex = -1;
        foreach ($plan as $ci => $p) {
             if (preg_match('/^BAB\s+' . $this->toRoman($targetBab) . '\b/i', $p[1])) {
                 $targetPlan = $p;
                 $ciIndex = $ci;
                 break;
             }
        }

        if (!$targetPlan) {
            return;
        }

        [$label, $heading, $guide] = $targetPlan;
        
        $meta = $this->collectSkripsiMeta($requestText, $model);
        // Clean markdown from attachment so it can be appended to
        $existing = rtrim($uploadText);

        yield $this->progressEvent($conversation, $model,
            "🚀 **Melanjutkan dokumen skripsi yang diunggah.**\n\n"
            . "Saya telah membaca dokumen Anda yang berisi materi hingga BAB " . $this->toRoman($highest) . ". Saya akan menulis {$heading} dan menggabungkannya menjadi dokumen penuh utuh..."
        );

        // Pre-append the original doc wrapped in artifact
        $titleAttr = htmlspecialchars($meta['judul'] ?? 'Skripsi', ENT_QUOTES);
        yield "<antArtifact type=\"text/markdown\" title=\"{$titleAttr}\">\n";
        yield $existing . "\n\n";

        // Summary of what is written so far
        $summary = "RINGKASAN DARI DOKUMEN YANG DIUNGGAH SEBELUMNYA:\n";
        $outlineLines = $this->chapterOutlineLines($existing);
        $summary .= count($outlineLines) > 0 ? implode("\n", array_slice($outlineLines, -50)) : "(Belum ada isi)";
        
        $maxTokensPerChapter = $tier === 'large' ? 10240 : 8192;
        $gen = $this->generateChapterBody($model, $maxTokensPerChapter, $stopKey, $requestText, $meta, $summary, $label, $heading, $guide, false);
        
        $chapterText = '';
        foreach ($gen as $ev) {
             if (is_array($ev)) {
                 if (($ev['type'] ?? '') === 'thinking') {
                     yield $ev;
                 }
                 continue;
             }
        }
        
        $chapterText = (string) $gen->getReturn();
        yield $chapterText;
        yield "\n</antArtifact>";

        $nextBab = $targetBab + 1;
        if ($nextBab <= 5 && !Cache::get($stopKey)) {
             yield "\n\n**{$heading}** telah selesai ditambahkan ke dalam dokumen unggahan. Lanjut ke BAB berikutnya?";
             yield ['type' => 'suggestions', 'data' => [
                 "Lanjutkan BAB " . $this->toRoman($nextBab)
             ]];
        }
    }
    
    protected function toRoman(int $num): string {
        $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
        return $map[$num] ?? 'I';
    }

    /**
     * "lanjutkan BAB N" continuation (issue #4): APPEND the next chapter(s) to the
     * SAME skripsi artifact instead of asking a small model to regenerate the whole
     * document (which produced a brand-new ngaco doc). The existing content — YAML
     * front-matter, cover, and every chapter already written — is preserved
     * verbatim; only the requested new chapter(s) are generated and appended, then
     * the whole document is re-emitted in ONE artifact that the save logic pins to
     * the original identifier (a new version of the same document).
     */
    protected function streamSkripsiContinuation(Conversation $conversation, string $model, string $tier, string $stopKey, MessageArtifact $artifact, int $fromBab, int $toBab, string $request): \Generator
    {
        $existing = rtrim((string) $artifact->content);
        $judul = (string) $artifact->title;
        // Reuse the title from front-matter/artifact for chapter context.
        $meta = ['judul' => $judul !== '' ? $judul : 'Skripsi'];

        $plan = $this->skripsiChapterPlan();
        $fromBab = max(1, $fromBab);
        $toBab = min(5, $toBab);

        yield $this->progressEvent($conversation, $model,
            "🚀 **Melanjutkan dokumen yang sama — bukan membuat baru.**\n\n"
            . "Judul dipertahankan: _{$meta['judul']}_. Saya menambahkan "
            . ($fromBab === $toBab ? "**BAB " . $this->intToRoman($fromBab) . "**" : "**BAB " . $this->intToRoman($fromBab) . "–" . $this->intToRoman($toBab) . "**")
            . " ke dokumen, isi lama tidak diubah.");

        // Rolling summary seeded from the existing document's outline so the new
        // chapter stays consistent with what's already there.
        $summary = '';
        foreach ((is_array($artifact->outline_json) ? $artifact->outline_json : MessageArtifact::extractOutline($existing)) as $h) {
            if (($h['level'] ?? 1) <= 2) {
                $summary .= '• ' . trim((string) ($h['text'] ?? '')) . "\n";
            }
        }

        // Re-open the artifact with the FULL existing content, then append.
        $titleAttr = htmlspecialchars($meta['judul'], ENT_QUOTES);
        yield "<antArtifact type=\"text/markdown\" title=\"{$titleAttr}\">\n" . $existing . "\n\n";

        $newHeadings = [];
        for ($bab = $fromBab; $bab <= $toBab; $bab++) {
            if (Cache::get($stopKey)) {
                break;
            }
            [$label, $heading, $guide] = $plan[$bab];
            yield ['type' => 'thinking', 'text' => "Menulis {$heading} untuk melanjutkan dokumen…\n", 'transient' => true];

            $gen = $this->generateChapterBody($model, $tier === 'large' ? 8192 : 6144, $stopKey, $request, $meta, $summary, $label, $heading, $guide, false);
            $chapterThinking = '';
            foreach ($gen as $ev) {
                if (is_array($ev) && ($ev['type'] ?? '') === 'thinking') {
                    $chapterThinking .= (string) ($ev['text'] ?? '');
                }
                yield $ev;
            }
            $chapterText = (string) $gen->getReturn();

            $outline = $this->chapterOutlineLines($chapterText);
            $summary .= "• {$heading}:\n"
                . ($outline ? implode("\n", array_map(fn ($l) => '  ' . $l, $outline)) : '  (sudah ditulis)') . "\n";
            $newHeadings[] = $heading;

            foreach (str_split($chapterText . "\n\n", 400) as $piece) {
                yield $piece;
            }

            yield $this->progressEvent($conversation, $model,
                "✅ **{$heading} selesai dan ditambahkan ke dokumen.**\n\n"
                . ($outline ? "Isi ringkasnya:\n" . implode("\n", array_map(fn ($l) => '- ' . $l, array_slice($outline, 0, 6))) : 'Bagian ini sudah masuk ke dokumen.'),
                $chapterThinking);
        }

        yield "</antArtifact>";

        if (!Cache::get($stopKey)) {
            $added = implode(', ', $newHeadings);
            $nextBab = $toBab + 1;
            yield "\n\n---\n\n## 📖 Ringkasan Kelanjutan\n\n"
                . "Saya menambahkan **{$added}** ke dokumen **\"{$meta['judul']}\"** yang sudah ada — judul, halaman sampul, dan bab-bab sebelumnya tidak diubah sama sekali, hanya bab baru yang ditambahkan.";
            if ($nextBab <= 5) {
                $nextRoman = $this->intToRoman($nextBab);
                yield "\n\nUntuk meneruskan, ketik **\"lanjutkan BAB {$nextRoman}\"**.";
                yield ['type' => 'suggestions', 'data' => [
                    "Lanjutkan ke BAB {$nextRoman}",
                    "Perdalam " . end($newHeadings),
                    'Tambah tabel dan diagram pendukung',
                    'Buat versi PDF-nya',
                ]];
            } else {
                yield "\n\nSemua bab inti (I–V) sudah ada. Anda bisa menambah **Daftar Pustaka** atau memperdalam bab tertentu.";
                yield ['type' => 'suggestions', 'data' => [
                    'Tambahkan Daftar Pustaka',
                    'Perdalam BAB IV (analisis & pembahasan)',
                    'Perbaiki struktur/format dokumen',
                    'Buat versi PDF-nya',
                ]];
            }
        }
    }

    /**
     * Per-chapter skripsi pipeline for local GGUF models (perubahan.md #2).
     *
     * Instead of asking a small model to write a 100-page document in one
     * breath (it runs out of tokens or coherence mid-way), the document is
     * produced in stages: one constrained metadata call, then one generation
     * per chapter. Each chapter call gets a compact outline of what was
     * already written (headings + first sentences), so chapters stay
     * consistent without re-feeding whole chapters into the small context.
     * The pieces are stitched into ONE <antArtifact> block that we open and
     * close ourselves — the model never has to emit artifact tags at all,
     * eliminating that failure mode entirely for skripsi.
     *
     * Yields the same chunk shapes the provider stream does (strings for
     * content, ['type' => 'thinking'] arrays), so the main stream() loop
     * consumes it unchanged.
     */
    protected function streamSkripsiPerChapter(Conversation $conversation, array $messages, string $model, string $tier, string $stopKey, ?int $babLimit = null, ?int $singleBabTarget = null): \Generator
    {
        // Working request = last few user turns joined, so a chip answer
        // ("Metode kuantitatif") still carries the original skripsi ask.
        $request = $this->recentUserRequestText($messages);

        // ── Stage 0: ask before groping (clarify chips, one time only) ───────
        // The research method reshapes BAB III–IV entirely; asking one tap-able
        // question beats guessing. The reply also lets the user drop cover data
        // (nama, NIM, universitas) in the same message.
        if ($this->needsSkripsiClarification($messages)) {
            $explicitTitle = $this->detectUserProvidedTitle($request);
            $titleMsg = $explicitTitle ? "Judul yang akan saya pakai: _{$explicitTitle}_ (tidak akan saya ubah).\n\n" : "";

            yield "Sebelum saya menyusun skripsinya, satu hal penting dulu:\n\n"
                . $titleMsg
                . "**Metode penelitian apa yang ingin Anda pakai?** Pilihan ini menentukan seluruh isi BAB III (metodologi) dan cara BAB IV menyajikan hasil, jadi lebih baik saya pastikan daripada menebak.\n\n"
                . "Silakan pilih salah satu tombol di bawah — atau ketik jawaban sendiri, sekaligus boleh sebutkan **nama Anda, NIM, universitas, program studi, dan nama pembimbing** agar halaman judulnya akurat.";
            yield ['type' => 'suggestions', 'data' => [
                'Metode kuantitatif (data angka & statistik)',
                'Metode kualitatif (wawancara & observasi)',
                'Metode campuran (mixed methods)',
                'Langsung tulis saja dengan asumsi terbaik',
            ]];

            return;
        }

        // Instant acknowledgement bubble BEFORE the (slow) metadata call, so a
        // chip click gets visible feedback immediately instead of silence.
        yield $this->progressEvent($conversation, $model,
            "🚀 **Siap — saya mulai menyusun skripsinya sekarang.**\n\n"
            . "Prosesnya 8 tahap: persiapan judul & metadata, lalu 7 bagian dokumen (Pengesahan & Abstrak, BAB I–V, Daftar Pustaka). "
            . "Setiap satu tahap selesai, saya melapor di chat ini — Anda tidak perlu menekan apa pun sampai dokumen final jadi.\n\n"
            . "_Tahap 1/8 — menentukan judul dan metadata dokumen…_");

        // ── Stage 1: metadata for the cover / front-matter ───────────────────
        yield ['type' => 'thinking', 'text' => "Tahap 1/8 — menyusun judul dan metadata dokumen…\n", 'transient' => true];
        $meta = $this->collectSkripsiMeta($request, $model);

        $frontMatter = "---\n"
            . "mode: skripsi\n"
            . "judul: {$meta['judul']}\n"
            . "penulis: {$meta['penulis']}\n"
            . "nim: {$meta['nim']}\n"
            . "prodi: {$meta['prodi']}\n"
            . "fakultas: {$meta['fakultas']}\n"
            . "universitas: {$meta['universitas']}\n"
            . "kota: {$meta['kota']}\n"
            . "tahun: {$meta['tahun']}\n"
            . "pembimbing: {$meta['pembimbing']}\n"
            . "---\n\n";

        // Separate report bubble: preparation done, run continues unattended.
        yield $this->progressEvent($conversation, $model,
            "📋 **Tahap 1/8 selesai — persiapan dokumen.**\n\n"
            . "Judul ditetapkan: _{$meta['judul']}_\n"
            . "Penulis: {$meta['penulis']} · {$meta['prodi']}, {$meta['universitas']}\n\n"
            . "Saya akan menulis dokumen ini bab demi bab (7 bagian) dan melapor di chat setiap satu bagian selesai. Proses berjalan terus tanpa perlu Anda tunggu-tekan apa pun sampai dokumen final jadi.");

        $titleAttr = htmlspecialchars($meta['judul'], ENT_QUOTES);
        yield "<antArtifact type=\"text/markdown\" title=\"{$titleAttr}\">\n" . $frontMatter;

        // [nama tahap, heading pembuka, panduan isi] — shared with the
        // "lanjutkan BAB N" continuation via skripsiChapterPlan().
        $chapters = $this->skripsiChapterPlan();

        // Scoped skripsi ("sampai bab N"): write Pengesahan/Abstrak (index 0) + BAB I..N
        // (index 1..N) only, with REAL content — then stop. Skips later chapters +
        // Daftar Pustaka. N came from detectBabReference; index N maps to BAB N.
        $singleBab = ($singleBabTarget !== null && $singleBabTarget >= 1 && $singleBabTarget <= 6);
        $partialSkripsi = false;
        
        // Track the true original index of each chapter to safely detect the front chapter
        $chaptersWithOriginalIndex = [];
        foreach ($chapters as $idx => $chapter) {
            $chaptersWithOriginalIndex[] = ['original_index' => $idx, 'chapter' => $chapter];
        }

        if ($singleBab) {
            $chaptersWithOriginalIndex = [ $chaptersWithOriginalIndex[$singleBabTarget] ];
        } else {
            $partialSkripsi = ($babLimit !== null && $babLimit >= 1 && $babLimit < 5);
            if ($partialSkripsi) {
                $chaptersWithOriginalIndex = array_slice($chaptersWithOriginalIndex, 0, $babLimit + 1);
            }
        }

        // Depth over speed (user directive): give each chapter a generous ceiling
        // and lean on the continuation loop rather than cutting the answer short.
        $maxTokensPerChapter = $tier === 'large' ? 10240 : 8192;
        $summary = '';
        $chapterOutlines = [];
        $totalChapters = count($chaptersWithOriginalIndex);

        foreach ($chaptersWithOriginalIndex as $ci => $item) {
            if (Cache::get($stopKey)) {
                break;
            }
            $originalIdx = $item['original_index'];
            [$label, $heading, $guide] = $item['chapter'];
            
            $stage = $ci + 2; // stage 1 was metadata
            yield ['type' => 'thinking', 'text' => "Tahap {$stage}/8 — menulis {$heading}…\n", 'transient' => true];

            // Generate the chapter body (initial pass + continuation rounds +
            // fill for any sub-bab the model skipped). Shared with the
            // "lanjutkan BAB N" continuation so both always produce a COMPLETE
            // chapter (fixes BAB I stopping at 1.4).
            $gen = $this->generateChapterBody($model, $maxTokensPerChapter, $stopKey, $request, $meta, $summary, $label, $heading, $guide, $originalIdx === 0);
            $chapterThinking = '';
            foreach ($gen as $ev) {
                if (is_array($ev) && ($ev['type'] ?? '') === 'thinking') {
                    $chapterThinking .= (string) ($ev['text'] ?? '');
                }
                yield $ev;
            }
            $chapterText = (string) $gen->getReturn();

            // Language guard: BAB I–V must be Indonesian. A small model drifts
            // into English right after the (legitimately English) ABSTRACT —
            // detect that and rewrite the chapter in Indonesian once.
            if ($ci >= 1 && $ci <= 5 && !Cache::get($stopKey) && $this->looksEnglish($chapterText)) {
                yield ['type' => 'thinking', 'text' => "Bagian {$heading} terdeteksi berbahasa Inggris — menulis ulang dalam Bahasa Indonesia…\n", 'transient' => true];
                $fixed = '';
                foreach ($this->aiService->streamResponse([
                    ['role' => 'system', 'content' => 'Anda penerjemah akademik profesional. Terjemahkan dokumen Markdown berikut ke Bahasa Indonesia baku akademik. Pertahankan SEMUA heading (#/##/###), penomoran sub-bab, dan struktur persis sama. Keluarkan HANYA hasil terjemahannya, tanpa komentar apa pun.'],
                    ['role' => 'user', 'content' => $chapterText],
                ], $model, ['max_tokens' => $maxTokensPerChapter]) as $chunk) {
                    if (Cache::get($stopKey)) {
                        break;
                    }
                    if (is_string($chunk)) {
                        $fixed .= $chunk;
                    }
                }
                $fixed = $this->cleanChapterText($fixed, $heading);
                // Only swap in the rewrite when it is genuinely Indonesian and
                // did not lose a big part of the chapter to truncation.
                if (!$this->looksEnglish($fixed) && strlen($fixed) >= (int) (strlen($chapterText) * 0.5)) {
                    $chapterText = $fixed;
                }
            }

            $outline = $this->chapterOutlineLines($chapterText);
            $chapterOutlines[] = ['heading' => $heading, 'lines' => $outline];
            // Rolling context for the NEXT chapters: drop ABSTRACT lines — that
            // English text is what dragged BAB I into English in the first place.
            $contextOutline = array_values(array_filter($outline, fn ($l) => !preg_match('/^abstract\b/i', trim($l))));
            $summary .= "• {$heading}:\n"
                . ($contextOutline ? implode("\n", array_map(fn ($l) => '  ' . $l, $contextOutline)) : '  (sudah ditulis)') . "\n";

            // Stream the finished chapter out in typewriter-sized bites.
            foreach (str_split($chapterText . "\n\n", 400) as $piece) {
                yield $piece;
            }

            // Separate report bubble in chat — informational only, the run
            // continues immediately with the next chapter.
            $isLast = $ci === $totalChapters - 1;
            $reportBody = $outline
                ? "Isi ringkasnya:\n" . implode("\n", array_map(fn ($l) => '- ' . $l, array_slice($outline, 0, 6)))
                : 'Bagian ini sudah masuk ke dokumen.';
            yield $this->progressEvent($conversation, $model,
                "✅ **Tahap {$stage}/8 — {$heading} selesai.**\n\n{$reportBody}\n\n"
                . ($isLast ? '_Semua bagian selesai — merapikan dokumen final…_' : '_Lanjut menulis bagian berikutnya…_'),
                $chapterThinking);

            if (Cache::get($stopKey)) {
                break; // close the artifact gracefully with what we have
            }
        }

        yield "</antArtifact>";

        // The user asked for a THOROUGH debrief after the artifact: what was
        // built, how, per-chapter contents, and what they can do next.
        if (!Cache::get($stopKey)) {
            yield "\n\n" . $this->buildSkripsiExplanation($meta, $chapterOutlines);
            if ($partialSkripsi) {
                $nextRoman = $this->intToRoman($babLimit + 1);
                yield "\n\n_Sesuai permintaan, saya menulisnya **sampai BAB {$babLimit}** dulu (dengan isi lengkap, bukan kerangka). Kalau mau dilanjutkan, ketik **\"lanjutkan BAB {$nextRoman}\"**._";
                yield ['type' => 'suggestions', 'data' => [
                    "Lanjutkan ke BAB {$nextRoman}",
                    "Perdalam BAB " . $this->intToRoman($babLimit),
                    'Ubah judul atau data halaman sampul',
                    'Buat versi PDF-nya',
                ]];
            } else {
                yield ['type' => 'suggestions', 'data' => [
                    'Perdalam BAB IV (analisis & pembahasan)',
                    'Tambah tabel dan diagram pendukung',
                    'Ubah judul atau data halaman sampul',
                    'Buat versi PDF-nya',
                ]];
            }
        }
    }

    /**
     * The long-form debrief appended after the assembled skripsi artifact —
     * structure, per-chapter contents, metadata used, academic conventions
     * applied, and concrete next actions. Deterministic (built from the
     * pipeline's own outlines), so it is always complete and accurate even
     * on the smallest local models.
     */
    protected function buildSkripsiExplanation(array $meta, array $chapterOutlines): string
    {
        $struktur = '';
        foreach ($chapterOutlines as $c) {
            $struktur .= "**{$c['heading']}**\n";
            $struktur .= $c['lines']
                ? implode("\n", array_map(fn ($l) => '- ' . $l, $c['lines'])) . "\n\n"
                : "- (bagian ini ditulis ringkas)\n\n";
        }

        return "---\n\n"
            . "## 📖 Penjelasan Lengkap Pekerjaan Saya\n\n"
            . "Dokumen skripsi **\"{$meta['judul']}\"** sudah selesai disusun — silakan buka di panel artifact. Berikut penjelasan detail tentang apa yang saya kerjakan, bagaimana caranya, dan mengapa demikian:\n\n"
            . "### 1. Cara dokumen ini disusun\n"
            . "Saya tidak menulisnya dalam satu tarikan napas (cara itu membuat dokumen panjang terpotong di tengah). Dokumen disusun **bab demi bab dalam 8 tahap**: tahap pertama menetapkan judul dan metadata halaman sampul, lalu tujuh tahap penulisan — halaman pengesahan & abstrak, BAB I sampai BAB V, dan daftar pustaka. Setiap bab baru menerima ringkasan bab-bab sebelumnya sebagai konteks, sehingga istilah, alur argumen, dan rujukan antar-bab tetap konsisten dari awal sampai akhir.\n\n"
            . "### 2. Struktur dokumen dan isi tiap bagian\n{$struktur}"
            . "### 3. Metadata halaman sampul yang dipakai\n"
            . "- **Judul:** {$meta['judul']}\n"
            . "- **Penulis:** {$meta['penulis']} (NIM {$meta['nim']})\n"
            . "- **Institusi:** {$meta['prodi']}, {$meta['fakultas']}, {$meta['universitas']}\n"
            . "- **Kota/Tahun:** {$meta['kota']}, {$meta['tahun']}\n"
            . "- **Pembimbing:** {$meta['pembimbing']}\n\n"
            . "Data yang tidak Anda sebutkan saya isi dengan placeholder yang wajar — cukup beri tahu nilai yang benar dan saya perbarui halaman sampulnya.\n\n"
            . "### 4. Standar akademik yang diterapkan\n"
            . "- Halaman **cover dan DAFTAR ISI dibuat otomatis** oleh sistem dari front-matter dan struktur heading, jadi tidak perlu ditulis manual.\n"
            . "- **ABSTRAK** satu paragraf ≤ 250 kata dengan kata kunci, plus **ABSTRACT** versi bahasa Inggris.\n"
            . "- Kutipan memakai pola penulis-tahun di BAB II dan dirangkum dalam **DAFTAR PUSTAKA** yang urut alfabetis.\n"
            . "- Penomoran sub-bab baku (1.1, 2.2.1, dst.) sehingga rapi saat diekspor ke **PDF/DOCX** dengan penomoran halaman akademik.\n\n"
            . "### 5. Yang bisa Anda lakukan sekarang\n"
            . "- **Meninjau per bab** — sebutkan bagian yang kurang pas, saya revisi secara tertarget tanpa menulis ulang semuanya.\n"
            . "- **Memperdalam** — misal \"perdalam BAB IV\" atau \"tambah 5 referensi di BAB II\".\n"
            . "- **Mengekspor** — dokumen siap diunduh sebagai PDF/DOCX dari panel artifact.\n\n"
            . "Gunakan tombol saran di bawah kolom chat, atau langsung ketik permintaan Anda.";
    }

    /**
     * Detect explicit title from the user request.
     */
    protected function detectUserProvidedTitle(string $request): ?string
    {
        // 1. DAHULUKAN pola berlabel yang DENGAN tanda kutip
        if (preg_match('/\bjudul(?:\s*(?:skripsi|nya))?\s*[:\-]\s*["“](.+?)["”]/i', $request, $m)) {
            return trim($m[1]);
        }
        
        // 2. Pola "dari judul ini …" (dengan kutip diprioritaskan)
        if (preg_match('/\bdari\s+judul\s+(?:ini|berikut)\b[:\-]?\s*["“](.+?)["”]/i', $request, $m)) {
            return trim($m[1]);
        }
        
        // 3. Judul polos dalam tanda kutip (tanpa awalan label eksplisit)
        if (preg_match('/["“](.{15,140}?)["”]/', $request, $m)) {
            return trim($m[1]);
        }

        // 4. Pola berlabel TANPA tanda kutip (berhenti di pemisah metadata/kalimat)
        if (preg_match('/\bjudul(?:\s*(?:skripsi|nya))?\s*[:\-]\s*(.+?)(?=\s*(?:\b(?:metode|penulis|nim|prodi|sampai\s+bab)\b|[.!?,]|$|\n))/i', $request, $m)) {
            return trim($m[1]);
        }
        
        // 5. Pola "dari judul ini …" tanpa kutip
        if (preg_match('/\bdari\s+judul\s+(?:ini|berikut)\b[:\-]?\s*(.+?)(?=\s*(?:\b(?:buatkan|buat|susun|tolong)\b|[.!?,]|$|\n))/i', $request, $m)) {
            return trim($m[1]);
        }

        // Fallback frasa judul
        $judulFallback = trim((string) preg_replace('/^(tolong|mohon|coba)?\s*(buatkan|buat|tuliskan|tulis|generate|susun(kan)?)\b[\s,:]*/i', '', $request));
        if (strlen($judulFallback) >= 15 && strlen($judulFallback) <= 140) {
            if (preg_match('/\b(sistem|aplikasi|pengembangan|analisis|rancang bangun|perancangan|implementasi)\b/i', $judulFallback)) {
                return $judulFallback;
            }
        }

        return null;
    }

    /**
     * Stage-1 helper: one small constrained call that turns the user request
     * into the nine front-matter fields; safe defaults cover anything missing.
     */
    protected function collectSkripsiMeta(string $request, string $model): array
    {
        $explicitTitle = $this->detectUserProvidedTitle($request);
        $judulFallback = trim((string) preg_replace('/^(tolong|mohon|coba)?\s*(buatkan|buat|tuliskan|tulis|generate|susun(kan)?)\b[\s,:]*/i', '', $request));
        
        // Default set initial
        $defaults = [
            'judul' => \Illuminate\Support\Str::limit($explicitTitle ?: ($judulFallback !== '' ? $judulFallback : 'Skripsi'), 120, ''),
            'penulis' => 'Nama Penulis',
            'nim' => '00000000',
            'prodi' => 'Program Studi',
            'fakultas' => 'Fakultas',
            'universitas' => 'Universitas',
            'kota' => 'Kota',
            'tahun' => (string) now()->year,
            'pembimbing' => 'Nama Pembimbing',
        ];

        try {
            $prompt = "Dari permintaan skripsi berikut, isi metadata dokumen. Gunakan informasi yang disebut pengguna; jika tidak disebut, buat placeholder Indonesia yang wajar (JANGAN menulis 'tidak diketahui'). Judul harus berupa judul skripsi akademik yang baik dan spesifik.\n\n"
                . "Permintaan: " . \Illuminate\Support\Str::limit($request, 1200) . "\n\n"
                . "Keluarkan HANYA 9 baris YAML dengan kunci: judul, penulis, nim, prodi, fakultas, universitas, kota, tahun, pembimbing.";
            $out = '';
            foreach ($this->aiService->streamResponse(
                [['role' => 'user', 'content' => $prompt]],
                $model,
                ['max_tokens' => 320, 'grammar' => $this->skripsiMetaGrammar()]
            ) as $chunk) {
                if (is_string($chunk)) {
                    $out .= $chunk;
                }
                if (strlen($out) > 2000) {
                    break;
                }
            }
            foreach (array_keys($defaults) as $key) {
                if (preg_match('/^' . $key . ':\s*(.+)$/mi', $out, $m)) {
                    // Strip any native reasoning tag the model leaked onto the value
                    // (e.g. "Nama Pembimbing</think>") so it never lands in the cover.
                    $val = trim((string) preg_replace('/<\/?(?:think|thinking|sim_thinking)>/i', '', $m[1]));
                    if ($val !== '' && mb_strlen($val) < 200) {
                        // Jangan timpa judul jika pengguna sudah memberikan judul verbatim
                        if ($key === 'judul' && $explicitTitle !== null) {
                            continue;
                        }
                        $defaults[$key] = $val;
                    }
                }
            }
        } catch (\Throwable $e) {
            // keep defaults — the pipeline must not die on a metadata hiccup
        }

        // The title lands in YAML and in the antArtifact title attribute.
        $defaults['judul'] = trim(str_replace(['"', "\r", "\n"], ['', ' ', ' '], $defaults['judul'])) ?: 'Skripsi';

        return $defaults;
    }

    /** System prompt for the chapter-writer calls of the skripsi pipeline. */
    protected function chapterWriterPrompt(): string
    {
        return "Anda adalah penulis akademik Indonesia yang menulis skripsi bab demi bab.\n"
            . "ATURAN KERAS:\n"
            . "1. Tulis HANYA bagian yang diminta — jangan menulis bab lain dan jangan mengulang bab sebelumnya.\n"
            . "2. Keluarkan Markdown murni: mulai LANGSUNG dengan heading '# ...' — TANPA kalimat pembuka, TANPA penutup, TANPA ``` code fence, TANPA tag <antArtifact>.\n"
            . "3. Setiap sub-bab (## heading) berisi minimal 3 paragraf prosa akademik yang utuh dan substantif — bukan outline satu kalimat, bukan placeholder.\n"
            . "4. DILARANG KERAS menulis teks penanda/placeholder seperti '(Isi bagian ini ditulis lengkap...)', '(menandai struktur)', '(...)', atau tanda kurung kosong. Setiap bagian HARUS langsung berisi paragraf nyata. Jangan pernah menulis kerangka kosong.\n"
            . "5. BAHASA: seluruh tulisan WAJIB Bahasa Indonesia baku. SATU-SATUNYA pengecualian adalah bagian berjudul '# ABSTRACT' (terjemahan abstrak) yang ditulis dalam bahasa Inggris. Di luar bagian itu, menulis kalimat berbahasa Inggris adalah KESALAHAN.\n"
            . "6. Tulis HANYA bagian yang diminta pada giliran ini — JANGAN menuliskan heading bab lain (mis. BAB berikutnya atau DAFTAR PUSTAKA) di bagian ini.\n"
            . "7. KHUSUS sub-bab '1.4 Batasan Masalah': WAJIB ditulis sebagai paragraf mengalir. DILARANG KERAS menggunakan format daftar/bullet/poin (- atau 1., 2., 3.).\n"
            . "8. Jika Anda menyertakan tabel, pastikan formatnya tabel Markdown baku. Jika Anda menyertakan diagram, gunakan blok ```mermaid. Setiap diagram blok ```mermaid WAJIB diawali dengan judul/caption teks bernomor (contoh: \"**Gambar 3.1** Alur Penelitian\") di luar blok sebagai fallback.";
    }

    /**
     * Generate ONE skripsi chapter body completely: initial pass, up to three
     * truncation-continuation rounds, then a TARGETED fill for any sub-bab the
     * guide declares (## N.M) that the model skipped — the small model routinely
     * stops after 1.4 without ever writing 1.5, so a plain truncation check is
     * not enough. Yields transient thinking events for the scratchpad; the
     * finished, cleaned chapter markdown is returned via ->getReturn().
     * Shared by the fresh pipeline and the "lanjutkan BAB N" continuation.
     */
    protected function generateChapterBody(string $model, int $maxTokens, string $stopKey, string $request, array $meta, string $summary, string $label, string $heading, string $guide, bool $isFrontChapter): \Generator
    {
        $topic = trim((string) ($meta['judul'] ?? ''));
        $langRule = $isFrontChapter
            ? "BAHASA: HALAMAN PENGESAHAN dan ABSTRAK wajib Bahasa Indonesia baku; HANYA bagian '# ABSTRACT' yang ditulis dalam bahasa Inggris."
            : "⚠️ PENTING — BAHASA: Tulis SELURUH {$label} dalam Bahasa Indonesia baku, dari kalimat pertama sampai kalimat terakhir. DILARANG memakai bahasa Inggris sama sekali.";

        // The user prompt binds the TOPIC hard (a small model otherwise drifts to
        // an unrelated topic — seen live: a cloud-computing skripsi came out about
        // livestock disease) and bans placeholder/skeleton output outright.
        $buildUser = function (bool $harden) use ($request, $topic, $summary, $label, $heading, $guide, $langRule): string {
            return "TOPIK/JUDUL SKRIPSI (WAJIB diikuti, dilarang berpindah topik): \"{$topic}\".\n"
                . "Permintaan pengguna: {$request}\n"
                . ($summary !== '' ? "\nRingkasan bagian yang SUDAH ditulis (untuk konsistensi, JANGAN diulang):\n" . mb_substr($summary, 0, 3500) . "\n" : '')
                . "\nTUGAS SEKARANG: tulis {$label} secara LENGKAP dan MENDALAM untuk skripsi berjudul \"{$topic}\" — mulai LANGSUNG dengan heading '# {$heading}'.\n{$guide}\n\n"
                . "ATURAN ISI (WAJIB dipatuhi):\n"
                . "- Setiap sub-bab berisi PARAGRAF akademik nyata yang panjang, spesifik pada topik di atas.\n"
                . "- DILARANG KERAS menulis placeholder/penanda seperti '(Isi bagian ini ditulis lengkap...)', '(menandai struktur)', '(...)', atau kurung kosong. Jika Anda menulis itu, jawaban Anda SALAH total.\n"
                . "- Tulis HANYA {$label}. JANGAN menuliskan heading atau kerangka bab lain (BAB lain / DAFTAR PUSTAKA) di bagian ini.\n"
                . ($harden
                    ? "- PERINGATAN: percobaan sebelumnya gagal karena hanya berupa kerangka kosong. KALI INI tulis isi paragraf yang benar-benar penuh, tidak boleh ada satu pun tanda kurung placeholder.\n"
                    : '')
                . $langRule;
        };

        // Up to 3 attempts: a chapter that comes back as an empty skeleton
        // (placeholder markers, or headings with no real prose) is rejected and
        // rewritten with a hardened prompt — the garbage never reaches the doc.
        $chapterText = '';
        $attempt = 0;
        while ($attempt < 3 && !Cache::get($stopKey)) {
            $attempt++;
            $messages = [
                ['role' => 'system', 'content' => $this->chapterWriterPrompt()],
                ['role' => 'user', 'content' => $buildUser($attempt > 1)],
            ];
            $gen = $this->streamRawChapter($messages, $model, $maxTokens, $stopKey, $label);
            foreach ($gen as $ev) {
                yield $ev;
            }
            $candidate = $this->stripStubLines(
                $this->trimToChapterScope(
                    $this->cleanChapterText((string) $gen->getReturn(), $heading),
                    $heading,
                    $isFrontChapter
                )
            );
            $chapterText = $candidate; // keep the latest as fallback
            if (!$this->chapterLooksLikeStub($candidate) || Cache::get($stopKey)) {
                break;
            }
            yield ['type' => 'thinking', 'text' => "Bagian {$heading} keluar sebagai kerangka kosong — menulis ulang dengan isi paragraf nyata…\n"];
        }

        // Complete every sub-bab the guide declares (## N.M) — not just the
        // MISSING headings but also the EMPTY ones (heading present, no prose,
        // seen live: 1.1–1.3 came back as bare headings). Rebuilt in declared
        // order so filled sub-babs never land out of sequence at the end.
        if (!Cache::get($stopKey)
            && preg_match_all('/(?<!#)##(?!#)\s*(\d+\.\d+)\s+([^,\n#]+)/', $guide, $gm, PREG_SET_ORDER)) {
            $declared = [];
            $seen = [];
            foreach ($gm as $g) {
                if (isset($seen[$g[1]])) {
                    continue;
                }
                $seen[$g[1]] = true;
                // Clean the label: drop any parenthetical hint and everything from
                // the first sentence break, so a run-on guide line like
                // "Sistematika Penulisan. Setiap sub-bab minimal…" yields just the
                // sub-bab title, not the trailing instruction.
                $label = preg_replace('/\s*[\(.].*$/s', '', $g[2]);
                $declared[] = [$g[1], trim((string) $label, " \t-–")];
            }
            $gen2 = $this->completeChapterSubbabs($model, $maxTokens, $stopKey, $meta, $heading, $chapterText, $declared);
            foreach ($gen2 as $ev) {
                yield $ev;
            }
            $chapterText = (string) $gen2->getReturn();
        }

        return $this->stripStubLines($chapterText);
    }

    /**
     * Ensure every declared sub-bab (## N.M) has REAL prose and appears in the
     * right order. Splits the chapter into intro + per-sub-bab sections, finds
     * the empty/missing ones, generates them in a single fill call, and
     * reassembles the chapter with the declared sub-babs in sequence (extra
     * sub-babs the model added are kept after). Returns the rebuilt chapter via
     * ->getReturn(); yields a status thinking line if a fill was needed.
     */
    protected function completeChapterSubbabs(string $model, int $maxTokens, string $stopKey, array $meta, string $heading, string $chapterText, array $declared): \Generator
    {
        // Split into intro (before first "## ") + each "## " section.
        // Patch 4: Perlebar deteksi isi untuk varian heading (## 3.1, ### 3.1, 3.1 Judul)
        $sections = preg_split('/(?m)^(?=#{0,3}[ \t]*\d+\.\d+\b)/', $chapterText);
        $intro = rtrim((string) array_shift($sections));
        $bodies = [];   // num => "## N.M …\n<body>"
        $extra = [];    // ## sections that aren't declared numbers
        foreach ($sections as $sec) {
            if (preg_match('/^#{0,3}[ \t]*(\d+\.\d+)\b/', $sec, $sm)) {
                $bodies[$sm[1]] = rtrim($sec);
            } elseif (trim($sec) !== '') {
                $extra[] = rtrim($sec);
            }
        }

        $contentOf = function (string $num) use (&$bodies): string {
            if (!isset($bodies[$num])) {
                return '';
            }
            return trim((string) preg_replace('/^#{0,3}[ \t]*\d+\.\d+[^\n]*\n?/', '', $bodies[$num]));
        };

        // Which declared sub-babs are empty or too thin to count as written?
        $need = [];
        foreach ($declared as [$num, $label]) {
            if (mb_strlen($contentOf($num)) < 120) { // Ambang dinaikkan ke 120 char
                $need[$num] = $label;
            }
        }

        if ($need && !Cache::get($stopKey)) {
            foreach ($need as $num => $label) {
                if (Cache::get($stopKey)) {
                    break;
                }
                yield ['type' => 'thinking', 'text' => "Melengkapi sub-bab {$heading} yang kosong: {$num} {$label}…\n"];
                
                $attempt = 0;
                $success = false;
                while ($attempt < 2 && !Cache::get($stopKey)) {
                    $attempt++;
                    $fillMessages = [
                        ['role' => 'system', 'content' => $this->chapterWriterPrompt()],
                        ['role' => 'user', 'content' =>
                            "Topik/judul skripsi: \"{$meta['judul']}\".\n"
                            . "Dalam {$heading}, sub-bab '{$num} {$label}' masih kosong.\n"
                            . "TUGAS: Tulis LENGKAP sub-bab ini dalam Bahasa Indonesia baku — mulai dengan heading '## {$num} {$label}', tulis minimal 2-3 paragraf akademik yang tebal, spesifik pada topik di atas. DILARANG menulis placeholder/tanda kurung kosong."],
                    ];
                    $gen = $this->streamRawChapter($fillMessages, $model, $maxTokens, $stopKey, $heading);
                    foreach ($gen as $ev) {
                        yield $ev;
                    }
                    $fillText = (string) preg_replace('/<\/?antArtifact[^>]*>/i', '', (string) $gen->getReturn());
                    
                    // Parse the fill's sections
                    $clean = $this->stripStubLines(rtrim($fillText));
                    $proseOnly = trim((string) preg_replace('/^#{0,3}[ \t]*\d+\.\d+[^\n]*\n?/', '', $clean));
                    
                    if (mb_strlen($proseOnly) >= 120) {
                        $bodies[$num] = "## {$num} {$label}\n\n" . $proseOnly;
                        $success = true;
                        break;
                    }
                }
                
                if (!$success) {
                    // Larangan heading telanjang
                    $fallbackMsg = "Bagian ini memaparkan penjelasan rinci mengenai {$label} sesuai dengan batasan dan ruang lingkup yang telah ditetapkan. Pembahasan lebih mendalam mengenai aspek ini akan diuraikan pada tahap penyusunan atau revisi berikutnya.";
                    $bodies[$num] = "## {$num} {$label}\n\n" . $fallbackMsg;
                }
            }
        }

        // Reassemble: intro, declared sub-babs in order, then any extras.
        $result = $intro;
        foreach ($declared as [$num, $label]) {
            $result .= "\n\n" . ($bodies[$num] ?? "## {$num} {$label}\n\nPenjelasan rinci mengenai {$label}...");
        }
        foreach ($extra as $sec) {
            $result .= "\n\n" . $sec;
        }

        return trim($result);
    }

    /**
     * Raw chapter generation: one model call + up to 3 truncation-continuation
     * rounds. Yields the model's native reasoning as NON-transient thinking so it
     * stays attached to the finished message (the pipeline's "proses berpikir"
     * must remain visible after the run ends, not vanish). Returns the assembled
     * (uncleaned) chapter text via ->getReturn().
     */
    protected function streamRawChapter(array $messages, string $model, int $maxTokens, string $stopKey, string $label): \Generator
    {
        $text = '';
        $truncated = false;
        foreach ($this->aiService->streamResponse($messages, $model, ['max_tokens' => $maxTokens]) as $chunk) {
            if (Cache::get($stopKey)) {
                break;
            }
            if (!is_string($chunk)) {
                if (is_array($chunk) && ($chunk['type'] ?? '') === 'thinking' && ($chunk['text'] ?? '') !== '') {
                    yield ['type' => 'thinking', 'text' => $chunk['text']];
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'truncated') {
                    $truncated = true;
                }
                continue;
            }
            $text .= $chunk;
        }

        $rounds = 0;
        while ($truncated && $rounds < 3 && !Cache::get($stopKey) && trim($text) !== '') {
            $rounds++;
            $truncated = false;
            $tail = substr($text, -400);
            $cont = array_merge($messages, [
                ['role' => 'assistant', 'content' => $text],
                ['role' => 'user', 'content' => "[LANJUTKAN — tulisan Anda terpotong. Sambung PERSIS dari potongan terakhir di bawah, tanpa mengulang, tanpa kalimat pembuka, sampai {$label} selesai.\nPotongan terakhir:\n...{$tail}]"],
            ]);
            $ct = '';
            foreach ($this->aiService->streamResponse($cont, $model, ['max_tokens' => $maxTokens]) as $chunk) {
                if (Cache::get($stopKey)) {
                    break;
                }
                if (is_string($chunk)) {
                    $ct .= $chunk;
                } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'truncated') {
                    $truncated = true;
                }
            }
            $ct = trim((string) preg_replace('/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $ct));
            for ($k = min(300, strlen($ct), strlen($text)); $k > 20; $k--) {
                if (substr($text, -$k) === substr($ct, 0, $k)) {
                    $ct = substr($ct, $k);
                    break;
                }
            }
            if (trim($ct) === '') {
                break;
            }
            $text .= $ct;
        }

        return $text;
    }

    /**
     * A chapter is a useless "skeleton" when it carries the model's placeholder
     * markers, or is essentially just headings with no real paragraphs. Such a
     * chapter is rejected and regenerated so it never lands in the document.
     */
    protected function chapterLooksLikeStub(string $text): bool
    {
        if (preg_match('/menandai struktur|isi bagian ini ditulis|ditulis lengkap dalam paragraf|ditulis saat generasi|isi bagian ini/i', $text)) {
            return true;
        }
        $prose = 0;
        foreach (preg_split('/\n/', $text) as $line) {
            $t = trim($line);
            if ($t === '' || $t[0] === '#') {
                continue;
            }
            // A line that is only a parenthetical/italic placeholder doesn't count.
            if (preg_match('/^[_*>\s]*[\(\[].*[\)\]][_*\s]*$/u', $t)) {
                continue;
            }
            if (mb_strlen($t) >= 40) {
                $prose++;
            }
        }

        return $prose < 2;
    }

    /**
     * Remove any residual placeholder/skeleton lines the model emitted, so a
     * stray "(Isi bagian ini ditulis lengkap…)" can never reach the document
     * even if the surrounding chapter is otherwise real.
     */
    protected function stripStubLines(string $text): string
    {
        $lines = [];
        foreach (preg_split('/\n/', $text) as $line) {
            if (preg_match('/menandai struktur|isi bagian ini ditulis|ditulis lengkap dalam paragraf|ditulis saat generasi/i', $line)) {
                continue;
            }
            // Leaked system directives the small model sometimes echoes verbatim
            // into the prose (seen live: "DILARANG mengarang data…").
            if (preg_match('/^\s*\[?(SISTEM|SYSTEM)\b|DILARANG mengarang|menolak memberikan hasil|tanpa kalimat pembuka/i', $line)) {
                continue;
            }
            $lines[] = $line;
        }
        // Collapse the blank lines a removed stub may leave behind.
        $out = (string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines));

        return trim($out);
    }

    /**
     * Trim a generated chapter to ITS OWN scope: cut everything from the first
     * top-level heading that belongs to a DIFFERENT section. A small model asked
     * for "Pengesahan+Abstrak" sometimes dumps the whole BAB I–V skeleton after
     * it; this removes that leak so each chapter contributes only its own part.
     */
    protected function trimToChapterScope(string $text, string $heading, bool $isFrontChapter): string
    {
        $ownBab = preg_match('/^BAB\s+([IVXLCDM]+|\d+)/i', trim($heading), $hm)
            ? $this->detectBabReference('bab ' . $hm[1])
            : null;

        $lines = explode("\n", $text);
        $out = [];
        $seenOwn = false;
        $seenHeads = [];
        foreach ($lines as $line) {
            if (preg_match('/^#\s+(BAB\s+([IVXLCDM]+|\d+)\b|DAFTAR\s+PUSTAKA|HALAMAN\s+PENGESAHAN|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR)/i', $line, $cm)) {
                if ($isFrontChapter) {
                    $owned = (bool) preg_match('/^#\s+(HALAMAN\s+PENGESAHAN|ABSTRAK|ABSTRACT|KATA\s+PENGANTAR)/i', $line);
                } elseif ($ownBab !== null) {
                    $n = isset($cm[2]) && $cm[2] !== '' ? $this->detectBabReference('bab ' . $cm[2]) : null;
                    $owned = ($n === $ownBab);
                } else { // Daftar Pustaka chapter
                    $owned = (bool) preg_match('/^#\s+DAFTAR\s+PUSTAKA/i', $line);
                }
                if (!$owned) {
                    if ($seenOwn) {
                        break; // reached a different chapter → stop here
                    }
                    // A leading foreign heading before our own content: skip it.
                    continue;
                }
                // Drop a repeated identical top-level heading (small models echo
                // "# HALAMAN PENGESAHAN" twice) — keep only the first.
                $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $line)));
                if (isset($seenHeads[$key])) {
                    continue;
                }
                $seenHeads[$key] = true;
                $seenOwn = true;
            }
            $out[] = $line;
        }

        return trim(implode("\n", $out));
    }

    /**
     * Self-critique / quality mode (perubahan.md #5): the model writes a full
     * draft first (never shown as the answer — it streams into the thinking
     * panel so the user sees progress), then reviews its own draft and writes
     * an improved final version, which is what actually streams as content.
     * Costs ~2× time/tokens; only runs when the user turned the toggle on.
     */
    protected function streamWithSelfCritique(array $messagesForAi, string $model, array $genOptions, string $stopKey): \Generator
    {
        // ── Pass 1: silent draft ─────────────────────────────────────────────
        yield ['type' => 'thinking', 'text' => "🔍 Mode kualitas aktif — menyusun draf awal…\n\n", 'transient' => true];

        $draft = '';
        foreach ($this->aiService->streamResponse($messagesForAi, $model, $genOptions) as $chunk) {
            if (Cache::get($stopKey)) {
                return;
            }
            if (is_string($chunk)) {
                $draft .= $chunk;
                // Show the draft forming in the thinking panel (transient: it
                // never pollutes the saved final answer's thinking).
                yield ['type' => 'thinking', 'text' => $chunk, 'transient' => true];
            } elseif (is_array($chunk) && ($chunk['type'] ?? '') === 'thinking' && ($chunk['text'] ?? '') !== '') {
                yield ['type' => 'thinking', 'text' => $chunk['text'], 'transient' => true];
            }
        }

        $draftClean = trim((string) preg_replace('/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $draft));
        if ($draftClean === '') {
            return; // nothing to improve — let the caller's empty-answer handling run
        }

        // ── Pass 2: review + improved rewrite (this is the visible answer) ───
        yield ['type' => 'thinking', 'text' => "\n\n✏️ Draf selesai — memeriksa kelemahan dan menulis versi final yang lebih baik…\n", 'transient' => true];

        $reviseMessages = array_merge($messagesForAi, [
            ['role' => 'assistant', 'content' => $draftClean],
            ['role' => 'user', 'content' => "[SISTEM — SELF-REVIEW:\n"
                . "Tinjau jawaban Anda di atas sebagai editor yang kritis: cari bagian yang dangkal, kurang akurat, kurang terstruktur, bertele-tele, atau salah bahasa.\n"
                . "Lalu tulis ULANG jawaban versi FINAL yang lebih baik: lebih dalam, lebih tepat, lebih rapi, bahasa konsisten dengan bahasa pengguna.\n"
                . "Keluarkan HANYA jawaban final yang sudah diperbaiki — tanpa daftar kritik, tanpa menyebut proses review ini, dan pertahankan format yang diwajibkan (artifact/markdown) bila ada.]"],
        ]);

        $revised = '';
        foreach ($this->aiService->streamResponse($reviseMessages, $model, $genOptions) as $chunk) {
            if (Cache::get($stopKey)) {
                break;
            }
            if (is_string($chunk)) {
                $revised .= $chunk;
                yield $chunk;
            } else {
                yield $chunk; // thinking/truncated pass through normally
            }
        }

        // Safety net: revision came back empty/failed → fall back to the draft
        // so quality mode can never produce a WORSE outcome than normal mode.
        if (trim((string) preg_replace('/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $revised)) === '') {
            foreach (str_split($draftClean, 400) as $piece) {
                yield $piece;
            }
        }
    }

    /**
     * Local tool use (perubahan.md #8): local models are instructed that when
     * a question needs fresh/factual information they should answer with ONE
     * <antSearch>query</antSearch> tag instead of guessing. This wrapper
     * sniffs the start of the stream for that tag; when found, the doomed
     * answer is scrapped, the web is searched, and the model re-answers with
     * numbered sources (emitted as citations chips).
     */
    /** Does this question need up-to-the-minute information? (deterministic trigger) */
    protected function needsFreshInfo(string $text): bool
    {
        return (bool) preg_match(
            '/\b(harga|termurah|termahal|terbaru|terkini|sekarang|saat ini|hari ini|minggu ini|bulan ini|berita|kurs|saham|crypto|bitcoin|skor|klasemen|jadwal|rilis|update terbaru|versi terbaru|baru rilis|tahun 202[4-9])\b'
            . '|\b(latest|current|today|newest|price of|release date)\b/iu',
            $text
        );
    }

    /** The model gave up ("no access / offline") instead of using its search tool. */
    protected const SURRENDER_RE = '/tidak (memiliki|punya|mempunyai) akses|tidak (dapat|bisa) mengakses'
        . '|beroperasi secara offline|data (saya )?(terbatas|hanya sampai)|pengetahuan saya (terbatas|hanya)'
        . '|don\'?t have access|cannot access|no access to (real[- ]?time|current)|as an ai/iu';

    protected function interceptLocalSearch(\Generator $stream, array $messagesForAi, string $model, string $stopKey, string $userQuestion = ''): \Generator
    {
        $visible = fn (string $s): string => trim((string) preg_replace(
            '/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $s
        ));
        $fallbackQuery = \Illuminate\Support\Str::limit($userQuestion, 120, '');

        $buf = '';
        $decided = false;
        foreach ($stream as $chunk) {
            if (!is_string($chunk) || $decided) {
                yield $chunk;
                continue;
            }
            $buf .= $chunk;
            $sniff = $visible($buf);

            if (preg_match('/<antSearch>([\s\S]{2,200}?)<\/antSearch>/i', $sniff, $m)) {
                // Model asked for a search — scrap this stream and re-answer.
                yield from $this->searchThenReanswer(trim($m[1]), $messagesForAi, $model, $stopKey);
                return;
            }

            // Surrender answer ("saya tidak memiliki akses…") → the user wanted
            // facts, so run the search anyway with the question as the query.
            if ($fallbackQuery !== '' && strlen($sniff) >= 40 && preg_match(self::SURRENDER_RE, mb_substr($sniff, 0, 400))) {
                yield from $this->searchThenReanswer($fallbackQuery, $messagesForAi, $model, $stopKey);
                return;
            }

            // Both the tag and surrender phrases appear early in a reply —
            // keep sniffing until enough visible text has arrived. Latency is
            // fine: the Indonesian guard downstream buffers ~250 chars anyway.
            if (strlen($sniff) < 420 && strlen($buf) < 8000) {
                continue;
            }

            // Normal answer — release the buffer and passthrough from here.
            $decided = true;
            yield $buf;
        }

        if (!$decided && $buf !== '') {
            $sniff = $visible($buf);
            if (preg_match('/<antSearch>([\s\S]{2,200}?)<\/antSearch>/i', $sniff, $m)) {
                yield from $this->searchThenReanswer(trim($m[1]), $messagesForAi, $model, $stopKey);
            } elseif ($fallbackQuery !== '' && preg_match(self::SURRENDER_RE, mb_substr($sniff, 0, 400))) {
                yield from $this->searchThenReanswer($fallbackQuery, $messagesForAi, $model, $stopKey);
            } else {
                yield $buf;
            }
        }
    }

    /**
     * Run the model-requested web search and stream the re-answer grounded in
     * numbered sources. Citations are forwarded as a structured chunk so the
     * main loop saves them and renders source chips under the reply.
     */
    protected function searchThenReanswer(string $query, array $messagesForAi, string $model, string $stopKey): \Generator
    {
        yield ['type' => 'thinking', 'text' => "🔎 Model meminta pencarian web: \"{$query}\" — mencari sumber…\n", 'transient' => true];

        $results = [];
        try {
            $results = (new \App\Services\WebSearchService())->search($query, 4);
        } catch (\Throwable $e) {
            // Search backend down — fall through to the no-results path.
        }

        if (empty($results)) {
            // No sources — say so VISIBLY. A confident-sounding answer from
            // stale model memory must never masquerade as a searched fact.
            yield "⚠️ _Pencarian web tidak mengembalikan hasil (mesin pencari mungkin diblokir jaringan Anda — cek `storage/logs/laravel.log` baris `WebSearchService`; solusi permanen: isi `SEARCH_PROVIDER=tavily` + `SEARCH_API_KEY` di file .env). Jawaban di bawah berasal dari pengetahuan internal model dan BISA USANG:_\n\n";

            $fallback = array_merge($messagesForAi, [
                ['role' => 'assistant', 'content' => "<antSearch>{$query}</antSearch>"],
                ['role' => 'user', 'content' => '[SISTEM: Pencarian web tidak tersedia/tidak menemukan hasil. Jawab pertanyaan pengguna sebaik mungkin dari pengetahuanmu sendiri, nyatakan dengan jelas bahwa angka/fakta terkini tidak bisa kamu verifikasi, dan JANGAN mengarang angka pasti. JANGAN memakai tag <antSearch> lagi.]'],
            ]);
            foreach ($this->aiService->streamResponse($fallback, $model) as $chunk) {
                if (Cache::get($stopKey)) {
                    return;
                }
                yield $chunk;
            }

            return;
        }

        $block = "[HASIL PENCARIAN WEB untuk \"{$query}\":\n";
        $citations = [];
        foreach ($results as $i => $r) {
            $n = $i + 1;
            $block .= "\n[{$n}] {$r['title']}\nURL: {$r['url']}\n{$r['snippet']}\n";
            $citations[] = ['n' => $n, 'title' => $r['title'], 'url' => $r['url']];
        }
        $block .= "\nJawab pertanyaan pengguna SEKARANG berdasarkan sumber-sumber di atas, dalam bahasa pengguna. "
            . "Kutip nomor sumbernya inline seperti [1] pada klaim yang bersumber. "
            . "Jika sumber tidak menjawab pertanyaan, katakan jujur. JANGAN memakai tag <antSearch> lagi.]";

        yield ['type' => 'citations', 'data' => $citations];
        yield ['type' => 'thinking', 'text' => '✅ ' . count($citations) . " sumber ditemukan — menyusun jawaban berdasarkan sumber…\n", 'transient' => true];

        $reanswer = array_merge($messagesForAi, [
            ['role' => 'assistant', 'content' => "<antSearch>{$query}</antSearch>"],
            ['role' => 'user', 'content' => $block],
        ]);
        foreach ($this->aiService->streamResponse($reanswer, $model) as $chunk) {
            if (Cache::get($stopKey)) {
                return;
            }
            yield $chunk;
        }
    }

    /**
     * Language watchdog for local-model plain chat (user wrote Indonesian).
     * Buffers the first ~250 chars of the answer; if it sniffs as English the
     * whole attempt is scrapped (never shown) and ONE retry with a hard
     * Indonesian-only directive is streamed instead. Thinking chunks pass
     * through live either way.
     */
    protected function guardIndonesianAnswer(\Generator $stream, array $messagesForAi, string $model): \Generator
    {
        $retryIndonesian = function () use ($messagesForAi, $model, &$buf): \Generator {
            $retry = $messagesForAi;
            if (($retry[0]['role'] ?? '') === 'system') {
                $retry[0]['content'] = "JAWAB 100% DALAM BAHASA INDONESIA. Menulis bahasa Inggris DILARANG KERAS.\n\n" . $retry[0]['content'];
            }
            $retry[] = ['role' => 'assistant', 'content' => $buf];
            $retry[] = ['role' => 'user', 'content' => '[SISTEM: Jawaban Anda barusan memakai bahasa Inggris — itu salah. Ulangi jawaban dari awal SEPENUHNYA dalam Bahasa Indonesia baku. Jangan menyinggung kesalahan ini, langsung jawab.]'];
            foreach ($this->aiService->streamResponse($retry, $model) as $c) {
                yield $c;
            }
        };

        // Inline <think> blocks are legitimately English (the model's
        // reasoning language) — sniff only the visible answer text.
        $visible = fn (string $s): string => trim((string) preg_replace(
            '/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $s
        ));

        $buf = '';
        $checked = false;
        foreach ($stream as $chunk) {
            if (!is_string($chunk) || $checked) {
                yield $chunk;
                continue;
            }
            $buf .= $chunk;
            $sniff = $visible($buf);
            if (strlen($sniff) < 250 && strlen($buf) < 6000) {
                continue; // keep sniffing until enough visible answer text
            }
            $checked = true;
            if ($sniff !== '' && $this->looksEnglish($sniff)) {
                yield from $retryIndonesian();
                return; // original stream abandoned (server aborts on disconnect)
            }
            yield $buf; // Indonesian — release the buffer, passthrough from here
        }

        // Stream ended below the sniff threshold (short answers like
        // "Hello! How can I help you today?") — same check on what we have.
        if (!$checked && $buf !== '') {
            $sniff = $visible($buf);
            if ($sniff !== '' && $this->looksEnglish($sniff)) {
                yield from $retryIndonesian();
            } else {
                yield $buf;
            }
        }
    }

    /**
     * Cheap language sniff: does this chapter read as English? Counts common
     * English vs Indonesian function words in the first few KB. Used by the
     * pipeline's language guard for BAB I–V (ABSTRACT and DAFTAR PUSTAKA may
     * legitimately contain English, so they are never checked).
     */
    protected function looksEnglish(string $text): bool
    {
        $sample = ' ' . mb_strtolower(mb_substr($text, 0, 4000)) . ' ';
        $en = 0;
        foreach ([' the ', ' of ', ' and ', ' this ', ' is ', ' are ', ' with ', ' that ', ' research ', ' study ', ' will ', ' to '] as $w) {
            $en += substr_count($sample, $w);
        }
        $id = 0;
        foreach ([' yang ', ' dan ', ' dengan ', ' ini ', ' pada ', ' dalam ', ' untuk ', ' adalah ', ' penelitian ', ' terhadap ', ' dari ', ' akan '] as $w) {
            $id += substr_count($sample, $w);
        }

        return $en > $id;
    }

    /**
     * Normalize one pipeline chapter: strip reasoning/artifact/fence wrappers
     * and any chat noise before the first heading, so only clean document
     * content is stitched into the artifact.
     */
    protected function cleanChapterText(string $text, string $heading): string
    {
        $text = (string) preg_replace('/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $text);
        $text = (string) preg_replace('/<\/?antArtifact[^>]*>/i', '', $text);
        if (preg_match('/^\s*```(?:markdown)?\s*\n([\s\S]*?)\n?```\s*$/', trim($text), $m)) {
            $text = $m[1];
        }
        // Chapters start at their heading; anything before the first '#' line is chat noise.
        if (preg_match('/^#{1,2}\s/m', $text, $m, PREG_OFFSET_CAPTURE)) {
            $text = substr($text, $m[0][1]);
        }
        $text = trim($text);
        if ($text === '') {
            return "# {$heading}\n\n*(Bagian ini belum berhasil dihasilkan — kirim \"lanjutkan {$heading}\" untuk mencoba lagi.)*";
        }
        
        $lines = explode("\n", $text);
        $cleanedLines = [];
        $ownBab = preg_match('/^BAB\s+([IVXLCDM]+|\d+)/i', trim($heading), $hm)
            ? $this->detectBabReference('bab ' . $hm[1])
            : null;
        $cleanHead = mb_strtolower(trim(preg_replace('/^#+\s*/', '', $heading)));

        foreach ($lines as $line) {
            $t = trim($line);
            $isHeadingToRemove = false;

            if ($ownBab !== null) {
                if (preg_match('/^#{0,3}\s*BAB\s+([IVXLCDM]+|\d+)\b/i', $t, $cm)
                    || preg_match('/^\*\*\s*BAB\s+([IVXLCDM]+|\d+)\b.*?\*\*\s*$/i', $t, $cm)
                    || preg_match('/^BAB\s+([IVXLCDM]+|\d+)\b/i', $t, $cm)) {
                    
                    $n = isset($cm[1]) && $cm[1] !== '' ? $this->detectBabReference('bab ' . $cm[1]) : null;
                    if ($n === $ownBab && mb_strlen($t) < 120 && !preg_match('/[.,;?]$/', $t)) {
                        $isHeadingToRemove = true;
                    }
                }
            } else {
                if (preg_match('/^#{0,3}\s*' . preg_quote($cleanHead, '/') . '/i', mb_strtolower($t))
                    || preg_match('/^\*\*\s*' . preg_quote($cleanHead, '/') . '.*?\*\*\s*$/i', mb_strtolower($t))
                    || mb_strtolower($t) === $cleanHead) {
                    if (mb_strlen($t) < 120 && !preg_match('/[.,;?]$/', $t)) {
                        $isHeadingToRemove = true;
                    }
                }
            }

            if (!$isHeadingToRemove) {
                $cleanedLines[] = $line;
            }
        }
        
        $text = trim(implode("\n", $cleanedLines));
        $text = "# {$heading}\n\n" . $text;

        return $text;
    }

    /**
     * Compact outline of a finished chapter (sub-headings + first sentence
     * each). Shared by three consumers: the rolling context for later chapter
     * calls, the per-stage report bubbles, and the final long-form debrief.
     */
    protected function chapterOutlineLines(string $text): array
    {
        $lines = [];
        if (preg_match_all('/^(#{1,3}\s+[^\n]+)\n+([^#\n][^\n]*)/m', $text, $mm, PREG_SET_ORDER)) {
            foreach (array_slice($mm, 0, 10) as $m) {
                $lines[] = trim((string) preg_replace('/^#+\s*/', '', $m[1]))
                    . ' — ' . \Illuminate\Support\Str::limit(trim($m[2]), 110, '…');
            }
        }

        return $lines;
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
        // Small local models can't be trusted to PLAN search queries (they
        // reply NO_SEARCH or garbage) — for them, search the question as-is.
        $queries = app(\App\Services\LlamaServerService::class)->isGgufModel($model)
            ? [\Illuminate\Support\Str::limit($lastUserText, 150, '')]
            : $this->planSearchQueries($lastUserText, $model, $researchMode ? 3 : 2);
        foreach ($queries as $query) {
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
                    $pos = strpos($lt, $open);
                    if ($pos !== false) {
                        // Found an opening tag! Yield any preamble before the tag as normal content.
                        if ($pos > 0) {
                            $out[] = ['type' => 'content', 'text' => substr($lt, 0, $pos)];
                        }
                        $state['buf'] = substr($lt, $pos + strlen($open));
                        $state['close'] = $close;
                        $state['phase'] = 'inside';
                        $opened = true;
                        break;
                    }
                }
                if ($opened) {
                    continue;
                }
                
                // If no opening tag found yet, check if the end of buffer could be a partial opening tag
                $couldForm = false;
                foreach ($tags as $open => $close) {
                    for ($len = 1; $len < strlen($open); $len++) {
                        if (str_ends_with($lt, substr($open, 0, $len))) {
                            $couldForm = true;
                            break 2;
                        }
                    }
                }
                if ($couldForm) {
                    break; // could still become an opening tag — wait
                }
                
                // Allow small models (0.5B–3B) to output up to 150 characters of preamble
                // (e.g., "Baiklah, mari kita analisis:\n<thinking>") before giving up!
                if (strlen($lt) < 150) {
                    break;
                }

                // No reasoning block found after 150 chars: answer starts normally.
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
        $llama = app(\App\Services\LlamaServerService::class);
        if ($model !== '' && $llama->isGgufModel($model)) {
            // Local GGUF engine has a small, real context window (e.g. 16384).
            // Budget against THAT — the adapter registry would otherwise report
            // the generic 128k OpenAI default and overflow the local server.
            $maxCtx = $llama->contextSizeFor($model);
        } else {
            try {
                if ($model !== '') {
                    $maxCtx = (new \App\Services\AI\Normalization\ModelAdapterRegistry())
                        ->for($model)->capabilities()->maxContextTokens ?: 128000;
                }
            } catch (\Throwable $e) {
                // Unknown model — keep the default.
            }
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

            // Never drop a message that carries attachments (issue #6): the
            // uploaded document is re-resolved by the provider on EVERY turn, so
            // a follow-up question about a document uploaded a while ago must
            // still find that attachment in the window. Pin such middle messages
            // back in (in original order) and keep only the rest in the digest.
            $pinnedAttachments = [];
            $digestMiddle = [];
            foreach ($middleMessages as $m) {
                if (!empty($m['attachments'])) {
                    $pinnedAttachments[] = $m;
                } else {
                    $digestMiddle[] = $m;
                }
            }

            $middleDigest = $this->buildMiddleDigest($digestMiddle);
            if ($middleDigest !== '') {
                $systemPrompt .= "\n\n--- EARLIER CONVERSATION DIGEST ---\n"
                    . "These exchanges fell outside the recent message window. They're summarised here so you stay aware of the thread; lean on PERSISTENT MEMORY (above) for durable facts.\n\n"
                    . $middleDigest;
            }

            $messagesForAi = array_merge($firstMessages, $pinnedAttachments, $recentMessages);
        } else {
            $messagesForAi = $userMessages;
        }

        // Prepend system message
        array_unshift($messagesForAi, [
            'role' => 'system',
            'content' => $systemPrompt,
        ]);

        // Append formatting reminder to last user message. Skip it for tiny local
        // GGUF models — the aggressive artifact push makes them emit spurious
        // documents for ordinary messages (their slim prompt covers this already).
        if (!empty($messagesForAi) && !$llama->isGgufModel($model)) {
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
     * Option chips: the model may end a reply with ONE <antOptions> tag whose
     * choices are rendered as one-tap buttons right above the composer (the
     * tag itself is stripped from the visible text by the streaming service).
     */
    protected function getOptionChipsInstructions(): string
    {
        return "\n\n--- OPTION CHIPS (interactive answer buttons) ---\n"
            . "You may end a reply with EXACTLY ONE tag of this form (options separated by |, max 4, each ≤ 60 chars, written in the user's language):\n"
            . "<antOptions>Pilihan A | Pilihan B | Pilihan C</antOptions>\n"
            . "The system strips the tag and renders the options as buttons above the chat box; clicking one sends it as the user's next message. Use it in exactly these situations:\n"
            . "1. BEFORE generating a large document (skripsi/makalah/laporan/proposal) when critical information is missing (e.g. research method, scope, target institution): ask ONE focused clarifying question in your reply, put the likely answers in <antOptions>, and DO NOT generate the document yet — wait for the answer. Always include an escape option like 'Langsung tulis saja dengan asumsi terbaik'. Ask at most once per document; if the user already answered or says to proceed, generate immediately.\n"
            . "2. When a request is genuinely ambiguous between a few interpretations: answer the most likely one briefly, then offer the alternatives as options.\n"
            . "3. AFTER completing a substantial answer or document: offer up to 3 concrete follow-up actions (e.g. 'Perdalam BAB IV', 'Buat versi PDF-nya', 'Tambah contoh kode').\n"
            . "Rules: never mention the tag or the buttons in your prose; never use it for trivial questions; options must be self-contained statements that make sense as a sent message. When a reply contains an <antArtifact> block, the <antOptions> tag goes AFTER the closing </antArtifact> as the very last thing in the reply (this is the ONLY thing allowed after </antArtifact>).";
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
            . "- This process explanation is MANDATORY and should be EXTENSIVE (minimum 500-800 words for full documents — longer is welcome): the user explicitly wants to understand everything that was done, section by section, in plain language\n"
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
            . "---\nmode: skripsi            # skripsi | tesis | makalah | laporan | proposal | jurnal | document — PILIH yang sesuai permintaan user (makalah ≠ skripsi: cover & struktur beda)\njudul: <full title>\npenulis: <author name>\nnim: <student id>\nprodi: <study program>\nfakultas: <faculty>\nuniversitas: <university>\nkota: <city>\ntahun: <year>\npembimbing: <advisor>\ndosen: <hanya untuk makalah: dosen pengampu mata kuliah>\nlogo: <path/URL logo, mis. attachments/<filename> dari file yang diupload user — KOSONGKAN/hapus baris ini bila user tidak mengirim logo>\n---\n"
            . "  Struktur per tipe: skripsi/tesis = BAB I–V + Daftar Pustaka; proposal = BAB I–III (tanpa bab hasil); makalah = Kata Pengantar + BAB I Pendahuluan / II Pembahasan / III Penutup + Daftar Pustaka; laporan = Kata Pengantar + Pendahuluan/Landasan Teori/Pelaksanaan/Penutup; jurnal = tanpa BAB (Abstrak→Pendahuluan→Metode→Hasil & Pembahasan→Kesimpulan).\n"
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
        $artifact = $this->latestMarkdownArtifact($conversation);

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
            // Keep the first ~2 sentences (up to 400 chars) instead of one, so the
            // digest carries enough of each turn to stay coherent (#4). With the
            // 32K window this branch fires rarely, so the extra length is cheap.
            $parts = preg_split('/(?<=[.!?])\s/', $text, 3);
            $snippet = trim(implode(' ', array_slice($parts, 0, 2)));
            if ($snippet === '') {
                $snippet = $text;
            }
            if (mb_strlen($snippet) > 400) {
                $snippet = mb_substr($snippet, 0, 400) . '…';
            }

            $out[] = $role . ': ' . $snippet;

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
