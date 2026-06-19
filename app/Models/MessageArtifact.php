<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageArtifact extends Model
{
    protected $fillable = ['message_id', 'type', 'language', 'title', 'content'];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
