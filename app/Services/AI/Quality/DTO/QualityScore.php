<?php

namespace App\Services\AI\Quality\DTO;

final class QualityScore
{
    /**
     * @param array<string, string> $notes
     */
    public function __construct(
        public readonly int $accuracy,
        public readonly int $completeness,
        public readonly int $consistency,
        public readonly int $academicQuality,
        public readonly int $formatting,
        public readonly int $overall,
        public readonly int $threshold,
        public readonly bool $passed,
        public readonly string $rubricVersion = 'v5.1.0',
        public readonly string $scorerModel = '',
        public readonly array $notes = [],
    ) {}

    public function toArray(): array
    {
        return [
            'accuracy' => $this->accuracy,
            'completeness' => $this->completeness,
            'consistency' => $this->consistency,
            'academic_quality' => $this->academicQuality,
            'formatting' => $this->formatting,
            'overall' => $this->overall,
            'threshold' => $this->threshold,
            'passed' => $this->passed,
            'rubric_version' => $this->rubricVersion,
            'scorer_model' => $this->scorerModel,
            'notes' => $this->notes,
        ];
    }
}
