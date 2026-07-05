<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('glm_api_key')->nullable()->after('mistral_api_key');
            $table->text('kimi_api_key')->nullable()->after('glm_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['glm_api_key', 'kimi_api_key']);
        });
    }
};
