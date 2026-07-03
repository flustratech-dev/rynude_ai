<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist the reasoning ("Proses berpikir") alongside the answer — it was
     * client-side only and vanished on page refresh.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('thinking')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('thinking');
        });
    }
};
