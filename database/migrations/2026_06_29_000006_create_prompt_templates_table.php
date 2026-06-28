<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versioned prompt template registry. Defaults live as .md files under
 * app/Services/AI/Prompts/Templates/ and get seeded into this table on
 * deploy. `provider_variant` is NULL for the universal version of a
 * template; rows with provider_variant='anthropic' / 'openai' / 'google' /
 * 'mistral' override it for that specific adapter.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('version');
            $table->longText('body');
            $table->json('variables_json');
            $table->string('provider_variant')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'version', 'provider_variant']);
            $table->index(['key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
