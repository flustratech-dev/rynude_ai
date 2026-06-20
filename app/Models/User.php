<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'anthropic_api_key',
        'openai_api_key',
        'nine_router_api_key',
        'nine_router_base_url',
        'use_proxy',
        'proxy_base_url',
        'proxy_api_key',
        'role',
        'token_balance',
        'custom_instructions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'anthropic_api_key' => 'encrypted',
            'openai_api_key' => 'encrypted',
            'nine_router_api_key' => 'encrypted',
            'use_proxy' => 'boolean',
            'proxy_api_key' => 'encrypted',
        ];
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
