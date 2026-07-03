<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * message_artifacts.version: revisions of the same artifact (identifier)
     * within a conversation get v1, v2, ... like Claude's version selector.
     * conversations.is_starred: pinned chats section in the sidebar.
     */
    public function up(): void
    {
        Schema::table('message_artifacts', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_starred')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('message_artifacts', function (Blueprint $table) {
            $table->dropColumn('version');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_starred');
        });
    }
};
