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
        Schema::table('messages', function (Blueprint $table) {
            $table->index('conversation_id');
        });

        Schema::table('message_artifacts', function (Blueprint $table) {
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
        });

        Schema::table('message_artifacts', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
        });
    }
};
