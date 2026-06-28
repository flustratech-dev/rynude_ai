<?php

namespace App\Services\AI\DTO;

final class ToolCallDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $input,
    ) {}
}
