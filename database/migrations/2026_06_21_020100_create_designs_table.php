<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('prototype'); // slides | prototype | wireframe | document | animation | blank
            $table->text('prompt')->nullable();
            $table->longText('content')->nullable(); // generated HTML/markup
            $table->string('status')->default('draft'); // draft | generating | ready | failed
            $table->boolean('is_starred')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
