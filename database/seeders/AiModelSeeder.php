<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            ['code' => 'kr/claude-sonnet-4.5', 'name' => 'Rynude Sonnet'],
            ['code' => 'kr/claude-haiku-4.5', 'name' => 'Rynude Haiku'],
            
            // Local AI Engine (Model Hub GGUF). Display names are rebranded to
            // "rynude"; the `code` stays stable (it keys LlamaServerService::CATALOG
            // and the .gguf filenames — those are NOT the HF repo IDs).
            ['code' => 'qwen-2.5-0.5b', 'name' => 'rynude Vignette', 'provider' => 'local'],
            ['code' => 'qwen-2.5-1.5b', 'name' => 'rynude Lyric 4.5', 'provider' => 'local'],
            ['code' => 'rynude-lyric-plus-1', 'name' => 'rynude Lyric 4.6', 'provider' => 'local'],
            ['code' => 'rynude-lyric-plus-2', 'name' => 'rynude Lyric 4.7', 'provider' => 'local'],
            ['code' => 'rynude-lyric-plus-3', 'name' => 'rynude Lyric 4.8', 'provider' => 'local'],
            ['code' => 'llama-3.2-3b', 'name' => 'rynude Stanza 4.5', 'provider' => 'local'],
            ['code' => 'rynude-stanza-plus-1', 'name' => 'rynude Stanza 4.6', 'provider' => 'local'],
            ['code' => 'mistral-7b-v0.3', 'name' => 'rynude Canto', 'provider' => 'local'],
            ['code' => 'llama-3.1-8b', 'name' => 'rynude Symphony', 'provider' => 'local'],
            ['code' => 'qwen-2.5-14b', 'name' => 'rynude Magnum', 'provider' => 'local'],

            ['code' => 'gpt-chat-latest', 'name' => 'GPT Chat Latest'],
            ['code' => 'gpt-5.5', 'name' => 'GPT 5.5'],
            ['code' => 'gpt-5.4-pro', 'name' => 'GPT 5.4 Pro'],
            ['code' => 'gpt-5.4-mini', 'name' => 'GPT 5.4 Mini'],
            ['code' => 'gpt-5.4-nano', 'name' => 'GPT 5.4 Nano'],
            ['code' => 'gpt-5.4', 'name' => 'GPT 5.4'],
            ['code' => 'gpt-5.3-codex', 'name' => 'GPT 5.3 Codex'],
            ['code' => 'gpt-5.3-chat', 'name' => 'GPT 5.3 Chat'],
            ['code' => 'gpt-4.1-mini', 'name' => 'GPT 4.1 Mini'],
            ['code' => 'gpt-4.1', 'name' => 'GPT 4.1'],
            ['code' => 'gpt-4o-mini', 'name' => 'GPT 4o Mini'],
            ['code' => 'gpt-4o', 'name' => 'GPT 4o'],
            ['code' => 'gpt-oss-120b', 'name' => 'GPT OSS 120B'],
            ['code' => 'claude-opus-4-8', 'name' => 'Rynude Opus 4.8'],
            ['code' => 'claude-opus-4-7', 'name' => 'Claude Opus 4.7'],
            ['code' => 'claude-opus-4-6', 'name' => 'Claude Opus 4.6'],
            ['code' => 'claude-sonnet-4-6', 'name' => 'Claude Sonnet 4.6'],
            ['code' => 'claude-opus-4-5', 'name' => 'Claude Opus 4.5'],
            ['code' => 'claude-sonnet-4-5', 'name' => 'Claude Sonnet 4.5'],
            ['code' => 'claude-sonnet-5', 'name' => 'Sonnet 5'],
            ['code' => 'fable-5', 'name' => 'Fable 5'],
            ['code' => 'fugu-ultra', 'name' => 'Fugu Ultra'],
            ['code' => 'claude-haiku-4-5', 'name' => 'Claude Haiku 4.6'],
            ['code' => 'gemini-3.5-flash', 'name' => 'Gemini 3.5 Flash'],
            ['code' => 'gemini-3.1-pro', 'name' => 'Gemini 3.1 Pro'],
            ['code' => 'gemini-3.1-flash-lite', 'name' => 'Gemini 3.1 Flash Lite'],
            ['code' => 'gemini-3.1-flash-image', 'name' => 'Nano Banana 2 (Gemini 3.1 Flash)'],
            ['code' => 'gemini-3-pro-image', 'name' => 'Nano Banana Pro (Gemini 3 Pro)'],
            ['code' => 'gemini-3-flash', 'name' => 'Gemini 3 Flash'],
            ['code' => 'gemini-2.5-pro', 'name' => 'Gemini 2.5 Pro'],
            ['code' => 'gemini-2.5-flash-lite', 'name' => 'Gemini 2.5 Flash Lite'],
            ['code' => 'gemini-2.5-flash-image', 'name' => 'Nano Banana (Gemini 2.5 Flash)'],
            ['code' => 'gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash'],
            ['code' => 'gemma-4-31b-it', 'name' => 'Gemma 4 31B IT'],
            ['code' => 'gemma-4-26b-a4b-it', 'name' => 'Gemma 4 26B A4B IT'],
            ['code' => 'deepseek-v4-pro', 'name' => 'DeepSeek V4 Pro'],
            ['code' => 'deepseek-v4-flash', 'name' => 'DeepSeek V4 Flash'],
            ['code' => 'deepseek-v3.2', 'name' => 'DeepSeek V3.2'],
            ['code' => 'deepseek-v3.1', 'name' => 'DeepSeek V3.1'],
            ['code' => 'deepseek-r1', 'name' => 'DeepSeek R1'],
            ['code' => 'deepseek-v3-0324', 'name' => 'DeepSeek V3 0324'],
            ['code' => 'grok-build-0.1', 'name' => 'Grok Build 0.1'],
            ['code' => 'grok-4.3', 'name' => 'Grok 4.3'],
            ['code' => 'grok-4.20', 'name' => 'Grok 4.20'],
            ['code' => 'kimi-k2.7-code-highspeed', 'name' => 'Kimi K2.7 Code Highspeed'],
            ['code' => 'kimi-k2.7-code', 'name' => 'Kimi K2.7 Code'],
            ['code' => 'kimi-k2.6', 'name' => 'Kimi K2.6'],
            ['code' => 'kimi-k2.5', 'name' => 'Kimi K2.5'],
            ['code' => 'qwen3.7-max', 'name' => 'Qwen3.7 Max'],
            ['code' => 'qwen3.7-plus', 'name' => 'Qwen3.7 Plus'],
            ['code' => 'qwen3.6-max', 'name' => 'Qwen3.6 Max'],
            ['code' => 'qwen3.6-plus', 'name' => 'Qwen3.6 Plus'],
            ['code' => 'qwen3.5-flash', 'name' => 'Qwen3.5 Flash'],
            ['code' => 'qwen-plus', 'name' => 'Qwen Plus', 'provider' => 'qwen'],
            ['code' => 'qwen-flash', 'name' => 'Qwen Flash', 'provider' => 'qwen'],
            ['code' => 'qwen-turbo', 'name' => 'Qwen Turbo', 'provider' => 'qwen'],
            ['code' => 'qwen-max', 'name' => 'Qwen Max', 'provider' => 'qwen'],
            ['code' => 'glm-5.2', 'name' => 'GLM 5.2'],
            ['code' => 'glm-5.1', 'name' => 'GLM 5.1'],
            ['code' => 'glm-5-turbo', 'name' => 'GLM 5 Turbo'],
            ['code' => 'glm-5', 'name' => 'GLM 5'],
            ['code' => 'glm-4.7-flash', 'name' => 'GLM 4.7 Flash'],
            ['code' => 'glm-4.5-flash', 'name' => 'GLM 4.5 Flash (Free)', 'provider' => 'glm'],
            ['code' => 'glm-4.7-flash', 'name' => 'GLM 4.7 Flash (Free)', 'provider' => 'glm'],
            ['code' => 'glm-4.6', 'name' => 'GLM 4.6', 'provider' => 'glm'],
            ['code' => 'llama-4-maverick', 'name' => 'Llama 4 Maverick'],
            ['code' => 'llama-4-scout', 'name' => 'Llama 4 Scout'],
            ['code' => 'llama-3.3-70b', 'name' => 'Llama 3.3 70B'],
            // NOTE: no 'llama-3.1-8b' here! That code = rynude Symphony (local,
            // seeded above). A duplicate entry in this cloud list used to
            // OVERWRITE it (name 'Llama 3.1 8B', provider null) because
            // updateOrInsert runs in array order — the classic reason Symphony
            // vanished from the model picker. Cloud 8B lives on as the HF entry
            // 'meta-llama/Llama-3.1-8B-Instruct' below.
            ['code' => 'minimax-m3', 'name' => 'MiniMax M3'],
            ['code' => 'minimax-m2.7', 'name' => 'MiniMax M2.7'],
            ['code' => 'minimax-m2.5', 'name' => 'MiniMax M2.5'],
            ['code' => 'mimo-v2.5-pro', 'name' => 'Mimo V2.5 Pro'],
            ['code' => 'mimo-v2.5', 'name' => 'Mimo V2.5'],
            ['code' => 'mimo-v2-flash', 'name' => 'Mimo V2 Flash'],
            ['code' => 'nex-n2-pro', 'name' => 'Nex N2 Pro (Free)'],
            ['code' => 'text-embedding-3-large', 'name' => 'Text Embedding 3 Large'],
            ['code' => 'text-embedding-3-small', 'name' => 'Text Embedding 3 Small'],
            ['code' => 'gemini-embedding-2', 'name' => 'Gemini Embedding 2'],
            ['code' => 'gemini-embedding', 'name' => 'Gemini Embedding'],
            ['code' => 'qwen3-embedding-8b', 'name' => 'Qwen3 Embedding 8B'],
            ['code' => 'qwen3-embedding-4b', 'name' => 'Qwen3 Embedding 4B'],
            ['code' => 'bge-m3', 'name' => 'BGE M3'],
            ['code' => 'pplx-embed-v1-4b', 'name' => 'Perplexity Embed V1 4B'],
            ['code' => 'pplx-embed-v1-0.6b', 'name' => 'Perplexity Embed V1 0.6B'],
            ['code' => 'whisper-large-v3', 'name' => 'Whisper Large V3'],
            ['code' => 'whisper-large-v3-turbo', 'name' => 'Whisper Large V3 Turbo'],
            ['code' => 'imagen-4-ultra', 'name' => 'Imagen 4 Ultra'],
            ['code' => 'imagen-4-fast', 'name' => 'Imagen 4 Fast'],
            ['code' => 'imagen-4', 'name' => 'Imagen 4'],
            ['code' => 'veo-3.1-fast', 'name' => 'Veo 3.1 Fast'],
            ['code' => 'veo-3.1-lite', 'name' => 'Veo 3.1 Lite'],
            ['code' => 'veo-3.1', 'name' => 'Veo 3.1'],
            ['code' => 'gemini-3.1-flash-tts', 'name' => 'Gemini 3.1 Flash TTS'],
            ['code' => 'gemini-2.5-pro-tts', 'name' => 'Gemini 2.5 Pro TTS'],
            ['code' => 'gemini-2.5-flash-tts', 'name' => 'Gemini 2.5 Flash TTS'],
            
            // Hugging Face Free Router Models (Requested by User)
            ['code' => 'Qwen/Qwen3.6-27B', 'name' => 'HG Qwen3.6', 'provider' => 'huggingface'],
            ['code' => 'meta-llama/Llama-3.1-8B-Instruct', 'name' => 'HG Llama-3.1-8B-Instruct', 'provider' => 'huggingface'],
            ['code' => 'deepseek-ai/DeepSeek-V4-Pro', 'name' => 'HG DeepSeek-V4-Pro', 'provider' => 'huggingface'],
            ['code' => 'moonshotai/Kimi-K2.6', 'name' => 'HG Kimi-K2.6', 'provider' => 'huggingface'],
            ['code' => 'moonshotai/Kimi-K2.7-Code', 'name' => 'HG Kimi-K2.7-Code', 'provider' => 'huggingface'],
            ['code' => 'google/gemma-4-31B-it', 'name' => 'HG gemma-4-31B-it', 'provider' => 'huggingface'],
            ['code' => 'google/gemma-3-12b-it', 'name' => 'HG gemma-3-12b-it', 'provider' => 'huggingface'],
            
            // Kimi (Moonshot) — OpenAI-compatible via https://api.moonshot.ai/v1, uses kimi_api_key
            ['code' => 'kimi-latest', 'name' => 'Kimi Latest', 'provider' => 'kimi'],
            ['code' => 'moonshot-v1-8k', 'name' => 'Moonshot v1 8K', 'provider' => 'kimi'],
        ];

        // The array index doubles as the display order (sort_order) so the model
        // picker follows this list exactly instead of DB insertion order.
        foreach ($models as $index => $model) {
            DB::table('ai_models')->updateOrInsert(
                ['code' => $model['code']],
                [
                    'name' => $model['name'],
                    'is_active' => true,
                    'provider' => $model['provider'] ?? null,
                    'sort_order' => $index,
                ]
            );
        }

        // Anything not in this curated list (older seeds, custom models) sinks
        // below it so the picker stays tidy and follows the order above.
        DB::table('ai_models')
            ->whereNotIn('code', array_column($models, 'code'))
            ->update(['sort_order' => 9999]);
    }
}
