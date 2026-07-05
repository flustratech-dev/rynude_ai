<?php

namespace App\Services\AI;

/**
 * OutputCompressor — Native PHP implementation of RTK (Rust Token Killer) strategies.
 *
 * Compresses shell command output before it is sent to the LLM, reducing token
 * consumption by 60–90% for verbose commands like npm install, phpunit, etc.
 *
 * Four strategies (mirroring RTK):
 *  1. Smart Filtering   — remove command-specific boilerplate & noise lines
 *  2. Deduplication     — collapse consecutive identical/similar lines into [×N]
 *  3. Progress Grouping — collapse spinner/progress bar sequences into one line
 *  4. Intelligent Truncation — context-aware trimming with a meaningful summary
 *
 * Usage:
 *   [$compressed, $stats] = OutputCompressor::compress($rawOutput, $command);
 *   // $stats = ['original_chars' => N, 'compressed_chars' => M, 'saved_pct' => P]
 */
class OutputCompressor
{
    // Hard cap after compression (still generous for the LLM)
    private const MAX_CHARS = 12000;

    // Minimum length before we bother compressing (tiny outputs are fine raw)
    private const MIN_COMPRESS_CHARS = 500;

    // ──────────────────────────────────────────────────────────────────────────
    // Public Entry Point
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Compress terminal output before sending to the LLM.
     *
     * @param  string $raw     Raw stdout + stderr from the process
     * @param  string $command The full shell command that produced the output
     * @return array{0: string, 1: array} [$compressedOutput, $stats]
     */
    public static function compress(string $raw, string $command): array
    {
        $originalChars = strlen($raw);

        // Don't waste CPU on trivially small output
        if ($originalChars < self::MIN_COMPRESS_CHARS) {
            return [$raw, self::makeStats($originalChars, $originalChars)];
        }

        // 1. Strip ANSI escape codes & carriage returns (universal noise)
        $cleaned = self::stripAnsi($raw);

        // 2. Detect command category and apply targeted compression
        $category = self::detectCategory($command);
        $compressed = match ($category) {
            'npm'     => self::compressNpm($cleaned),
            'git'     => self::compressGit($cleaned, $command),
            'test'    => self::compressTest($cleaned),
            'composer'=> self::compressComposer($cleaned),
            'docker'  => self::compressDocker($cleaned),
            'artisan' => self::compressArtisan($cleaned),
            default   => self::compressGeneric($cleaned),
        };

        // 3. Universal post-processing on all categories
        $lines = explode("\n", $compressed);
        $lines = self::collapseBlankLines($lines);
        $lines = self::deduplicateLines($lines);
        $compressed = implode("\n", $lines);

        // 4. Hard cap as a safety net
        $finalChars = strlen($compressed);
        if ($finalChars > self::MAX_CHARS) {
            $compressed = self::hardTruncate($compressed, $command, $category);
            $finalChars = strlen($compressed);
        }

        return [$compressed, self::makeStats($originalChars, $finalChars)];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Category Detection
    // ──────────────────────────────────────────────────────────────────────────

    private static function detectCategory(string $command): string
    {
        $cmd = strtolower(trim($command));

        if (preg_match('/\b(npm|yarn|pnpm|bun)\b/', $cmd))  return 'npm';
        if (preg_match('/\bgit\b/', $cmd))                   return 'git';
        if (preg_match('/\b(phpunit|jest|pytest|cargo test|go test|vitest|mocha)\b/', $cmd)) return 'test';
        if (preg_match('/\bcomposer\b/', $cmd))              return 'composer';
        if (preg_match('/\bdocker\b/', $cmd))                return 'docker';
        if (preg_match('/\bartisan\b/', $cmd))               return 'artisan';

        return 'generic';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Category-Specific Compressors
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * npm / yarn / pnpm — strip progress spinners, redundant warnings, audit noise.
     */
    private static function compressNpm(string $output): string
    {
        $lines  = explode("\n", $output);
        $kept   = [];
        $warnCount = 0;
        $warnExamples = [];
        $progressCount = 0;

        // Patterns to suppress (noise)
        $noisePatterns = [
            '/^\s*$/',                                         // blank lines (handled later by collapseBlankLines)
            '/^[⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏▐▌▀▄▖▗▘▝▚▞]/u',               // spinner characters
            '/^\s*([\d.]+%|\d+\s*\/\s*\d+)\s*(?:complete|done)?/i', // progress %
            '/idealTree/',                                     // npm internal tree phases
            '/sill\s+/i',                                      // npm verbose sill lines
            '/verb\s+/i',                                      // npm verbose verb lines
            '/http (GET|HEAD|fetch)/i',                        // npm registry fetches
            '/\d+\s+packages\s+are\s+looking\s+for\s+funding/i',
            '/run\s+`npm\s+fund`/i',
            '/^\s*npm notice/i',
        ];

        foreach ($lines as $line) {
            $isNoise = false;
            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isNoise = true;
                    break;
                }
            }

            // Aggregate deprecation warnings instead of showing all
            if (!$isNoise && preg_match('/^\s*npm warn deprecated\s+(.+?):/i', $line, $m)) {
                $warnCount++;
                if (count($warnExamples) < 3) {
                    $warnExamples[] = trim($m[1]);
                }
                continue;
            }

            if (!$isNoise) {
                $kept[] = $line;
            } else {
                $progressCount++;
            }
        }

        // Inject deprecation summary if we suppressed any
        if ($warnCount > 0) {
            $examples = implode(', ', $warnExamples);
            $more = $warnCount > count($warnExamples) ? ' (+' . ($warnCount - count($warnExamples)) . ' more)' : '';
            array_unshift($kept, "npm warn deprecated: {$warnCount} packages ({$examples}{$more})");
        }

        return implode("\n", $kept);
    }

    /**
     * git — preserve meaningful output, strip hints/suggestions/lock warnings.
     */
    private static function compressGit(string $output, string $command): string
    {
        $subcmd = strtolower(trim(str_ireplace('git', '', $command)));
        $lines  = explode("\n", $output);
        $kept   = [];

        // For git diff — keep almost everything (AI needs to read the actual diff)
        if (str_contains($subcmd, 'diff')) {
            foreach ($lines as $line) {
                // Only strip ANSI and trailing whitespace; preserve all diff content
                $kept[] = rtrim($line);
            }
            return implode("\n", $kept);
        }

        $noisePatterns = [
            '/^hint:/i',
            '/^For more information/i',
            '/^See also:/i',
            '/^warning: LF will be replaced/i',
            '/^warning: CRLF will be replaced/i',
            '/Waiting for your editor/i',
            '/^\s*#\s*On branch/i',  // redundant in some git versions
            '/^Counting objects:/i',
            '/^Compressing objects:/i',
            '/^Writing objects:/i',
            '/^Delta compression using/i',
            '/^remote:\s+Counting/i',
            '/^remote:\s+Compressing/i',
            '/Resolving deltas:/i',
        ];

        foreach ($lines as $line) {
            $isNoise = false;
            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isNoise = true;
                    break;
                }
            }
            if (!$isNoise) {
                $kept[] = $line;
            }
        }

        // For git log — limit to 20 entries max (avoid massive history dumps)
        if (str_contains($subcmd, 'log')) {
            $commitLines = array_filter($kept, fn($l) => preg_match('/^commit [a-f0-9]{40}/i', $l));
            if (count($commitLines) > 20) {
                $truncateAt = 0;
                $commitSeen = 0;
                foreach ($kept as $i => $line) {
                    if (preg_match('/^commit [a-f0-9]{40}/i', $line)) {
                        $commitSeen++;
                        if ($commitSeen > 20) {
                            $truncateAt = $i;
                            break;
                        }
                    }
                }
                if ($truncateAt > 0) {
                    $kept = array_slice($kept, 0, $truncateAt);
                    $kept[] = '... [log truncated at 20 commits]';
                }
            }
        }

        return implode("\n", $kept);
    }

    /**
     * Test runners — PHPUnit, Jest, pytest, etc.
     * Strategy: only keep failures + final summary line.
     */
    private static function compressTest(string $output): string
    {
        $lines = explode("\n", $output);
        $kept  = [];

        // Patterns that are header/footer noise
        $noisePatterns = [
            '/^PHPUnit \d+\.\d+/i',
            '/^Runtime:\s+PHP/i',
            '/^Configuration:/i',
            '/^Random Seed:/i',
            '/^JEST|jest-circus/i',
            '/^Test Suites?:/i',
        ];

        // Detect if there are any failures
        $hasFailures = preg_match('/FAIL(ED|URES?)|Error[s]?:|ERRORS?|✗|✘|×/i', $output);

        foreach ($lines as $line) {
            // Always keep failure lines, stack traces, assertions
            if (preg_match('/FAIL|Error|Exception|ERRORS|✗|✘|×|^\s+at\s|^\s+#\d+/i', $line)) {
                $kept[] = $line;
                continue;
            }

            // Keep the final summary lines (OK, FAILURES, Tests: N)
            if (preg_match('/^(OK|FAILURES?|Tests?:|Time:|Memory:|PASS|FAIL)\b/i', $line)) {
                $kept[] = $line;
                continue;
            }

            // If all tests pass, skip individual dot progress lines
            if (!$hasFailures && preg_match('/^[.FEW\s]+\s+\d+\s*\/\s*\d+/', $line)) {
                continue; // skip dot-progress lines when all passing
            }

            // Skip header noise
            $isNoise = false;
            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isNoise = true;
                    break;
                }
            }

            if (!$isNoise) {
                $kept[] = $line;
            }
        }

        // If we stripped too much (empty result), return a one-liner summary
        if (empty(array_filter($kept))) {
            // Extract numbers from original output
            if (preg_match('/(\d+) tests?.*?(\d+) assertions?/i', $output, $m)) {
                return "Tests: {$m[1]} tests, {$m[2]} assertions — " . ($hasFailures ? 'FAILED' : 'OK');
            }
        }

        return implode("\n", $kept);
    }

    /**
     * composer — strip download progress, hash checks, lock file notices.
     */
    private static function compressComposer(string $output): string
    {
        $lines = explode("\n", $output);
        $kept  = [];

        $noisePatterns = [
            '/^\s*-\s+Downloading\s+/i',
            '/^\s*-\s+Installing\s+.*\(Downloading\)/i',
            '/Checking platform requirements/i',
            '/Writing lock file/i',
            '/Generating optimized autoload files/i',
            '/Generated optimized autoload files/i',
            '/\d+\/\d+\s*\[.*?\]\s*\d+%/i',  // progress bars
        ];

        foreach ($lines as $line) {
            $isNoise = false;
            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isNoise = true;
                    break;
                }
            }
            // Keep package install lines (just strip the "(Downloading)" suffix)
            if (!$isNoise) {
                // Normalize: "- Installing foo/bar (1.2.3): Extracting archive"
                // → "- Installing foo/bar (1.2.3)"
                $line = preg_replace('/:\s+(Extracting archive|Loading from cache|Cloning|Downloading)[^$]*/i', '', $line) ?? $line;
                $kept[] = rtrim($line);
            }
        }

        return implode("\n", $kept);
    }

    /**
     * docker — strip layer download progress (the most verbose noise in Docker).
     */
    private static function compressDocker(string $output): string
    {
        $lines = explode("\n", $output);
        $kept  = [];
        $layerGroups = [];

        foreach ($lines as $line) {
            // Docker layer lines: "abc123def: Pulling fs layer" / "abc123def: Pull complete"
            if (preg_match('/^([a-f0-9]{12}): (.+)$/i', $line, $m)) {
                $layerId = $m[1];
                $status  = $m[2];
                // Only keep the final state (Pull complete / Already exists)
                if (preg_match('/Pull complete|Already exists|Layer already exists/i', $status)) {
                    $layerGroups[$layerId] = $layerId . ': ' . trim($status);
                } elseif (!isset($layerGroups[$layerId])) {
                    $layerGroups[$layerId] = $line; // keep first occurrence
                }
                continue;
            }
            // Skip raw byte progress lines
            if (preg_match('/\d+(\.\d+)?(B|kB|MB|GB)\/\d+(\.\d+)?(B|kB|MB|GB)/i', $line)) {
                continue;
            }
            $kept[] = $line;
        }

        // Inject the collapsed layer summary
        if (!empty($layerGroups)) {
            $kept = array_merge(['[Docker layers]'], array_values($layerGroups), $kept);
        }

        return implode("\n", $kept);
    }

    /**
     * artisan — strip migration already run notices, cache lines, etc.
     */
    private static function compressArtisan(string $output): string
    {
        $lines = explode("\n", $output);
        $kept  = [];

        $noisePatterns = [
            '/Nothing to migrate/i',
            '/^INFO\s+Preparing database/i',
            '/Running migrations/i',
            '/^\s{2,}[0-9_]+_.*\.php\.{3,}/i', // long dotted migration lines
        ];

        foreach ($lines as $line) {
            $isNoise = false;
            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isNoise = true;
                    break;
                }
            }
            if (!$isNoise) {
                $kept[] = $line;
            }
        }

        return implode("\n", $kept);
    }

    /**
     * Generic compressor for unrecognized commands.
     */
    private static function compressGeneric(string $output): string
    {
        $lines = explode("\n", $output);
        $kept  = [];

        foreach ($lines as $line) {
            // Strip spinner characters at line start
            $line = preg_replace('/^[⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏]+\s*/u', '', $line) ?? $line;
            // Strip percentage-only progress lines
            if (preg_match('/^\s*\d+(\.\d+)?%\s*$/', trim($line))) {
                continue;
            }
            $kept[] = $line;
        }

        return implode("\n", $kept);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Universal Post-Processing Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Strip ANSI escape sequences and carriage returns from a string.
     */
    private static function stripAnsi(string $text): string
    {
        // Remove ANSI escape codes
        $text = preg_replace('/\x1B(?:[@-Z\\-_]|\[[0-?]*[ -\/]*[@-~])/', '', $text) ?? $text;
        // Remove carriage returns (terminal animation artifacts)
        $text = str_replace("\r\n", "\n", $text);
        $text = preg_replace('/\r[^\n]/', '', $text) ?? $text;
        return $text;
    }

    /**
     * Collapse 3+ consecutive blank lines down to 1.
     */
    private static function collapseBlankLines(array $lines): array
    {
        $result      = [];
        $blankCount  = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                $blankCount++;
                if ($blankCount <= 1) {
                    $result[] = '';
                }
            } else {
                $blankCount = 0;
                $result[]   = $line;
            }
        }

        return $result;
    }

    /**
     * Collapse consecutive duplicate lines into "line [×N]".
     * Also collapses lines that differ only in a trailing number/percentage.
     */
    private static function deduplicateLines(array $lines): array
    {
        if (empty($lines)) return $lines;

        $result  = [];
        $prev    = null;
        $count   = 1;

        foreach ($lines as $line) {
            // Normalize the line for comparison: strip trailing numbers/% so
            // "Downloading... 10%" and "Downloading... 90%" both collapse.
            $normalized = preg_replace('/[\d.,]+\s*(%|MB|KB|B|s|ms)?\s*$/', '', trim($line));

            if ($prev !== null && $normalized === $prev && $normalized !== '') {
                $count++;
            } else {
                if ($prev !== null) {
                    // Flush previous
                    $lastLine = end($result);
                    if ($count > 1) {
                        // Replace the last pushed line with the annotated version
                        array_pop($result);
                        $result[] = $lastLine . ' [×' . $count . ']';
                    }
                }
                $result[] = $line;
                $count    = 1;
                $prev     = $normalized;
            }
        }

        // Flush the final group
        if ($count > 1) {
            $lastLine = array_pop($result);
            $result[] = $lastLine . ' [×' . $count . ']';
        }

        return $result;
    }

    /**
     * Hard truncate with a tail-first strategy (keep the end of output,
     * which usually has the most relevant info like summaries/errors).
     */
    private static function hardTruncate(string $text, string $command, string $category): string
    {
        // Keep the last MAX_CHARS characters (tail of output is most useful)
        $tail    = mb_substr($text, -self::MAX_CHARS);
        $firstNl = strpos($tail, "\n");
        if ($firstNl !== false) {
            $tail = substr($tail, $firstNl + 1);
        }

        $originalLines = substr_count($text, "\n") + 1;
        $keptLines     = substr_count($tail, "\n") + 1;
        $skipped       = $originalLines - $keptLines;

        return "... [{$skipped} lines of {$category} output hidden — showing final {$keptLines} lines]\n" . $tail;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Stats Helper
    // ──────────────────────────────────────────────────────────────────────────

    private static function makeStats(int $original, int $compressed): array
    {
        $savedPct = $original > 0
            ? (int) round((1 - $compressed / $original) * 100)
            : 0;

        return [
            'original_chars'   => $original,
            'compressed_chars' => $compressed,
            'saved_pct'        => max(0, $savedPct),
        ];
    }
}
