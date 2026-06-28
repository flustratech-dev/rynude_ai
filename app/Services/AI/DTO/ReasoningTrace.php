<?php

namespace App\Services\AI\DTO;

final class ReasoningTrace
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $signature = null,
    ) {}
}
