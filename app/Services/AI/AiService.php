<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;

class AiService
{
    /**
     * Resolve the appropriate provider based on the model name.
     */
    public function resolveProvider(string $model): LLMProviderInterface
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->use_proxy) {
            // If proxy is enabled, all models route through OpenAI-compatible provider
            return new OpenAIProvider();
        }

        // Check if the model is registered with an explicit provider
        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        if ($aiModel && $aiModel->provider === 'huggingface') {
            return new OpenAIProvider();
        }
        if ($aiModel && $aiModel->provider === 'google') {
            return new GoogleProvider();
        }
        if ($aiModel && $aiModel->provider === 'mistral') {
            return new MistralProvider();
        }

        // Route by model-name prefix for Google (Gemini) and Mistral models.
        if (str_starts_with($model, 'gemini')) {
            return new GoogleProvider();
        }
        if (str_starts_with($model, 'mistral') || str_starts_with($model, 'magistral') || str_starts_with($model, 'ministral') || str_starts_with($model, 'open-mistral') || str_starts_with($model, 'open-mixtral') || str_starts_with($model, 'codestral')) {
            return new MistralProvider();
        }

        // Hardcode Kiro/9Router models to always use OpenAI Provider (since 9Router uses OpenAI compatible endpoint)
        if (str_starts_with($model, 'kr/claude')) {
            return new OpenAIProvider();
        }

        if (str_starts_with($model, 'claude')) {
            return new AnthropicProvider();
        }

        if (str_starts_with($model, 'gpt')) {
            return new OpenAIProvider();
        }

        // Default or generic fallback
        return new OpenAIProvider();
    }

    /**
     * Stream a response using the appropriate provider.
     */
    public function streamResponse(array $messages, string $model): \Generator
    {
        $provider = $this->resolveProvider($model);
        return $provider->streamResponse($messages, $model);
    }
}
