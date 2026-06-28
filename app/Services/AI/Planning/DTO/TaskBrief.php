<?php

namespace App\Services\AI\Planning\DTO;

final class TaskBrief
{
    /**
     * @param string[] $constraints
     * @param string[] $successCriteria
     * @param array<string, mixed> $rawSignals
     */
    public function __construct(
        public readonly string $goal,
        public readonly string $audience,
        public readonly string $deliverable,
        public readonly array $constraints,
        public readonly array $successCriteria,
        public readonly string $suggestedMode,
        public readonly array $rawSignals = [],
    ) {}

    public function toArray(): array
    {
        return [
            'goal' => $this->goal,
            'audience' => $this->audience,
            'deliverable' => $this->deliverable,
            'constraints' => $this->constraints,
            'success_criteria' => $this->successCriteria,
            'suggested_mode' => $this->suggestedMode,
            'raw_signals' => $this->rawSignals,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            goal: (string) ($data['goal'] ?? ''),
            audience: (string) ($data['audience'] ?? ''),
            deliverable: (string) ($data['deliverable'] ?? 'chat'),
            constraints: (array) ($data['constraints'] ?? []),
            successCriteria: (array) ($data['success_criteria'] ?? []),
            suggestedMode: (string) ($data['suggested_mode'] ?? 'chat'),
            rawSignals: (array) ($data['raw_signals'] ?? []),
        );
    }
}
