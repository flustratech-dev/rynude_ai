<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $models = [
            ['code' => 'Qwen/Qwen3.6-27B', 'name' => 'Qwen3.6 HG', 'provider' => 'huggingface'],
            ['code' => 'meta-llama/Llama-3.1-8B-Instruct', 'name' => 'Llama-3.1-8B-Instruct HG', 'provider' => 'huggingface'],
            ['code' => 'deepseek-ai/DeepSeek-V4-Pro', 'name' => 'DeepSeek-V4-Pro HG', 'provider' => 'huggingface'],
            ['code' => 'moonshotai/Kimi-K2.6', 'name' => 'Kimi-K2.6 HG', 'provider' => 'huggingface'],
            ['code' => 'moonshotai/Kimi-K2.7-Code', 'name' => 'Kimi-K2.7-Code HG', 'provider' => 'huggingface'],
            ['code' => 'google/gemma-4-31B-it', 'name' => 'gemma-4-31B-it HG', 'provider' => 'huggingface'],
            ['code' => 'google/gemma-3-12b-it', 'name' => 'gemma-3-12b-it HG', 'provider' => 'huggingface'],
        ];

        foreach ($models as $model) {
            \Illuminate\Support\Facades\DB::table('ai_models')->updateOrInsert(
                ['code' => $model['code']],
                [
                    'name' => $model['name'], 
                    'is_active' => true,
                    'provider' => $model['provider']
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = [
            'Qwen/Qwen3.6-27B',
            'meta-llama/Llama-3.1-8B-Instruct',
            'deepseek-ai/DeepSeek-V4-Pro',
            'moonshotai/Kimi-K2.6',
            'moonshotai/Kimi-K2.7-Code',
            'google/gemma-4-31B-it',
            'google/gemma-3-12b-it',
        ];

        \Illuminate\Support\Facades\DB::table('ai_models')->whereIn('code', $codes)->delete();
    }
};
