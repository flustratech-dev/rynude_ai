<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('use_proxy')->default(false);
            $table->string('proxy_base_url')->nullable();
            $table->text('proxy_api_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['use_proxy', 'proxy_base_url', 'proxy_api_key']);
        });
    }
};
