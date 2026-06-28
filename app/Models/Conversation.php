<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'title', 'project_id', 'archived_at', 'share_token',
        'memory', 'memory_synced_count', 'memory_updated_at', 'metadata',
        'draft_prompt',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'memory_updated_at' => 'datetime',
        'memory_synced_count' => 'integer',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
