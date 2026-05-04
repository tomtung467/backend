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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->integer('table_number')->unique();
            $table->integer('capacity');
            $table->string('section')->nullable();
            $table->enum('status', ['empty', 'occupied', 'reserved'])->default('empty');
            $table->integer('current_customer_count')->default(0);
            $table->timestamp('occupied_since')->nullable();
            $table->timestamp('reserved_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('table_number');
            $table->index('status');
            $table->index('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
