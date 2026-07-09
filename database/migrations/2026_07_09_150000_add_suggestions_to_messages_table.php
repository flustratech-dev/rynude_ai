<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Option chips (Claude-style "AI asks first / follow-up" buttons).
 *
 * An assistant message may carry up to 4 short suggested replies — either
 * clarifying answer choices (asked BEFORE generating a big document) or
 * follow-up actions (offered after an answer). They are persisted so the
 * chips survive a page reload; the client only renders the ones attached
 * to the LAST assistant message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->json('suggestions')->nullable()->after('citations');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('suggestions');
        });
    }
};
