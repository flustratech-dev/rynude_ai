<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderWebToken extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'connection_type',
        'access_token',
        'cookies',
        'expires_at',
        'last_validated_at',
        'status',
        'last_error',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'cookies' => 'encrypted:array',
        'expires_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markActive(): void
    {
        $this->update([
            'status' => 'active',
            'last_error' => null,
            'last_validated_at' => now(),
        ]);
    }

    public function recordError(string $error): void
    {
        $this->update([
            'status' => 'error',
            'last_error' => $error,
        ]);
    }
}
