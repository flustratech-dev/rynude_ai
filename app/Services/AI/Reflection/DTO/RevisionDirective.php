<?php

namespace App\Services\AI\Reflection\DTO;

final class RevisionDirective
{
    public function __construct(
        public readonly string $area,
        public readonly string $instruction,
        public readonly string $severity = 'medium',
    ) {}

    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'instruction' => $this->instruction,
            'severity' => $this->severity,
        ];
    }
}
