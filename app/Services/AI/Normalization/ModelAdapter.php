<?php

namespace App\Services\AI\Normalization;

use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\Normalization\Events\NormalizedEvent;

/**
 * Provider-agnostic adapter base. Each subclass wraps an existing
 * LLMProviderInterface implementation and translates its native event stream
 * into a uniform sequence of NormalizedEvent objects.
 *
 * Adapters are model-scoped: ModelAdapterRegistry constructs an instance per
 * model code so capabilities() can vary across models from the same provider
 * (e.g. claude-haiku-* has no extended thinking; sonnet/opus do).
 *
 * Design invariant (Sprint 1 §1.3): underlying providers are NOT rewritten.
 * The adapter is the only translation surface.
 */
abstract class ModelAdapter
{
    public function __construct(
        public readonly string $model,
    ) {}

    abstract public function capabilities(): ModelCapability;

    /**
     * Stream a completion for the given normalized request.
     *
     * @return \Generator<int, NormalizedEvent>
     */
    abstract public function streamCompletion(NormalizedRequest $req): \Generator;
}
