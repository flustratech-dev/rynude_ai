<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per reflection pass. `passed = true` means the draft satisfied
 * every checklist item; otherwise `directives_json` carries the
 * RevisionDirectives that the next ImprovePass should apply.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('reflection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('pass_index')->default(0);
            $table->json('checklist_json');
            $table->json('directives_json')->nullable();
            $table->boolean('passed')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['generation_run_id', 'pass_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_logs');
    }
};
