<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per pipeline stage execution. `pass_index` separates initial run
 * (0) from regeneration attempts (1..N). Inputs and outputs are stored as
 * JSON so any stage's contribution to the final answer can be replayed
 * during debugging without re-running the LLM.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('generation_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_run_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', [
                'task_analyzer', 'planning', 'research',
                'draft', 'improve', 'review', 'final',
                'reflection', 'scoring',
            ]);
            $table->unsignedTinyInteger('pass_index')->default(0);
            $table->enum('status', ['running', 'done', 'failed', 'skipped'])->default('running');
            $table->string('prompt_template_key')->nullable();
            $table->json('input_json')->nullable();
            $table->json('output_json')->nullable();
            $table->text('thinking_text')->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('elapsed_ms')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['generation_run_id', 'pass_index', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_stages');
    }
};
