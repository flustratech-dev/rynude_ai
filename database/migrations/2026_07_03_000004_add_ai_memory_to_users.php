<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cross-conversation user memory (like Claude's user memory): a compact
     * profile distilled from the per-conversation memories, injected into the
     * system prompt of every new chat.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('ai_memory')->nullable();
            $table->timestamp('ai_memory_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ai_memory', 'ai_memory_synced_at']);
        });
    }
};
