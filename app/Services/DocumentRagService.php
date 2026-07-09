<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Offline RAG (Retrieval-Augmented Generation) for attached documents.
 *
 * Instead of dumping up to 100K chars of a parsed PDF/DOCX into the prompt —
 * which overflows the 16K context of local GGUF models (rynude Vignette→Magnum)
 * and buries the answer for cloud models — documents are chunked once (cached
 * by file mtime) and only the chunks most relevant to the user's question are
 * injected, wrapped in grounding instructions that forbid answering beyond the
 * excerpts. Pure-PHP BM25 over lexical tokens: no embedding model, no vector
 * DB, fully offline, deterministic — the right trade-off for a desktop app
 * where the retrieval corpus is a handful of attached files.
 */
class DocumentRagService
{
    /** Target chunk size in characters (~300 tokens) with paragraph alignment. */
    private const CHUNK_SIZE = 1200;

    /** Overlap between consecutive chunks so answers spanning a boundary survive. */
    private const CHUNK_OVERLAP = 200;

    /** BM25 constants (standard defaults). */
    private const BM25_K1 = 1.5;
    private const BM25_B = 0.75;

    /**
     * Indonesian + English stopwords: excluded from scoring so retrieval keys
     * on content words, not "yang/dan/the/of".
     */
    private const STOPWORDS = [
        'yang', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'dengan', 'untuk', 'pada',
        'adalah', 'dalam', 'tidak', 'akan', 'juga', 'atau', 'bisa', 'ada', 'saya',
        'kamu', 'anda', 'dia', 'kita', 'mereka', 'apa', 'siapa', 'bagaimana',
        'kenapa', 'mengapa', 'dimana', 'kapan', 'sudah', 'belum', 'harus', 'dapat',
        'tersebut', 'sebagai', 'oleh', 'karena', 'jika', 'maka', 'lebih', 'agar',
        'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'at', 'is', 'are',
        'was', 'were', 'be', 'been', 'it', 'this', 'that', 'with', 'for', 'as',
        'by', 'from', 'what', 'which', 'who', 'how', 'why', 'when', 'where',
        'can', 'could', 'would', 'should', 'do', 'does', 'did', 'not', 'no',
    ];

    /**
     * Build the prompt block for one attached document, retrieval-augmented
     * when the document exceeds the budget.
     *
     * @param string $docText  Full extracted document text.
     * @param string $docName  Display filename (for citations).
     * @param string $query    The user's question — retrieval key.
     * @param int    $budget   Max characters of document content to inject.
     */
    public function buildDocumentBlock(string $docText, string $docName, string $query, int $budget): string
    {
        $docText = trim($docText);
        if ($docText === '') {
            return '';
        }

        // Small documents fit whole: no retrieval, but still grounded.
        if (strlen($docText) <= $budget) {
            return "\n\n[DOKUMEN LAMPIRAN: {$docName} — isi lengkap]\n"
                . $docText
                . "\n[AKHIR DOKUMEN: {$docName}]\n"
                . $this->groundingInstructions($docName, false);
        }

        $chunks = $this->chunksFor($docText, $docName);
        $selected = $this->retrieve($chunks, $query, $budget);

        $block = "\n\n[DOKUMEN LAMPIRAN: {$docName} — "
            . count($selected) . " kutipan paling relevan dari " . count($chunks) . " bagian (mode RAG)]\n";

        // Document outline: RAG excerpts lose the table of contents, so the
        // model has no way to know that "1.1 = Latar Belakang" and would
        // happily follow a user's WRONG section number. The outline restores
        // that map and powers the premise-correction grounding rule below.
        $outline = $this->extractOutline($docText);
        if ($outline !== '') {
            $block .= "\n[STRUKTUR DOKUMEN — daftar bagian yang terdeteksi:\n{$outline}]\n";
        }

        foreach ($selected as $i => $chunk) {
            $n = $i + 1;
            $pos = $chunk['position'];
            $block .= "\n--- Kutipan {$n} (bagian ~{$pos}% dari dokumen) ---\n{$chunk['text']}\n";
        }
        $block .= "\n[AKHIR KUTIPAN DOKUMEN: {$docName}]\n"
            . $this->groundingInstructions($docName, true);

        return $block;
    }

    /**
     * Detect the document's section headings (BAB I, 1.1, 2.3.1 …) so the
     * prompt carries a compact table of contents. Cached alongside chunks.
     */
    public function extractOutline(string $docText): string
    {
        $key = 'rag_outline_' . md5(strlen($docText) . '|' . substr($docText, 0, 512));

        return Cache::remember($key, 3600, function () use ($docText) {
            $lines = [];
            // BAB headings (BAB I PENDAHULUAN …) and numbered sections
            // (1.1 Latar Belakang, 2.3.1 …) on their own line, short enough
            // to be a heading rather than prose.
            if (preg_match_all(
                '/^[ \t]*((?:BAB\s+[IVXLC\d]+\b[^\n]{0,70})|(?:\d+(?:\.\d+){1,3}[\.\)]?\s+\p{Lu}[^\n]{2,70}))\s*$/miu',
                $docText,
                $m
            )) {
                foreach ($m[1] as $h) {
                    $h = trim(preg_replace('/\s+/', ' ', $h));
                    // Skip obvious non-headings (sentences ending with period + lowercase-heavy)
                    if (mb_strlen($h) > 80) {
                        continue;
                    }
                    $lines[] = $h;
                    if (count($lines) >= 45) {
                        break;
                    }
                }
            }

            return implode("\n", array_values(array_unique($lines)));
        });
    }

    /**
     * Anti-hallucination grounding contract appended after every document block.
     */
    private function groundingInstructions(string $docName, bool $isExcerpts): string
    {
        $scope = $isExcerpts ? 'kutipan' : 'dokumen';
        return "[INSTRUKSI GROUNDING — WAJIB:\n"
            . "1. Saat menjawab tentang \"{$docName}\", gunakan HANYA informasi yang tertulis dalam {$scope} di atas.\n"
            . "2. JANGAN mengarang isi, angka, nama, atau kesimpulan yang tidak ada dalam {$scope}.\n"
            . "3. Jika jawabannya tidak ditemukan dalam {$scope}, katakan dengan jujur bahwa informasi itu tidak ditemukan di bagian dokumen yang tersedia — jangan menebak.\n"
            . "4. Saat mengutip, sebutkan sumbernya (mis. \"menurut dokumen {$docName}\" atau nomor kutipannya).\n"
            . "5. PERIKSA PREMIS USER: jika pertanyaan user mengandung premis yang TIDAK COCOK dengan dokumen — misalnya menyebut nomor bagian yang salah (\"latar belakang di 1.3\" padahal menurut STRUKTUR DOKUMEN latar belakang ada di 1.1), nama, angka, atau klaim yang keliru — KOREKSI dulu dengan sopan berdasarkan struktur/isi dokumen, baru jawab pertanyaannya. DILARANG ikut-ikutan premis yang salah.]\n";
    }

    /**
     * Chunk a document, cached by content hash so repeated turns on the same
     * attachment skip re-chunking.
     *
     * @return array<int, array{text: string, position: int}>
     */
    public function chunksFor(string $docText, string $docName): array
    {
        $key = 'rag_chunks_' . md5($docName . '|' . strlen($docText) . '|' . substr($docText, 0, 512));
        return Cache::remember($key, 3600, fn () => $this->chunkText($docText));
    }

    /**
     * Paragraph-aware sliding-window chunking. Splits on blank lines first,
     * packs paragraphs up to CHUNK_SIZE, and carries CHUNK_OVERLAP chars of
     * tail into the next chunk. Oversized single paragraphs are hard-split.
     *
     * @return array<int, array{text: string, position: int}>
     */
    public function chunkText(string $docText): array
    {
        $totalLen = max(1, strlen($docText));
        $paragraphs = preg_split('/\n\s*\n/', $docText) ?: [$docText];

        // Hard-split any paragraph that alone exceeds the chunk size.
        $units = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            while (strlen($p) > self::CHUNK_SIZE) {
                // Prefer breaking at a sentence end inside the window.
                $window = substr($p, 0, self::CHUNK_SIZE);
                $cut = max(strrpos($window, '. '), strrpos($window, "\n"));
                $cut = ($cut === false || $cut < self::CHUNK_SIZE / 2) ? self::CHUNK_SIZE : $cut + 1;
                $units[] = trim(substr($p, 0, $cut));
                $p = trim(substr($p, $cut));
            }
            if ($p !== '') {
                $units[] = $p;
            }
        }

        $chunks = [];
        $current = '';
        $offset = 0; // char offset of current chunk start, for position %
        $consumed = 0;
        foreach ($units as $unit) {
            if ($current !== '' && strlen($current) + strlen($unit) + 2 > self::CHUNK_SIZE) {
                $chunks[] = ['text' => $current, 'position' => (int) round($offset / $totalLen * 100)];
                // Overlap: carry the tail of the finished chunk forward.
                $tail = substr($current, -self::CHUNK_OVERLAP);
                $offset = max(0, $consumed - strlen($tail));
                $current = $tail . "\n" . $unit;
            } else {
                if ($current === '') {
                    $offset = $consumed;
                }
                $current = $current === '' ? $unit : $current . "\n\n" . $unit;
            }
            $consumed += strlen($unit) + 2;
        }
        if (trim($current) !== '') {
            $chunks[] = ['text' => $current, 'position' => (int) round($offset / $totalLen * 100)];
        }

        return $chunks;
    }

    /**
     * BM25-rank chunks against the query and pack the best into the budget.
     * The first chunk (title page / abstract / opening) is always included —
     * it anchors what the document IS, which stops small models from
     * hallucinating a different document entirely.
     *
     * @param array<int, array{text: string, position: int}> $chunks
     * @return array<int, array{text: string, position: int}>
     */
    public function retrieve(array $chunks, string $query, int $budget): array
    {
        if (empty($chunks)) {
            return [];
        }

        $queryTerms = $this->tokenize($query);

        // No usable query terms (e.g. "ringkas dokumen ini") → spread evenly
        // through the document instead of lexical matching.
        if (empty($queryTerms)) {
            return $this->spreadSample($chunks, $budget);
        }

        // Corpus stats for BM25.
        $docFreq = [];
        $docLens = [];
        $tokenized = [];
        foreach ($chunks as $i => $chunk) {
            $terms = $this->tokenize($chunk['text']);
            $tokenized[$i] = array_count_values($terms);
            $docLens[$i] = max(1, count($terms));
            foreach (array_keys($tokenized[$i]) as $t) {
                $docFreq[$t] = ($docFreq[$t] ?? 0) + 1;
            }
        }
        $n = count($chunks);
        $avgLen = array_sum($docLens) / $n;

        $scores = [];
        foreach ($chunks as $i => $chunk) {
            $score = 0.0;
            foreach ($queryTerms as $term) {
                $tf = $tokenized[$i][$term] ?? 0;
                if ($tf === 0) {
                    continue;
                }
                $df = $docFreq[$term] ?? 0;
                $idf = log(($n - $df + 0.5) / ($df + 0.5) + 1);
                $score += $idf * ($tf * (self::BM25_K1 + 1))
                    / ($tf + self::BM25_K1 * (1 - self::BM25_B + self::BM25_B * $docLens[$i] / $avgLen));
            }
            $scores[$i] = $score;
        }

        // Semantic blend (perubahan.md #6): when the local embedding model is
        // available, rerank the top lexical candidates by meaning too — so
        // "dampak finansial" still finds "pengaruh terhadap pendapatan".
        // Any failure silently falls back to pure BM25.
        $scores = $this->blendWithEmbeddings($chunks, $query, $scores);

        arsort($scores);

        // Always anchor with the opening chunk, then fill with top-scoring ones.
        $selectedIdx = [0 => true];
        $used = strlen($chunks[0]['text']);
        foreach (array_keys($scores) as $i) {
            if (isset($selectedIdx[$i])) {
                continue;
            }
            $len = strlen($chunks[$i]['text']);
            if ($used + $len > $budget) {
                continue;
            }
            // Skip zero-score chunks once at least one real match is in.
            if ($scores[$i] <= 0 && count($selectedIdx) > 1) {
                break;
            }
            $selectedIdx[$i] = true;
            $used += $len;
        }

        // Present in document order so the excerpt block reads coherently.
        $orderedIdx = array_keys($selectedIdx);
        sort($orderedIdx);

        return array_values(array_map(fn ($i) => $chunks[$i], $orderedIdx));
    }

    /**
     * Even sampling across the document for summary-style queries with no
     * distinctive terms ("ringkas", "apa isi file ini").
     *
     * @param array<int, array{text: string, position: int}> $chunks
     * @return array<int, array{text: string, position: int}>
     */
    private function spreadSample(array $chunks, int $budget): array
    {
        $selected = [];
        $used = 0;
        $n = count($chunks);
        // First + evenly spaced picks until the budget is spent.
        $step = max(1, (int) ceil($n / max(1, intdiv($budget, self::CHUNK_SIZE))));
        for ($i = 0; $i < $n; $i += $step) {
            $len = strlen($chunks[$i]['text']);
            if ($used + $len > $budget && !empty($selected)) {
                break;
            }
            $selected[] = $chunks[$i];
            $used += $len;
        }
        return $selected;
    }

    /**
     * Lowercase word tokens minus stopwords; keeps digits (NIM, years, figures).
     *
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        $lower = mb_strtolower($text);
        preg_match_all('/[\p{L}\p{N}]{2,}/u', $lower, $m);
        $stop = array_flip(self::STOPWORDS);
        $tokens = array_values(array_filter($m[0], fn ($t) => !isset($stop[$t])));

        // Section numbers ("1.3", "2.2.1") are prime retrieval keys for
        // academic documents but the word regex splits them into 1-char
        // digits that get dropped — keep them as whole tokens.
        if (preg_match_all('/\b\d+(?:\.\d+)+\b/', $lower, $secs)) {
            $tokens = array_merge($tokens, $secs[0]);
        }

        return $tokens;
    }

    // ─── Semantic reranking (perubahan.md #6) ────────────────────────────────

    /** How many top-BM25 candidates get semantically reranked (cost bound). */
    private const SEMANTIC_CANDIDATES = 40;

    /**
     * Blend BM25 scores with embedding cosine similarity for the top lexical
     * candidates. 50/50 blend: lexical grounding is kept (exact numbers, NIM,
     * names) while paraphrased questions stop missing relevant chunks.
     * Returns the original scores untouched when embeddings are unavailable.
     *
     * @param array<int, array{text: string, position: int}> $chunks
     * @param array<int, float> $scores
     * @return array<int, float>
     */
    private function blendWithEmbeddings(array $chunks, string $query, array $scores): array
    {
        if (!$this->embeddingsAvailable()) {
            return $scores;
        }

        $ranked = $scores;
        arsort($ranked);
        $candidateIdx = array_slice(array_keys($ranked), 0, self::SEMANTIC_CANDIDATES);
        if (empty($candidateIdx)) {
            return $scores;
        }

        // One batch: query first, then candidate chunk texts (truncated —
        // embeddings don't need the whole chunk to capture its meaning).
        $texts = [mb_substr($query, 0, 1000)];
        foreach ($candidateIdx as $i) {
            $texts[] = mb_substr($chunks[$i]['text'], 0, 1500);
        }

        $vectors = $this->embedTexts($texts);
        if ($vectors === null || count($vectors) !== count($texts)) {
            \Illuminate\Support\Facades\Log::warning('Semantic RAG: embedding call failed — falling back to pure BM25.');
            return $scores;
        }

        // Visible proof in storage/logs/laravel.log that rynude Sense is working.
        \Illuminate\Support\Facades\Log::info('Semantic RAG active: reranked ' . count($candidateIdx) . ' chunks with embeddings for query "' . mb_substr($query, 0, 80) . '"');

        $queryVec = $vectors[0];
        $maxBm25 = max(0.0001, max(array_map(fn ($i) => $scores[$i], $candidateIdx)));

        $blended = $scores;
        foreach ($candidateIdx as $k => $i) {
            $cos = max(0.0, $this->cosine($queryVec, $vectors[$k + 1]));
            $lex = $scores[$i] / $maxBm25; // 0..1
            // +1000 keeps every reranked candidate above the non-candidates,
            // whose raw BM25 scores live on a different scale.
            $blended[$i] = 1000 + (0.5 * $lex + 0.5 * $cos);
        }

        return $blended;
    }

    /** Cosine similarity between two equal-length vectors. */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        return ($na > 0 && $nb > 0) ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }

    /**
     * Is the local GGUF engine up WITH an embedding model loaded? Cached for
     * 60s so retrieval doesn't ping /health on every chunk lookup.
     */
    private function embeddingsAvailable(): bool
    {
        return (bool) Cache::remember('rag_embed_available', 60, function () {
            try {
                $port = (int) config('services.local_gguf.port', 8091);
                $resp = \Illuminate\Support\Facades\Http::timeout(2)
                    ->get("http://127.0.0.1:{$port}/health");
                return $resp->ok() && ($resp->json('embed') === true);
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

    /**
     * Batch-embed texts via the local engine, with a per-text cache (a chunk's
     * vector never changes, so repeated questions on the same document only
     * embed the query). Returns null on any failure → caller falls back to BM25.
     *
     * @param string[] $texts
     * @return array<int, array<int, float>>|null
     */
    private function embedTexts(array $texts): ?array
    {
        try {
            $port = (int) config('services.local_gguf.port', 8091);

            // Resolve cache hits; collect misses for one batch call.
            $result = [];
            $missing = [];
            foreach ($texts as $idx => $text) {
                $key = 'rag_vec_' . md5($text);
                $cached = Cache::get($key);
                if (is_array($cached)) {
                    $result[$idx] = $cached;
                } else {
                    $missing[$idx] = $text;
                }
            }

            if (!empty($missing)) {
                $resp = \Illuminate\Support\Facades\Http::timeout(30)
                    ->post("http://127.0.0.1:{$port}/v1/embeddings", ['input' => array_values($missing)]);
                if (!$resp->ok()) {
                    return null;
                }
                $data = $resp->json('data');
                if (!is_array($data) || count($data) !== count($missing)) {
                    return null;
                }
                $missIdx = array_keys($missing);
                foreach ($data as $j => $row) {
                    $vec = $row['embedding'] ?? null;
                    if (!is_array($vec)) {
                        return null;
                    }
                    $idx = $missIdx[$j];
                    $result[$idx] = $vec;
                    Cache::put('rag_vec_' . md5($texts[$idx]), $vec, 86400);
                }
            }

            ksort($result);
            return array_values($result);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
