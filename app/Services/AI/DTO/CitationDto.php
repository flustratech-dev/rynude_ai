<?php

namespace App\Services\AI\DTO;

final class CitationDto
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $url = null,
        public readonly ?string $title = null,
    ) {}
}
