<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageArtifact extends Model
{
    protected $fillable = [
        'message_id',
        'identifier',
        'type',
        'language',
        'title',
        'content',
        'is_public',
        'public_token',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
