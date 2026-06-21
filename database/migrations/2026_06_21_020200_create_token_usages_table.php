<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('model');
            $table->string('provider')->nullable();
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->date('usage_date');
            $table->timestamps();

            $table->index(['user_id', 'usage_date']);
            $table->index(['user_id', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_usages');
    }
};
