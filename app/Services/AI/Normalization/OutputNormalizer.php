<?php

namespace App\Services\AI\Normalization;

use App\Services\AI\DTO\NormalizedOutput;
use App\Services\AI\Normalization\Extractors\ArtifactExtractor;
use App\Services\AI\Normalization\Extractors\CitationExtractor;
use App\Services\AI\Normalization\Extractors\ReasoningExtractor;
use App\Services\AI\Normalization\Extractors\RefusalDetector;

class OutputNormalizer
{
    public function __construct(
        private ArtifactExtractor $artifactExtractor,
        private ReasoningExtractor $reasoningExtractor,
        private CitationExtractor $citationExtractor,
        private RefusalDetector $refusalDetector
    ) {}

    public function normalize(string $rawText, array $toolCalls = []): NormalizedOutput
    {
        $isRefusal = $this->refusalDetector->detect($rawText);
        
        $reasoningResult = $this->reasoningExtractor->extract($rawText);
        $text = $reasoningResult['cleanText'];
        $reasoning = $reasoningResult['reasoning'];
        
        $artifactResult = $this->artifactExtractor->extract($text);
        $text = $artifactResult['cleanText'];
        $artifact = $artifactResult['artifact'];
        
        $citationResult = $this->citationExtractor->extract($text);
        $text = $citationResult['cleanText'];
        $citations = $citationResult['citations'];
        
        return new NormalizedOutput(
            visibleText: $text,
            artifact: $artifact,
            reasoning: $reasoning,
            toolCalls: $toolCalls,
            citations: $citations,
            flags: ['is_refusal' => $isRefusal]
        );
    }
}
