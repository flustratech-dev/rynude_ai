<?php

namespace App\Services\AI;

/**
 * RtkTracker — Session-level accumulator for RTK (Response Token Killer) compression stats.
 *
 * OutputCompressor compresses bash/git command outputs in AgentTools.
 * This class provides a simple static accumulator so the compression stats
 * can be read by the providers when they call TokenUsage::record().
 *
 * Usage:
 *   // In AgentTools after compression:
 *   RtkTracker::add($stats['original_chars'], $stats['compressed_chars']);
 *
 *   // In providers before/after calling TokenUsage::record():
 *   [$saved, $original] = RtkTracker::flushAndGet();
 *   TokenUsage::record($userId, $model, $provider, $input, $output, $saved, $original);
 */
class RtkTracker
{
    private static int $sessionSavedChars = 0;
    private static int $sessionOriginalChars = 0;

    /**
     * Accumulate compression stats from a single OutputCompressor::compress() call.
     *
     * @param int $originalChars  Characters before compression
     * @param int $compressedChars Characters after compression
     */
    public static function add(int $originalChars, int $compressedChars): void
    {
        $saved = max(0, $originalChars - $compressedChars);
        self::$sessionSavedChars    += $saved;
        self::$sessionOriginalChars += $originalChars;
    }

    /**
     * Get current accumulated stats without resetting.
     */
    public static function getStats(): array
    {
        $savedPct = self::$sessionOriginalChars > 0
            ? round(self::$sessionSavedChars / self::$sessionOriginalChars * 100, 1)
            : 0;

        return [
            'saved_chars'    => self::$sessionSavedChars,
            'original_chars' => self::$sessionOriginalChars,
            'saved_pct'      => $savedPct,
        ];
    }

    /**
     * Flush (read + reset) session RTK stats.
     * Returns [$savedChars, $originalChars].
     * Call this in the provider when recording token usage.
     */
    public static function flushAndGet(): array
    {
        $saved    = self::$sessionSavedChars;
        $original = self::$sessionOriginalChars;

        self::$sessionSavedChars    = 0;
        self::$sessionOriginalChars = 0;

        return [$saved, $original];
    }

    /**
     * Reset without reading.
     */
    public static function reset(): void
    {
        self::$sessionSavedChars    = 0;
        self::$sessionOriginalChars = 0;
    }
}
