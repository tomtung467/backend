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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('food_id')->constrained('foods')->onDelete('cascade');
            $table->decimal('yield_quantity', 12, 4);
            $table->string('yield_unit');
            $table->text('preparation_instructions')->nullable();
            $table->timestamps();
            
            $table->index('food_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
