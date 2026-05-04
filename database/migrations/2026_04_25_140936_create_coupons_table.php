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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percent', 'fixed_amount'])->default('percent');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_order_value', 12, 2)->default(0);
            $table->integer('max_uses_per_customer')->default(1);
            $table->integer('total_uses_limit')->nullable();
            $table->integer('current_uses')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->constrained('employees')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
