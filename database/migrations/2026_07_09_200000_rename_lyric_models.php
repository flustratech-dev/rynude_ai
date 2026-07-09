<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename the two Lyric-family models on EXISTING databases (display name only —
 * the `code` and .gguf filenames are stable keys and stay untouched):
 *
 *   qwen-2.5-1.5b        "rynude Lyric"          → "rynude Lyric 4.5"   (base Qwen3-1.7B)
 *   rynude-lyric-plus-1  "rynude Lyric+ (Tuned)" → "rynude Lyric 4.6"   (QLoRA fine-tune)
 *
 * The seeder + catalog + earlier migrations already carry the new names for
 * fresh installs; this migration brings an already-seeded DB in line.
 * Idempotent — safe to run on fresh installs too.
 */
return new class extends Migration
{
    private array $renames = [
        'qwen-2.5-1.5b'       => 'rynude Lyric 4.5',
        'rynude-lyric-plus-1' => 'rynude Lyric 4.6',
    ];

    public function up(): void
    {
        foreach ($this->renames as $code => $name) {
            DB::table('ai_models')->where('code', $code)->update(['name' => $name]);
        }
    }

    public function down(): void
    {
        DB::table('ai_models')->where('code', 'qwen-2.5-1.5b')->update(['name' => 'rynude Lyric']);
        DB::table('ai_models')->where('code', 'rynude-lyric-plus-1')->update(['name' => 'rynude Lyric+ (Tuned)']);
    }
};
