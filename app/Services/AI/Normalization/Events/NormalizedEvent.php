<?php

namespace App\Services\AI\Normalization\Events;

final class NormalizedEvent
{
    public function __construct(
        public readonly string $type,
        public readonly array $payload,
    ) {}

    public static function text(string $text): self
    {
        return new self('text', ['text' => $text]);
    }

    public static function thinking(string $text): self
    {
        return new self('thinking', ['text' => $text]);
    }

    public static function toolUse(string $id, string $name, array $input): self
    {
        return new self('tool_use', ['id' => $id, 'name' => $name, 'input' => $input]);
    }

    public static function usage(int $tokensIn, int $tokensOut, int $cacheReadTokens = 0, int $cacheWriteTokens = 0): self
    {
        return new self('usage', [
            'tokens_in' => $tokensIn,
            'tokens_out' => $tokensOut,
            'cache_read_tokens' => $cacheReadTokens,
            'cache_write_tokens' => $cacheWriteTokens,
        ]);
    }

    public static function stage(string $name, string $status, array $extra = []): self
    {
        return new self('stage', array_merge(['name' => $name, 'status' => $status], $extra));
    }

    public static function score(int $overall, int $threshold, bool $passed): self
    {
        return new self('score', [
            'overall' => $overall,
            'threshold' => $threshold,
            'passed' => $passed,
        ]);
    }

    public static function done(array $extra = []): self
    {
        return new self('done', $extra);
    }

    public static function error(string $message): self
    {
        return new self('error', ['message' => $message]);
    }

    public static function reset(): self
    {
        return new self('reset', []);
    }
}
