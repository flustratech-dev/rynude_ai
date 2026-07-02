<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderWebToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProviderTokenController extends Controller
{
    /**
     * Store token from browser extension
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(['chatgpt', 'gemini', 'claude'])],
            'token' => ['nullable', 'string'],
            'cookies' => ['nullable', 'array'],
        ]);

        $user = Auth::user();

        // Create or update token
        $providerToken = ProviderWebToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $validated['provider'],
            ],
            [
                'connection_type' => 'web_token',
                'access_token' => $validated['token'] ?? null,
                'cookies' => $validated['cookies'] ?? null,
                'status' => 'active',
                'last_validated_at' => now(),
                'expires_at' => now()->addDays(30), // Assume 30 day expiry
            ]
        );

        return response()->json([
            'success' => true,
            'message' => ucfirst($validated['provider']) . ' connected successfully',
            'provider' => $validated['provider'],
            'connected' => true,
        ]);
    }

    /**
     * Get connection status for all providers
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $connections = ProviderWebToken::where('user_id', $user->id)
            ->get()
            ->keyBy('provider');

        $map = function ($provider) use ($connections) {
            $row = $connections->get($provider);
            return [
                'connected' => (bool) $row,
                'status' => $row?->status ?? 'disconnected',
                'last_validated' => $row?->last_validated_at?->diffForHumans(),
            ];
        };

        return response()->json([
            'chatgpt' => $map('chatgpt'),
            'gemini' => $map('gemini'),
            'claude' => $map('claude'),
        ]);
    }

    /**
     * Disconnect a provider
     */
    public function destroy(string $provider): JsonResponse
    {
        $user = Auth::user();

        $deleted = ProviderWebToken::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => ucfirst($provider) . ' disconnected',
            'provider' => $provider,
            'connected' => false,
        ]);
    }
}
