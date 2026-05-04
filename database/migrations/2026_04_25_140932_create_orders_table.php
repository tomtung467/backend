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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('table_id')->constrained('tables')->onDelete('restrict');
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'ready', 'served', 'paid', 'cancelled'])->default('pending');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('special_requests')->nullable();
            $table->foreignId('created_by_id')->constrained('employees')->onDelete('restrict');
            $table->string('source')->default('dine_in');
            $table->timestamp('estimated_completion_time')->nullable();
            $table->timestamp('actual_completion_time')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('table_id');
            $table->index('status');
            $table->index('created_by_id');
            $table->index('coupon_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
