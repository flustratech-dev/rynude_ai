<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persists the TaskBrief + ExecutionPlan that the planner produced for this
 * run. Always exactly zero or one row per run (Chat / Research modes only).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_run_id')->constrained()->cascadeOnDelete();
            $table->json('brief_json');
            $table->json('execution_json');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
