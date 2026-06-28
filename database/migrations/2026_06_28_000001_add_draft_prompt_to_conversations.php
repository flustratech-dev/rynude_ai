<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `draft_prompt` so an in-progress user message survives page refresh /
 * accidental tab close. The textarea autosaves every couple of seconds; the
 * conversation reload restores whatever was being typed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->text('draft_prompt')->nullable()->after('memory');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('draft_prompt');
        });
    }
};
