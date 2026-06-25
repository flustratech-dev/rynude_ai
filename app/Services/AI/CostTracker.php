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
        'default' => ['input' => 3.0, 'output' => 15.0],
    ];

    private static float $sessionCost = 0.0;
    private static int $sessionInputTokens = 0;
    private static int $sessionOutputTokens = 0;

    /**
     * Track and return cost for a single turn.
     */
    public static function track(string $model, int $inputTokens, int $outputTokens): float
    {
        $rates = self::$pricing[$model] ?? self::$pricing['default'];
        $cost = (($inputTokens * $rates['input']) + ($outputTokens * $rates['output'])) / 1000000.0;

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
