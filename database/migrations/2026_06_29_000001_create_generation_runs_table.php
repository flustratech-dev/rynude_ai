<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per AI generation turn. Captures which mode ran (fast/chat/research),
 * the threshold it was scored against, regen count, token totals, cost, and
 * status across the pipeline state machine. The single row stays the parent
 * for all stage / plan / reflection / score rows produced during the turn,
 * giving a complete audit trail per turn.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('generation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('model');
            $table->string('pipeline_version')->default('v5.1');
            $table->enum('mode', ['fast', 'chat', 'research']);
            $table->unsignedSmallInteger('quality_threshold');
            $table->enum('status', [
                'pending', 'analyzing', 'planning', 'researching',
                'writing', 'reviewing', 'scoring',
                'complete', 'failed', 'aborted',
            ])->default('pending');
            $table->unsignedTinyInteger('regen_count')->default(0);
            $table->unsignedSmallInteger('final_score')->nullable();
            $table->unsignedInteger('cost_micro_usd')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('elapsed_ms')->default(0);
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_runs');
    }
};
