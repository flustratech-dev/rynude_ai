<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'role', 'content', 'rating'];
    protected $touches = ['conversation'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function artifacts()
    {
        return $this->hasMany(MessageArtifact::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
