<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_ai_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->unique()->constrained('foods')->onDelete('cascade');
            $table->longText('search_text');
            $table->json('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->string('content_hash', 64);
            $table->timestamp('embedded_at')->nullable();
            $table->timestamps();

            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_ai_profiles');
    }
};
