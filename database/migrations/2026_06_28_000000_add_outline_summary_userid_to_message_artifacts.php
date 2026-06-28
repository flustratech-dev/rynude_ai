<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds three columns to message_artifacts so the chat can answer "perbaiki Bab 2"
 * without re-reading the whole artifact every turn:
 *
 *  - outline_json  : extracted heading tree (BAB I → 1.1 → 1.1.1) for fast lookup
 *  - summary       : AI-generated paragraph summarizing what the artifact contains
 *  - user_id       : denormalized owner so ownership checks no longer need a
 *                    triple-JOIN through messages → conversations on every call
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('message_artifacts', function (Blueprint $table) {
            $table->json('outline_json')->nullable()->after('content');
            $table->text('summary')->nullable()->after('outline_json');
            $table->unsignedBigInteger('user_id')->nullable()->after('summary');
            $table->index('user_id');
        });

        // Backfill user_id from the existing message→conversation chain so
        // existing rows are usable by the new ownership shortcut immediately.
        // The UPDATE...INNER JOIN form is MySQL-only; SQLite uses a correlated
        // subquery. We gate on driver so the same migration is safe in both
        // prod (MySQL) and the test/dev sqlite-:memory: harness.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement(<<<'SQL'
                UPDATE message_artifacts ma
                INNER JOIN messages m ON m.id = ma.message_id
                INNER JOIN conversations c ON c.id = m.conversation_id
                SET ma.user_id = c.user_id
                WHERE ma.user_id IS NULL
            SQL);
        } elseif ($driver === 'sqlite') {
            DB::statement(<<<'SQL'
                UPDATE message_artifacts
                SET user_id = (
                    SELECT c.user_id
                    FROM messages m
                    JOIN conversations c ON c.id = m.conversation_id
                    WHERE m.id = message_artifacts.message_id
                )
                WHERE user_id IS NULL
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('message_artifacts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn(['outline_json', 'summary', 'user_id']);
        });
    }
};
