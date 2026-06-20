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
            $table->text('huggingface_api_key')->nullable()->after('proxy_base_url');
            $table->string('huggingface_base_url')->nullable()->after('huggingface_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('huggingface_api_key');
            $table->dropColumn('huggingface_base_url');
        });
    }
};
