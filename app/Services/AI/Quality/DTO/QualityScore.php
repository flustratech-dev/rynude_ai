<?php

namespace App\Services\AI\Quality\DTO;

class QualityScore
{
    public function __construct(
        public readonly int $overallScore,
        public readonly int $accuracyScore,
        public readonly int $completenessScore,
        public readonly int $consistencyScore,
        public readonly int $formattingScore,
        public readonly string $feedback,
        public readonly string $status // 'PASSED' or 'REQUIRES_IMPROVEMENT'
    ) {}

    public function toArray(): array
    {
        return [
            'overallScore' => $this->overallScore,
            'accuracyScore' => $this->accuracyScore,
            'completenessScore' => $this->completenessScore,
            'consistencyScore' => $this->consistencyScore,
            'formattingScore' => $this->formattingScore,
            'feedback' => $this->feedback,
            'status' => $this->status,
        ];
    }
}
