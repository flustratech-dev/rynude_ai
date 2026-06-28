<?php

namespace App\Services\AI\DTO;

final class ArtifactDto
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly string $language,
        public readonly string $title,
        public readonly string $content,
    ) {}

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'type' => $this->type,
            'language' => $this->language,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
