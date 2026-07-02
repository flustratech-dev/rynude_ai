<?php

namespace App\Services\AI\WebProviders;

use App\Models\ProviderWebToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

abstract class BaseWebProvider
{
    protected ProviderWebToken $token;
    protected string $provider;

    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get active token for current user
     */
    public static function forCurrentUser(string $provider): ?static
    {
        $token = ProviderWebToken::where('user_id', Auth::id())
            ->where('provider', $provider)
            ->where('status', 'active')
            ->first();

        if (!$token) {
            return null;
        }

        $instance = new static($provider);
        $instance->token = $token;
        return $instance;
    }

    /**
     * Send a message via the web provider
     */
    abstract public function sendMessage(array $messages, array $options = []): array;

    /**
     * Check if token is still valid
     */
    abstract public function validate(): bool;

    /**
     * Get HTTP client with appropriate headers
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token->access_token,
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Mark token as expired
     */
    protected function markExpired(?string $error = null): void
    {
        $this->token->update([
            'status' => 'expired',
            'last_error' => $error,
        ]);
    }
}
