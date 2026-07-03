<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * messages.citations: numbered web sources behind an assistant reply
     * (rendered as chips). conversations.style: per-chat response style
     * (normal|concise|explanatory|formal), like Claude's Styles.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->json('citations')->nullable();
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('style')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('citations');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('style');
        });
    }
};
