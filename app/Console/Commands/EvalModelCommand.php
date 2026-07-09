<?php

namespace App\Console\Commands;

use App\Services\AI\AiService;
use Illuminate\Console\Command;

/**
 * Eval harness (perubahan.md #9): a FIXED exam paper for AI models.
 *
 * Every prompt and every check in this file is deterministic, so running the
 * same command before and after a change (new weights, new sampling profile,
 * new prompt) produces comparable scores — "the model got smarter" becomes a
 * number instead of a feeling. Results are also written as JSON to
 * storage/app/evals/ so runs can be diffed over time.
 *
 *   php artisan rynude:eval qwen-2.5-1.5b
 *   php artisan rynude:eval qwen-2.5-1.5b --only=artifact
 *
 * IMPORTANT: do not casually edit the prompts/checks — changing the exam
 * invalidates comparisons with older report cards.
 */
class EvalModelCommand extends Command
{
    protected $signature = 'rynude:eval
        {model : Model code to test (e.g. qwen-2.5-1.5b or any cloud model code)}
        {--only= : Run only cases whose id contains this substring}
        {--max-tokens=0 : Override output token cap for every case (0 = per-case default)}';

    protected $description = 'Run the fixed evaluation suite against a model and print a scored report card';

    public function handle(AiService $aiService): int
    {
        $model = (string) $this->argument('model');
        $only = (string) ($this->option('only') ?? '');
        $capOverride = (int) $this->option('max-tokens');

        $cases = array_values(array_filter(
            $this->suite(),
            fn ($c) => $only === '' || str_contains($c['id'], $only)
        ));
        if (empty($cases)) {
            $this->error("No eval cases match --only={$only}");
            return self::FAILURE;
        }

        $this->info("Eval harness — model: {$model} — " . count($cases) . " cases");
        $this->newLine();

        $results = [];
        $totalScore = 0.0;

        foreach ($cases as $case) {
            $started = microtime(true);
            $output = $this->runCase($aiService, $model, $case, $capOverride);
            $seconds = round(microtime(true) - $started, 1);

            [$passed, $failed] = $this->grade($case['checks'], $output);
            $score = count($passed) + count($failed) > 0
                ? round(count($passed) / (count($passed) + count($failed)) * 100)
                : 0;
            $totalScore += $score;

            $status = $score === 100 ? '<info>PASS</info>' : ($score >= 50 ? '<comment>PART</comment>' : '<error>FAIL</error>');
            $this->line(sprintf('%s  %-22s %3d%%  %5.1fs  %s', $status, $case['id'], $score, $seconds, $failed ? 'gagal: ' . implode(', ', $failed) : ''));

            $results[] = [
                'id' => $case['id'],
                'category' => $case['category'],
                'score' => $score,
                'seconds' => $seconds,
                'passed' => $passed,
                'failed' => $failed,
                'output_chars' => strlen($output),
                'output_preview' => mb_substr($output, 0, 400),
            ];
        }

        $finalScore = round($totalScore / count($cases), 1);
        $this->newLine();
        $this->info("NILAI AKHIR: {$finalScore} / 100");

        // Persist the report card so runs can be compared over time.
        $dir = storage_path('app/evals');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'eval-' . preg_replace('/[^a-z0-9.-]+/i', '_', $model) . '-' . date('Ymd-His') . '.json';
        file_put_contents($file, json_encode([
            'model' => $model,
            'ran_at' => date('c'),
            'final_score' => $finalScore,
            'results' => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("Laporan disimpan: {$file}");

        return self::SUCCESS;
    }

    /** Stream one case and return the collected answer text (thinking stripped). */
    private function runCase(AiService $aiService, string $model, array $case, int $capOverride): string
    {
        $messages = [];
        if (!empty($case['system'])) {
            $messages[] = ['role' => 'system', 'content' => $case['system']];
        }
        $messages[] = ['role' => 'user', 'content' => $case['prompt']];

        $maxTokens = $capOverride > 0 ? $capOverride : ($case['max_tokens'] ?? 1024);

        $out = '';
        try {
            foreach ($aiService->streamResponse($messages, $model, ['max_tokens' => $maxTokens]) as $chunk) {
                if (is_string($chunk)) {
                    $out .= $chunk;
                }
            }
        } catch (\Throwable $e) {
            $out .= "\n[EVAL-ERROR: {$e->getMessage()}]";
        }

        // Reasoning belongs to the model's scratchpad, not the graded answer.
        return trim((string) preg_replace('/<(?:thinking|sim_thinking|think)>[\s\S]*?(?:<\/(?:thinking|sim_thinking|think)>|$)/i', '', $out));
    }

    /**
     * @return array{0: string[], 1: string[]} [passed check names, failed check names]
     */
    private function grade(array $checks, string $output): array
    {
        $passed = [];
        $failed = [];
        $mark = function (string $name, bool $ok) use (&$passed, &$failed) {
            $ok ? $passed[] = $name : $failed[] = $name;
        };

        foreach ($checks as $name => $spec) {
            switch ($name) {
                case 'min_chars':
                    $mark("min_chars({$spec})", strlen($output) >= $spec);
                    break;
                case 'max_chars':
                    $mark("max_chars({$spec})", strlen($output) <= $spec);
                    break;
                case 'must_regex':
                    foreach ($spec as $re) {
                        $mark('ada ' . $re, (bool) preg_match($re, $output));
                    }
                    break;
                case 'must_not_regex':
                    foreach ($spec as $re) {
                        $mark('bebas ' . $re, !preg_match($re, $output));
                    }
                    break;
                case 'artifact_closed':
                    $mark('artifact_terbuka', str_contains($output, '<antArtifact'));
                    $mark('artifact_tertutup', str_contains($output, '</antArtifact>'));
                    break;
                case 'no_loop':
                    $mark('tanpa_pengulangan', !$this->hasRepetitionLoop($output));
                    break;
                case 'no_error':
                    $mark('tanpa_error', !preg_match('/\[(Error|EVAL-ERROR)/i', $output));
                    break;
            }
        }

        return [$passed, $failed];
    }

    /** Detect the small-model failure mode: the same chunk of text echoed over and over. */
    private function hasRepetitionLoop(string $output): bool
    {
        $len = strlen($output);
        if ($len < 300) {
            return false;
        }
        for ($pos = 0; $pos + 80 <= $len; $pos += 40) {
            $window = substr($output, $pos, 80);
            if (trim($window) !== '' && substr_count($output, $window) >= 3) {
                return true;
            }
        }
        return false;
    }

    /**
     * The fixed exam paper. Two fixed system prompts (chat & document) are
     * embedded here on purpose — they must NOT track the live app prompts,
     * otherwise scores stop being comparable across time.
     */
    private function suite(): array
    {
        $chatSystem = 'You are Rynude, an intelligent AI assistant. Reply in the user\'s language using clean Markdown.';
        $docSystem = $chatSystem . ' When the user asks for a document (skripsi, makalah, laporan), output the COMPLETE document inside ONE <antArtifact type="text/markdown" title="Judul"> ... </antArtifact> block. Academic documents start with YAML front-matter between --- lines (mode, judul, penulis, nim, prodi, fakultas, universitas, kota, tahun, pembimbing), then full chapters with # headings, ending with DAFTAR PUSTAKA. Never use ```markdown code fences for the document.';

        return [
            [
                'id' => 'chat-sapaan', 'category' => 'chat',
                'system' => $chatSystem,
                'prompt' => 'Halo! Apa kabar?',
                'max_tokens' => 512,
                'checks' => ['min_chars' => 10, 'max_chars' => 2000, 'must_not_regex' => ['/<antArtifact/i'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'chat-penjelasan', 'category' => 'chat',
                'system' => $chatSystem,
                'prompt' => 'Jelaskan perbedaan RAM dan ROM dalam 3 poin singkat.',
                'max_tokens' => 768,
                'checks' => ['min_chars' => 100, 'must_regex' => ['/RAM/i', '/ROM/i'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'nalar-logika', 'category' => 'penalaran',
                'system' => $chatSystem,
                'prompt' => 'Jika semua kucing adalah hewan, dan sebagian hewan berbulu, apakah PASTI semua kucing berbulu? Jawab dengan "ya" atau "tidak" lalu jelaskan singkat.',
                'max_tokens' => 768,
                'checks' => ['must_regex' => ['/\btidak\b/i'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'nalar-hitung', 'category' => 'penalaran',
                'system' => $chatSystem,
                'prompt' => 'Berapa hasil 17 × 24? Tunjukkan cara hitungnya, lalu tulis jawaban akhirnya.',
                'max_tokens' => 768,
                'checks' => ['must_regex' => ['/408/'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'koding-python', 'category' => 'coding',
                'system' => $chatSystem,
                'prompt' => 'Buat fungsi Python bernama is_palindrome(s) yang mengembalikan True jika string s adalah palindrom (abaikan kapitalisasi dan spasi). Sertakan 2 contoh pemakaian.',
                'max_tokens' => 1024,
                'checks' => ['must_regex' => ['/def is_palindrome\s*\(/', '/return/'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'koding-debug', 'category' => 'coding',
                'system' => $chatSystem,
                'prompt' => "Kode PHP ini error \"Undefined array key\": `for (\$i = 0; \$i <= count(\$arr); \$i++) { echo \$arr[\$i]; }`. Apa bugnya dan bagaimana perbaikannya? Tulis kode yang sudah benar.",
                'max_tokens' => 1024,
                'checks' => ['must_regex' => ['/\$i\s*<\s*count/'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'artifact-makalah', 'category' => 'dokumen',
                'system' => $docSystem,
                'prompt' => 'Buatkan makalah singkat tentang dampak media sosial bagi remaja, lengkap dalam satu dokumen.',
                'max_tokens' => 4096,
                'checks' => ['artifact_closed' => true, 'must_regex' => ['/^#\s/m'], 'must_not_regex' => ['/```markdown/'], 'min_chars' => 1500, 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'artifact-skripsi', 'category' => 'dokumen',
                'system' => $docSystem,
                'prompt' => 'Buatkan skripsi lengkap tentang pengaruh penggunaan e-wallet terhadap perilaku konsumtif mahasiswa.',
                'max_tokens' => 8192,
                'checks' => ['artifact_closed' => true, 'must_regex' => ['/mode:\s*skripsi/i', '/BAB I/i', '/DAFTAR PUSTAKA/i'], 'min_chars' => 4000, 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'format-tabel', 'category' => 'format',
                'system' => $chatSystem,
                'prompt' => 'Buat tabel Markdown yang membandingkan 3 bahasa pemrograman dengan kolom: Bahasa, Kelebihan, Kekurangan.',
                'max_tokens' => 1024,
                'checks' => ['must_regex' => ['/\|.+\|.+\|/', '/\|\s*-+/'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'bahasa-terjemah', 'category' => 'bahasa',
                'system' => $chatSystem,
                'prompt' => "Terjemahkan kalimat ini ke bahasa Inggris: 'Ilmu pengetahuan adalah jendela dunia.'",
                'max_tokens' => 512,
                'checks' => ['must_regex' => ['/knowledge|science/i', '/window/i'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'fakta-geografi', 'category' => 'faktual',
                'system' => $chatSystem,
                'prompt' => 'Apa ibu kota Australia?',
                'max_tokens' => 512,
                'checks' => ['must_regex' => ['/canberra/i'], 'must_not_regex' => ['/^sydney/im'], 'no_error' => true],
            ],
            [
                'id' => 'fakta-sejarah', 'category' => 'faktual',
                'system' => $chatSystem,
                'prompt' => 'Siapa presiden pertama Indonesia dan pada tahun berapa ia mulai menjabat?',
                'max_tokens' => 512,
                'checks' => ['must_regex' => ['/s(oe|u)karno/i', '/1945/'], 'no_error' => true],
            ],
            [
                'id' => 'instruksi-ketat', 'category' => 'instruksi',
                'system' => $chatSystem,
                'prompt' => 'Jawab HANYA dengan satu kata, tanpa tanda baca: apa warna langit saat cerah?',
                'max_tokens' => 128,
                'checks' => ['must_regex' => ['/biru/i'], 'max_chars' => 60, 'no_error' => true],
            ],
            [
                'id' => 'bahasa-anti-inggris', 'category' => 'bahasa',
                'system' => $chatSystem,
                'prompt' => 'Tolong jelaskan secara singkat apa itu artificial intelligence dan bagaimana cara kerjanya, tapi jawab wajib pakai bahasa Indonesia yang mudah dipahami.',
                'max_tokens' => 512,
                'checks' => ['must_regex' => ['/kecerdasan|sistem|mesin|belajar|data/i'], 'must_not_regex' => ['/\b(is a|are the|this is|and the|which can)\b/i'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'bahasa-istilah-campur', 'category' => 'bahasa',
                'system' => $chatSystem,
                'prompt' => 'Kenapa saat koding Python sering ketemu error "IndexError: list index out of range"? Jelaskan penyebab utamanya.',
                'max_tokens' => 512,
                'checks' => ['must_regex' => ['/indeks|elemen|panjang|batas/i'], 'must_not_regex' => ['/\b(because the|when you try|this error occurs|out of bounds)\b/i'], 'no_loop' => true, 'no_error' => true],
            ],
            [
                'id' => 'anti-pidato-singkat', 'category' => 'chat',
                'system' => $chatSystem,
                'prompt' => 'oke paham makasih ya',
                'max_tokens' => 256,
                'checks' => ['max_chars' => 300, 'must_not_regex' => ['/sebagai model AI/i', '/kemampuan saya/i'], 'no_loop' => true, 'no_error' => true],
            ],
        ];
    }
}
