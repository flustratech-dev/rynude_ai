<?php

namespace App\Services\AI\Normalization;

use App\Services\AI\Normalization\Adapters\AnthropicAdapter;
use App\Services\AI\Normalization\Adapters\GoogleAdapter;
use App\Services\AI\Normalization\Adapters\MistralAdapter;
use App\Services\AI\Normalization\Adapters\OpenAIAdapter;

/**
 * Resolves a model code to its ModelAdapter.
 *
 * Routing mirrors AiService::resolveProvider's prefix logic so the pipeline
 * picks the same provider that the legacy ChatInterface code path does:
 *
 *   gemini*                              → GoogleAdapter
 *   mistral* / magistral* / ministral* /
 *   open-mistral* / open-mixtral* /
 *   codestral* / pixtral*                → MistralAdapter
 *   kr/claude* / mmf/mimo*               → OpenAIAdapter (9Router proxy, OpenAI-compatible)
 *   claude*                              → AnthropicAdapter
 *   gpt* / o1* / o3* / o4* / fallback    → OpenAIAdapter
 *
 * Note: DB-driven routing (AiModel.provider = 'huggingface' | 'ollama' | etc.)
 * and per-user `use_proxy` are intentionally NOT consulted here — those are
 * runtime/user concerns. OpenAIProvider itself handles HF, Ollama, and proxy
 * dispatch internally, so the adapter simply needs to delegate to it. The
 * pipeline supplies the model code; the provider resolves the rest.
 */
final class ModelAdapterRegistry
{
    public function for(string $model): ModelAdapter
    {
        if ($this->isGoogleModel($model)) {
            return new GoogleAdapter($model);
        }
        if ($this->isMistralModel($model)) {
            return new MistralAdapter($model);
        }
        if ($this->isOpenAiRouterModel($model)) {
            return new OpenAIAdapter($model);
        }
        if ($this->isAnthropicModel($model)) {
            return new AnthropicAdapter($model);
        }
        if ($this->isOpenAiModel($model)) {
            return new OpenAIAdapter($model);
        }

        // Fallback mirrors AiService::resolveProvider — unknown codes route to
        // OpenAI, which in turn handles HuggingFace / Ollama / proxy endpoints
        // internally based on the user's settings.
        return new OpenAIAdapter($model);
    }

    public function supports(string $model): bool
    {
        return $this->isGoogleModel($model)
            || $this->isMistralModel($model)
            || $this->isOpenAiRouterModel($model)
            || $this->isAnthropicModel($model)
            || $this->isOpenAiModel($model);
    }

    private function isAnthropicModel(string $model): bool
    {
        if (str_starts_with($model, 'kr/')) {
            return false;
        }
        return str_starts_with($model, 'claude');
    }

    private function isOpenAiModel(string $model): bool
    {
        return str_starts_with($model, 'gpt')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3')
            || str_starts_with($model, 'o4');
    }

    private function isOpenAiRouterModel(string $model): bool
    {
        return str_starts_with($model, 'kr/claude')
            || str_starts_with($model, 'mmf/mimo');
    }

    private function isGoogleModel(string $model): bool
    {
        return str_starts_with($model, 'gemini');
    }

    private function isMistralModel(string $model): bool
    {
        return str_starts_with($model, 'mistral')
            || str_starts_with($model, 'magistral')
            || str_starts_with($model, 'ministral')
            || str_starts_with($model, 'open-mistral')
            || str_starts_with($model, 'open-mixtral')
            || str_starts_with($model, 'codestral')
            || str_starts_with($model, 'pixtral');
    }
}
