<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RTK (Response Token Kompressor) Tracking
 *
 * Adds two columns to `token_usages` to track output compression statistics:
 *   - rtk_saved_chars:    number of characters removed by OutputCompressor
 *   - rtk_original_chars: total characters before compression
 *
 * Savings percentage = rtk_saved_chars / rtk_original_chars * 100
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_usages', function (Blueprint $table) {
            $table->integer('rtk_saved_chars')->default(0)->after('output_tokens')
                ->comment('Characters removed by RTK OutputCompressor in this session');
            $table->integer('rtk_original_chars')->default(0)->after('rtk_saved_chars')
                ->comment('Original characters before RTK compression');
        });
    }

    public function down(): void
    {
        Schema::table('token_usages', function (Blueprint $table) {
            $table->dropColumn(['rtk_saved_chars', 'rtk_original_chars']);
        });
    }
};
