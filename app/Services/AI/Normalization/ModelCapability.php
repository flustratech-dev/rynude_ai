<?php

namespace App\Services\AI\Normalization;

/**
 * Capability flags reported by a ModelAdapter for the model it wraps.
 *
 * Used by the pipeline (Sprint 1 Architecture §1.2) to decide whether a stage
 * may request thinking, native tool use, structured JSON output, or images.
 */
final class ModelCapability
{
    public function __construct(
        public readonly bool $thinking,
        public readonly bool $nativeTools,
        public readonly bool $jsonMode,
        public readonly bool $vision,
        public readonly int $maxContextTokens,
    ) {}

    public function toArray(): array
    {
        return [
            'thinking' => $this->thinking,
            'native_tools' => $this->nativeTools,
            'json_mode' => $this->jsonMode,
            'vision' => $this->vision,
            'max_ctx' => $this->maxContextTokens,
        ];
    }
}
