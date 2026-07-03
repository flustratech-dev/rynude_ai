<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'role', 'content', 'rating', 'model', 'parent_id', 'is_active_branch', 'citations'];
    protected $touches = ['conversation'];

    protected $casts = [
        'is_active_branch' => 'boolean',
        'citations' => 'array',
    ];

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

    /**
     * Query the visible conversation thread: branching keeps every edited or
     * regenerated variant in the table, but only one chain carries the flag.
     */
    public static function activeThread(int $conversationId)
    {
        return static::where('conversation_id', $conversationId)
            ->where('is_active_branch', true)
            ->orderBy('id');
    }

    /**
     * Hide this message and everything after it on the active chain. Called
     * before an edit/regenerate/branch-switch replaces the tail.
     */
    public static function deactivateTail(int $conversationId, int $fromMessageId): void
    {
        static::where('conversation_id', $conversationId)
            ->where('is_active_branch', true)
            ->where('id', '>=', $fromMessageId)
            ->update(['is_active_branch' => false]);
    }

    /**
     * Resolve (and persist) this message's parent link. Legacy rows predate
     * branching and have parent_id = null; siblings can only be grouped once
     * the link exists, so it is backfilled lazily at the first edit/regenerate.
     * 0 marks "first message of the conversation".
     *
     * Must run BEFORE deactivateTail() — the parent is found via the active chain.
     */
    public function ensureParentLink(): int
    {
        if ($this->parent_id !== null) {
            return (int) $this->parent_id;
        }

        $prev = static::where('conversation_id', $this->conversation_id)
            ->where('is_active_branch', true)
            ->where('id', '<', $this->id)
            ->orderByDesc('id')
            ->first();

        $this->parent_id = $prev ? $prev->id : 0;
        $this->save();

        return (int) $this->parent_id;
    }

    /**
     * Re-activate this sibling and the most recent chain of descendants under
     * it, after the caller has deactivated the currently visible tail.
     */
    public function activateBranch(): void
    {
        $current = $this;
        while ($current) {
            $current->is_active_branch = true;
            $current->save();

            $current = static::where('conversation_id', $this->conversation_id)
                ->where('parent_id', $current->id)
                ->orderByDesc('id')
                ->first();
        }
    }
}
