<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_id', 'title', 'project_id', 'archived_at', 'share_token'];

    protected $casts = [
        'archived_at' => 'datetime',
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
