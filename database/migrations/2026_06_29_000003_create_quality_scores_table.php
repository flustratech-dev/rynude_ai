<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-rubric quality score, one row per scoring pass. `threshold` is
 * persisted alongside `overall` so historical comparisons remain valid
 * even if mode defaults change later. `passed_threshold` is denormalized
 * for fast queries against historical run quality.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('quality_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('pass_index')->default(0);
            $table->unsignedSmallInteger('accuracy');
            $table->unsignedSmallInteger('completeness');
            $table->unsignedSmallInteger('consistency');
            $table->unsignedSmallInteger('academic_quality');
            $table->unsignedSmallInteger('formatting');
            $table->unsignedSmallInteger('overall');
            $table->unsignedSmallInteger('threshold');
            $table->string('rubric_version');
            $table->string('scorer_model');
            $table->json('notes_json')->nullable();
            $table->boolean('passed_threshold')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['generation_run_id', 'pass_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_scores');
    }
};
