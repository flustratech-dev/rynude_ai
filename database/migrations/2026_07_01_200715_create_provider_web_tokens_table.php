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
        Schema::create('provider_web_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // chatgpt, gemini, claude
            $table->enum('connection_type', ['web_token', 'api_key'])->default('web_token');
            $table->text('access_token')->nullable(); // Encrypted
            $table->json('cookies')->nullable(); // For Gemini (multiple cookies)
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->enum('status', ['active', 'expired', 'error'])->default('active');
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_web_tokens');
    }
};
