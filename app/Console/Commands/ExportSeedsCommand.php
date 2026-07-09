<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

/**
 * Export the USER questions from chat history as LoRA training "seeds"
 * (rancangan loRA.md, Bab 5.3). Only the prompts are exported — never the
 * assistant answers — because assistant answers may have come from Claude/GPT,
 * whose output cannot legally be used to train a model for sale (Bab 2). The
 * teacher model regenerates fresh answers to these seeds later.
 *
 * Output: JSONL, one seed per line, PII scrubbed:
 *   {"prompt":"buatkan skripsi tentang ...","category":"skripsi"}
 *
 *   php artisan rynude:export-seeds
 *   php artisan rynude:export-seeds --out=training/seeds.jsonl --min-len=8
 */
class ExportSeedsCommand extends Command
{
    protected $signature = 'rynude:export-seeds
        {--out=training/seeds.jsonl : Output JSONL path (relative to project root)}
        {--min-len=6 : Skip prompts shorter than this many characters}
        {--max=5000 : Maximum number of seeds to export}';

    protected $description = 'Export user chat prompts as de-identified LoRA training seeds (JSONL)';

    public function handle(): int
    {
        $out = $this->option('out');
        $minLen = (int) $this->option('min-len');
        $max = (int) $this->option('max');

        $path = base_path($out);
        @mkdir(dirname($path), 0755, true);

        $seen = [];
        $count = 0;
        $handle = fopen($path, 'w');
        if ($handle === false) {
            $this->error("Cannot write to {$path}");
            return self::FAILURE;
        }

        Message::where('role', 'user')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$seen, &$count, $handle, $minLen, $max) {
                foreach ($rows as $msg) {
                    if ($count >= $max) {
                        return false;
                    }
                    $text = $this->clean((string) $msg->content);
                    if (mb_strlen($text) < $minLen) {
                        continue;
                    }
                    $dedupKey = mb_strtolower(preg_replace('/\s+/', ' ', $text));
                    if (isset($seen[$dedupKey])) {
                        continue;
                    }
                    $seen[$dedupKey] = true;

                    fwrite($handle, json_encode([
                        'prompt' => $text,
                        'category' => $this->categorize($text),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                    $count++;
                }
            });

        fclose($handle);

        $this->info("Exported {$count} de-identified seeds → {$out}");
        if ($count === 0) {
            $this->warn('No seeds found. Chat history is empty — seeds accumulate as you use the app; re-run later.');
        }
        $this->line('Next: feed these to the teacher model in training/build_dataset.py (see training/README.md).');

        return self::SUCCESS;
    }

    /** Scrub obvious PII so exported seeds are safe to process/share. */
    private function clean(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        // Emails, phone numbers, long digit runs (NIM/KTP), @handles.
        $text = preg_replace('/[\w.+-]+@[\w.-]+\.\w+/', '[email]', $text) ?? $text;
        $text = preg_replace('/\b(?:\+?62|0)8\d{8,12}\b/', '[hp]', $text) ?? $text;
        $text = preg_replace('/\b\d{8,}\b/', '[angka]', $text) ?? $text;

        return $text;
    }

    /** Rough category tag so the dataset mix (Bab 5.2) can be balanced later. */
    private function categorize(string $text): string
    {
        $t = mb_strtolower($text);
        return match (true) {
            (bool) preg_match('/\b(skripsi|tesis|thesis|tugas akhir|proposal|makalah|laporan|jurnal)\b/', $t) => 'akademik',
            (bool) preg_match('/\b(kode|code|program|fungsi|function|error|bug|python|php|javascript|sql)\b/', $t) => 'coding',
            (bool) preg_match('/\b(pdf|docx|dokumen|artifact|buatkan|generate)\b/', $t) => 'dokumen',
            (bool) preg_match('/\b(harga|terbaru|berita|sekarang|kapan|siapa|dimana)\b/', $t) => 'faktual',
            default => 'umum',
        };
    }
}
