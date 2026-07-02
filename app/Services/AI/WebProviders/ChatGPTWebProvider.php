<?php

namespace App\Services\AI\WebProviders;

class ChatGPTWebProvider extends BaseWebProvider
{
    protected string $provider = 'chatgpt';

    public function sendMessage(array $messages, array $options = []): array
    {
        $response = $this->client()->post('https://chatgpt.com/backend-api/conversation', [
            'messages' => $messages,
            'model' => $options['model'] ?? 'gpt-4o',
            'stream' => false,
        ]);

        if ($response->unauthorized()) {
            $this->markExpired('Token expired');
            throw new \RuntimeException('ChatGPT token expired. Please reconnect via browser extension.');
        }

        if ($response->failed()) {
            throw new \RuntimeException('ChatGPT API error: ' . $response->body());
        }

        return $response->json();
    }

    public function validate(): bool
    {
        $response = $this->client()->get('https://chatgpt.com/backend-api/accounts/check');

        $valid = $response->successful();

        $this->token->update([
            'status' => $valid ? 'active' : 'expired',
            'last_validated_at' => now(),
            'last_error' => $valid ? null : 'Validation failed',
        ]);

        return $valid;
    }
}
