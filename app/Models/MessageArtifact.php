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
        'outline_json',
        'summary',
        'user_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'outline_json' => 'array',
    ];

    /**
     * Extract the heading tree from a markdown body. Cheap and deterministic —
     * called whenever an artifact is created/updated so the chat can answer
     * "perbaiki Bab 2" without re-parsing the whole document each turn.
     *
     * Returns: [['level' => 1, 'text' => 'BAB I PENDAHULUAN'], ['level' => 2, 'text' => '1.1 Latar Belakang'], …]
     */
    public static function extractOutline(?string $markdown): array
    {
        if (empty($markdown)) {
            return [];
        }
        $body = preg_replace('/^(?:\xEF\xBB\xBF)?\s*---\r?\n.*?\r?\n---[ \t]*\r?\n/s', '', $markdown, 1) ?? $markdown;

        $outline = [];
        foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
            // Skip inside fenced code blocks roughly — not perfect but good enough
            // for outline purposes (headings inside code are unusual).
            if (preg_match('/^(#{1,6})\s+(.*\S)/', $line, $m)) {
                $level = strlen($m[1]);
                $text = trim($m[2]);
                if ($text !== '') {
                    $outline[] = ['level' => $level, 'text' => $text];
                }
            }
        }
        return $outline;
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
