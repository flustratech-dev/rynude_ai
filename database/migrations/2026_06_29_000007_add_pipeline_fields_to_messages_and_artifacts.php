<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Threads the pipeline metadata back into the user-visible row types:
 *
 *   messages.generation_run_id          — assistant message → its pipeline run
 *   messages.pipeline_mode              — denormalized for fast filtering
 *   message_artifacts.generation_run_id — artifact → run that produced it
 *   message_artifacts.quality_score     — final overall score for the artifact
 *
 * All columns are nullable so legacy rows pre-pipeline remain valid.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'generation_run_id')) {
                $table->foreignId('generation_run_id')
                    ->nullable()
                    ->after('content')
                    ->constrained('generation_runs')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('messages', 'pipeline_mode')) {
                $table->enum('pipeline_mode', ['fast', 'chat', 'research'])
                    ->nullable()
                    ->after('generation_run_id');
            }
        });

        Schema::table('message_artifacts', function (Blueprint $table) {
            if (!Schema::hasColumn('message_artifacts', 'generation_run_id')) {
                $table->foreignId('generation_run_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('generation_runs')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('message_artifacts', 'quality_score')) {
                $table->unsignedSmallInteger('quality_score')
                    ->nullable()
                    ->after('generation_run_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'generation_run_id')) {
                $table->dropForeign(['generation_run_id']);
                $table->dropColumn('generation_run_id');
            }
            if (Schema::hasColumn('messages', 'pipeline_mode')) {
                $table->dropColumn('pipeline_mode');
            }
        });

        Schema::table('message_artifacts', function (Blueprint $table) {
            if (Schema::hasColumn('message_artifacts', 'generation_run_id')) {
                $table->dropForeign(['generation_run_id']);
                $table->dropColumn('generation_run_id');
            }
            if (Schema::hasColumn('message_artifacts', 'quality_score')) {
                $table->dropColumn('quality_score');
            }
        });
    }
};
