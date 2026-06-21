<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id', 'title', 'project_id', 'archived_at', 'share_token',
        'memory', 'memory_synced_count', 'memory_updated_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'memory_updated_at' => 'datetime',
        'memory_synced_count' => 'integer',
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
