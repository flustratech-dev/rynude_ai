<?php

namespace App\Services\AI\DTO;

final class NormalizedOutput
{
    /**
     * @param ToolCallDto[] $toolCalls
     * @param CitationDto[] $citations
     * @param array<string, bool> $flags
     */
    public function __construct(
        public readonly string $visibleText,
        public readonly ?ArtifactDto $artifact = null,
        public readonly ?ReasoningTrace $reasoning = null,
        public readonly array $toolCalls = [],
        public readonly array $citations = [],
        public readonly array $flags = [],
    ) {}
}
