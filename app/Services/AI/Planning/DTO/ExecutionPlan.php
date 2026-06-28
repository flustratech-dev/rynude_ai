<?php

namespace App\Services\AI\Planning\DTO;

final class ExecutionPlan
{
    /**
     * @param string[] $steps
     * @param string[] $requiredResearch
     * @param string[] $styleRules
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly array $steps,
        public readonly array $requiredResearch = [],
        public readonly array $styleRules = [],
        public readonly ?string $outline = null,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'steps' => $this->steps,
            'required_research' => $this->requiredResearch,
            'style_rules' => $this->styleRules,
            'outline' => $this->outline,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            steps: (array) ($data['steps'] ?? []),
            requiredResearch: (array) ($data['required_research'] ?? []),
            styleRules: (array) ($data['style_rules'] ?? []),
            outline: isset($data['outline']) ? (string) $data['outline'] : null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }
}
