<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Authoritative fix for the 6 Model Hub GGUF models on EXISTING databases:
 *
 *  1. Rebrand display names to "rynude …".
 *  2. Force provider = 'local' so they route to the local GGUF engine. In
 *     particular this repairs the `llama-3.1-8b` collision, where an earlier
 *     cloud-proxy row (provider = null) shadowed the local GGUF and let an
 *     UNINSTALLED model answer via the cloud proxy ("ghost model" bug).
 *
 * The `code` and any .gguf filenames are NOT touched — only display name and
 * provider. HF repository IDs are unaffected.
 */
return new class extends Migration
{
    private array $rebrand = [
        'qwen-2.5-0.5b'   => 'rynude Vignette',
        'qwen-2.5-1.5b'   => 'rynude Lyric 4.5',
        'llama-3.2-3b'    => 'rynude Stanza',
        'mistral-7b-v0.3' => 'rynude Canto',
        'llama-3.1-8b'    => 'rynude Symphony',
        'qwen-2.5-14b'    => 'rynude Magnum',
    ];

    public function up(): void
    {
        foreach ($this->rebrand as $code => $name) {
            DB::table('ai_models')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'provider' => 'local'],
            );
        }
    }

    public function down(): void
    {
        // Restore the original display names; leave provider as 'local' (the
        // pre-migration 'llama-3.1-8b' = null state was itself the bug).
        $original = [
            'qwen-2.5-0.5b'   => 'Qwen 2.5 (0.5B Local)',
            'qwen-2.5-1.5b'   => 'Qwen 2.5 (1.5B Local)',
            'llama-3.2-3b'    => 'Llama 3.2 (3B Local)',
            'mistral-7b-v0.3' => 'Mistral 7B (v0.3 Local)',
            'llama-3.1-8b'    => 'Llama 3.1 (8B Local)',
            'qwen-2.5-14b'    => 'Qwen 2.5 (14B Local)',
        ];
        foreach ($original as $code => $name) {
            DB::table('ai_models')->where('code', $code)->update(['name' => $name]);
        }
    }
};
