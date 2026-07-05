<?php

namespace App\Services\AI;

use App\Models\ProviderWebToken;
use App\Services\AI\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Auth;

class AiService
{
    /**
     * Resolve the appropriate provider based on the model name.
     */
    public function resolveProvider(string $model): LLMProviderInterface
    {
        $user = Auth::user();

        // Local AI models are always local — resolve first regardless of proxy setting
        $aiModel = \App\Models\AiModel::where('code', $model)->first();
        if ($aiModel && in_array($aiModel->provider, ['ollama', 'local', 'gguf', '9router'])) {
            return new OpenAIProvider();
        }

        if ($user && $user->use_proxy) {
            return new OpenAIProvider();
        }

        // Check if the model is registered with an explicit provider
        if ($aiModel && $aiModel->provider === 'huggingface') {
            return new OpenAIProvider();
        }
        if ($aiModel && $aiModel->provider === 'google') {
            return $this->resolveGoogleProvider($user);
        }
        if ($aiModel && $aiModel->provider === 'mistral') {
            return $this->resolveMistralProvider($user);
        }

        // Route by model-name prefix for Google (Gemini) and Mistral models.
        if (str_starts_with($model, 'gemini')) {
            return $this->resolveGoogleProvider($user);
        }
        if (str_starts_with($model, 'mistral') || str_starts_with($model, 'magistral') || str_starts_with($model, 'ministral') || str_starts_with($model, 'open-mistral') || str_starts_with($model, 'open-mixtral') || str_starts_with($model, 'codestral')) {
            return $this->resolveMistralProvider($user);
        }

        // Hardcode Kiro/9Router models to always use OpenAI Provider
        if (str_starts_with($model, 'kr/claude')) {
            return new OpenAIProvider();
        }

        if (str_starts_with($model, 'claude') || str_starts_with($model, 'fable')) {
            return $this->resolveClaudeProvider($user);
        }

        if (str_starts_with($model, 'gpt')) {
            return $this->resolveOpenAIProvider($user);
        }

        return new OpenAIProvider();
    }

    private function resolveClaudeProvider($user): LLMProviderInterface
    {
        if ($user && !empty($user->anthropic_api_key)) {
            return new AnthropicProvider();
        }
        if ($user && $this->hasWebToken($user, 'claude')) {
            return new WebAiProvider();
        }
        return new AnthropicProvider();
    }

    private function resolveGoogleProvider($user): LLMProviderInterface
    {
        if ($user && !empty($user->google_api_key)) {
            return new GoogleProvider();
        }
        if ($user && $this->hasWebToken($user, 'gemini')) {
            return new WebAiProvider();
        }
        return new GoogleProvider();
    }

    private function resolveOpenAIProvider($user): LLMProviderInterface
    {
        if ($user && !empty($user->openai_api_key)) {
            return new OpenAIProvider();
        }
        if ($user && $this->hasWebToken($user, 'chatgpt')) {
            return new WebAiProvider();
        }
        return new OpenAIProvider();
    }

    private function resolveMistralProvider($user): LLMProviderInterface
    {
        return new MistralProvider();
    }

    private function hasWebToken($user, string $provider): bool
    {
        return ProviderWebToken::where('user_id', $user->id)
            ->where('provider', $provider)
            ->where('status', 'active')
            ->exists();
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
