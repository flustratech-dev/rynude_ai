<?php

namespace App\Services\AI\WebProviders;

class GeminiWebProvider extends BaseWebProvider
{
    protected string $provider = 'gemini';

    public function sendMessage(array $messages, array $options = []): array
    {
        $response = $this->client()->post('https://gemini.google.com/_/BardChatUi/data/assistant.lamda.BardFrontendService/StreamResponse', [
            'messages' => $messages,
            'stream' => false,
        ]);

        if ($response->unauthorized()) {
            $this->markExpired('Session expired');
            throw new \RuntimeException('Gemini session expired. Please reconnect via browser extension.');
        }

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json();
    }

    public function validate(): bool
    {
        $response = $this->client()->get('https://gemini.google.com/_/BardChatUi/data/assistant.lamda.BardFrontendService/GetUserInfo');

        $valid = $response->successful();

        $this->token->update([
            'status' => $valid ? 'active' : 'expired',
            'last_validated_at' => now(),
            'last_error' => $valid ? null : 'Validation failed',
        ]);

        return $valid;
    }
}
