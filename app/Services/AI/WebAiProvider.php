<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\LLMProviderInterface;
use App\Services\AI\WebProviders\ChatGPTWebProvider;
use App\Services\AI\WebProviders\ClaudeWebProvider;
use App\Services\AI\WebProviders\GeminiWebProvider;
use Illuminate\Support\Facades\Auth;

class WebAiProvider implements LLMProviderInterface
{
    private array $providerMap = [
        'gpt'    => ChatGPTWebProvider::class,
        'chatgpt' => ChatGPTWebProvider::class,
        'gemini'  => GeminiWebProvider::class,
        'claude'  => ClaudeWebProvider::class,
    ];

    public function streamResponse(array $messages, string $model): \Generator
    {
        $webProvider = $this->resolve($model);

        if (!$webProvider) {
            throw new \RuntimeException("No web provider available for model: $model");
        }

        $result = $webProvider->sendMessage($messages, ['model' => $model]);

        yield $result['content'] ?? json_encode($result);
    }

    public function resolve(string $model): ChatGPTWebProvider|GeminiWebProvider|ClaudeWebProvider|null
    {
        foreach ($this->providerMap as $prefix => $class) {
            if (str_starts_with($model, $prefix)) {
                return $class::forCurrentUser($prefix === 'gpt' ? 'chatgpt' : $prefix);
            }
        }

        return null;
    }

    public static function available(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return \App\Models\ProviderWebToken::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }
}
