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
        Schema::create('agent_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id')->index();
            $table->uuid('session_id')->index();
            $table->uuid('agent_id');
            $table->string('event_type');
            $table->string('stage')->nullable();
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->integer('sequence_number')->index();
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_events');
    }
};
