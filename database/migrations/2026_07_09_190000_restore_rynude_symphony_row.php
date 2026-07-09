<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair for existing databases: the AiModelSeeder used to contain the code
 * 'llama-3.1-8b' TWICE — first as "rynude Symphony" (provider=local), then
 * again in the cloud list as "Llama 3.1 8B" (provider=null). updateOrInsert
 * runs in array order, so the second entry overwrote the first: Symphony
 * disappeared from the model picker (wrong name, provider null → shown as an
 * unavailable cloud model, sunk to the cloud sort position).
 *
 * The duplicate has been removed from the seeder; this migration re-asserts
 * ALL SIX local Model Hub rows (name, provider, active, and their intended
 * position near the top of the picker) on databases that already ran the
 * bad seed. Idempotent — safe on fresh installs too.
 */
return new class extends Migration
{
    /** code => [display name, sort_order matching the seeder's local block] */
    private array $locals = [
        'qwen-2.5-0.5b'   => ['rynude Vignette', 2],
        'qwen-2.5-1.5b'   => ['rynude Lyric 4.5', 3],
        'llama-3.2-3b'    => ['rynude Stanza', 4],
        'mistral-7b-v0.3' => ['rynude Canto', 5],
        'llama-3.1-8b'    => ['rynude Symphony', 6],
        'qwen-2.5-14b'    => ['rynude Magnum', 7],
    ];

    public function up(): void
    {
        foreach ($this->locals as $code => [$name, $order]) {
            DB::table('ai_models')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'provider' => 'local',
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
        }
    }

    public function down(): void
    {
        // Nothing to reverse — this is a data repair.
    }
};
