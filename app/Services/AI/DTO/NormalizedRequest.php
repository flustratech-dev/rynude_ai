<?php

namespace App\Services\AI\DTO;

final class NormalizedRequest
{
    public function __construct(
        public readonly string $systemPrompt,
        public readonly array $messages,
        public readonly array $tools = [],
        public readonly float $temperature = 0.7,
        public readonly ?int $maxTokens = null,
        public readonly ?int $thinkingBudget = null,
        public readonly bool $jsonMode = false,
        public readonly ?string $jsonSchema = null,
    ) {}
}
