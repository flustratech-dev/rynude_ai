<?php

namespace App\Services\AI\WebProviders;

class ClaudeWebProvider extends BaseWebProvider
{
    protected string $provider = 'claude';

    public function sendMessage(array $messages, array $options = []): array
    {
        $response = $this->client()->post('https://claude.ai/api/organizations/chat/conversations', [
            'messages' => $messages,
            'model' => $options['model'] ?? 'claude-3-5-sonnet',
            'stream' => false,
        ]);

        if ($response->unauthorized()) {
            $this->markExpired('Session expired');
            throw new \RuntimeException('Claude session expired. Please reconnect via browser extension.');
        }

        if ($response->failed()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }

        return $response->json();
    }

    public function validate(): bool
    {
        $response = $this->client()->get('https://claude.ai/api/me');

        $valid = $response->successful();

        $this->token->update([
            'status' => $valid ? 'active' : 'expired',
            'last_validated_at' => now(),
            'last_error' => $valid ? null : 'Validation failed',
        ]);

        return $valid;
    }
}
