<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Durable, model-agnostic memory of the conversation. Injected into the
            // system prompt every turn so context survives the sliding window and
            // switching models mid-chat.
            if (!Schema::hasColumn('conversations', 'memory')) {
                $table->text('memory')->nullable()->after('title');
            }
            // How many messages have already been folded into `memory`, so we only
            // re-summarize when enough new messages have accumulated.
            if (!Schema::hasColumn('conversations', 'memory_synced_count')) {
                $table->unsignedInteger('memory_synced_count')->default(0)->after('memory');
            }
            if (!Schema::hasColumn('conversations', 'memory_updated_at')) {
                $table->timestamp('memory_updated_at')->nullable()->after('memory_synced_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            foreach (['memory', 'memory_synced_count', 'memory_updated_at'] as $col) {
                if (Schema::hasColumn('conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
