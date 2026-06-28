<?php

namespace App\Services\AI\Coordinator;

final class GenerationOptions
{
    public function __construct(
        public readonly string $mode = 'auto',
        public readonly ?int $qualityThresholdOverride = null,
        public readonly int $maxRegenerations = 2,
        public readonly bool $enableThinking = true,
        public readonly bool $enableQualityScoring = true,
        public readonly bool $persistStages = true,
    ) {}
}
