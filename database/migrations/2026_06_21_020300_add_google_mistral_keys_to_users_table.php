<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_api_key')) {
                $table->text('google_api_key')->nullable()->after('huggingface_base_url');
            }
            if (!Schema::hasColumn('users', 'mistral_api_key')) {
                $table->text('mistral_api_key')->nullable()->after('google_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['google_api_key', 'mistral_api_key'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
