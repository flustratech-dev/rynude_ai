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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('openrouter_api_key');
            $table->text('nine_router_api_key')->nullable()->after('openai_api_key');
            $table->string('nine_router_base_url')->nullable()->after('nine_router_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('openrouter_api_key')->nullable();
            $table->dropColumn('nine_router_api_key');
            $table->dropColumn('nine_router_base_url');
        });
    }
};
