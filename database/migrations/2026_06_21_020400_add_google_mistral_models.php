<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Native Google (Gemini) models — routed through GoogleProvider with a Google AI key.
        $googleModels = [
            ['code' => 'gemini-2.5-pro', 'name' => 'Gemini 2.5 Pro'],
            ['code' => 'gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash'],
            ['code' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash'],
            ['code' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro'],
            ['code' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash'],
        ];

        foreach ($googleModels as $model) {
            DB::table('ai_models')->updateOrInsert(
                ['code' => $model['code']],
                ['name' => $model['name'], 'is_active' => true, 'provider' => 'google']
            );
        }

        // Native Mistral models — routed through MistralProvider with a Mistral key.
        $mistralModels = [
            ['code' => 'mistral-large-latest', 'name' => 'Mistral Large'],
            ['code' => 'mistral-small-latest', 'name' => 'Mistral Small'],
            ['code' => 'open-mistral-nemo', 'name' => 'Mistral Nemo'],
            ['code' => 'codestral-latest', 'name' => 'Codestral'],
            ['code' => 'magistral-medium-latest', 'name' => 'Magistral Medium'],
        ];

        foreach ($mistralModels as $model) {
            DB::table('ai_models')->updateOrInsert(
                ['code' => $model['code']],
                ['name' => $model['name'], 'is_active' => true, 'provider' => 'mistral']
            );
        }
    }

    public function down(): void
    {
        // Base models are not reversed.
    }
};
