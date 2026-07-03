<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Message branching (edit/regenerate ala Claude): siblings share a
     * parent_id; the visible conversation is the chain of rows with
     * is_active_branch = true. `model` records which AI wrote the reply.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('model')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->boolean('is_active_branch')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['model', 'parent_id', 'is_active_branch']);
        });
    }
};
