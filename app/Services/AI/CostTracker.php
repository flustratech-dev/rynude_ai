<?php

namespace App\Services\AI;

class CostTracker
{
    /**
     * Pricing per 1M tokens in USD.
     */
    private static array $pricing = [
        'claude-3-5-sonnet-20241022' => ['input' => 3.0, 'output' => 15.0],
        'claude-3-5-haiku-20241022' => ['input' => 0.8, 'output' => 4.0],
        'claude-3-opus-20240229' => ['input' => 15.0, 'output' => 75.0],
        'claude-sonnet-4-6' => ['input' => 3.0, 'output' => 15.0],
        'claude-haiku-4-6' => ['input' => 0.8, 'output' => 4.0],
        'claude-sonnet-5' => ['input' => 3.0, 'output' => 15.0],
        'fable-5' => ['input' => 15.0, 'output' => 75.0],
        'fugu-ultra' => ['input' => 25.0, 'output' => 100.0],
        'default' => ['input' => 3.0, 'output' => 15.0],
    ];

    private static float $sessionCost = 0.0;
    private static int $sessionInputTokens = 0;
    private static int $sessionOutputTokens = 0;

    /**
     * Track and return cost for a single turn.
     */
    public static function track(string $model, int $inputTokens, int $outputTokens, int $cacheReadTokens = 0, int $cacheWriteTokens = 0): float
    {
        $rates = self::$pricing[$model] ?? self::$pricing['default'];
        
        // Anthropic's inputTokens is the total count, which includes cached tokens.
        // We isolate regular non-cached input tokens.
        $regularInputTokens = max(0, $inputTokens - $cacheReadTokens - $cacheWriteTokens);
        
        $baseRate = $rates['input'];
        $readRate = $baseRate * 0.10;   // Cache read is 10% of base input rate
        $writeRate = $baseRate * 1.25;  // Cache write/creation is 125% of base input rate
        
        $cost = (
            ($regularInputTokens * $baseRate) +
            ($cacheReadTokens * $readRate) +
            ($cacheWriteTokens * $writeRate) +
            ($outputTokens * $rates['output'])
        ) / 1000000.0;

        self::$sessionCost += $cost;
        self::$sessionInputTokens += $inputTokens;
        self::$sessionOutputTokens += $outputTokens;

        return $cost;
    }

    /**
     * Get summary for the current session.
     */
    public static function getSessionSummary(): array
    {
        return [
            'cost' => round(self::$sessionCost, 5),
            'input_tokens' => self::$sessionInputTokens,
            'output_tokens' => self::$sessionOutputTokens,
            'total_tokens' => self::$sessionInputTokens + self::$sessionOutputTokens,
        ];
    }
    
    /**
     * Reset session counters.
     */
    public static function reset(): void
    {
        self::$sessionCost = 0.0;
        self::$sessionInputTokens = 0;
        self::$sessionOutputTokens = 0;
    }
}
