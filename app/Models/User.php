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
        'huggingface_api_key',
        'huggingface_base_url',
        'google_api_key',
        'mistral_api_key',
        'role',
        'token_balance',
        'custom_instructions',
        'preferences',
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
            'huggingface_api_key' => 'encrypted',
            'google_api_key' => 'encrypted',
            'mistral_api_key' => 'encrypted',
            'preferences' => 'array',
        ];
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function coworkTasks()
    {
        return $this->hasMany(CoworkTask::class);
    }

    public function designs()
    {
        return $this->hasMany(Design::class);
    }

    public function tokenUsages()
    {
        return $this->hasMany(TokenUsage::class);
    }
}
