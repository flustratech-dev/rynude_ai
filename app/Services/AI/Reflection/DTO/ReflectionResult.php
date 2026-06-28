<?php

namespace App\Services\AI\Reflection\DTO;

final class ReflectionResult
{
    /**
     * @param array<string, bool> $checklist
     * @param RevisionDirective[] $directives
     */
    public function __construct(
        public readonly bool $passed,
        public readonly array $checklist,
        public readonly array $directives = [],
    ) {}

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'checklist' => $this->checklist,
            'directives' => array_map(fn (RevisionDirective $d) => $d->toArray(), $this->directives),
        ];
    }
}
