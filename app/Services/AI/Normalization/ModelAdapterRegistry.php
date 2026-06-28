<?php

namespace App\Services\AI\Normalization;

use App\Services\AI\Normalization\Adapters\AnthropicAdapter;

/**
 * Resolves a model code to its ModelAdapter.
 *
 * Sprint 1 Step 3 only registers the Anthropic family. OpenAI, Google, and
 * Mistral adapters land in Step 4 — until then unknown models throw, which
 * is preferable to a silent fallback that hides missing wiring.
 *
 * Routing mirrors AiService::resolveProvider so the pipeline picks the same
 * provider that the legacy ChatInterface code path does, except the proxy
 * (use_proxy) and kr/claude routes are intentionally left to Step 4 when
 * OpenAIAdapter exists.
 */
final class ModelAdapterRegistry
{
    public function for(string $model): ModelAdapter
    {
        if ($this->isAnthropicModel($model)) {
            return new AnthropicAdapter($model);
        }

        throw new \InvalidArgumentException(
            "No ModelAdapter registered for model: {$model}"
        );
    }

    public function supports(string $model): bool
    {
        return $this->isAnthropicModel($model);
    }

    private function isAnthropicModel(string $model): bool
    {
        if (str_starts_with($model, 'kr/')) {
            return false;
        }
        return str_starts_with($model, 'claude');
    }
}
