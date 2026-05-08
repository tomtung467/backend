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
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->json('top_dishes')->nullable();
            $table->json('top_customers')->nullable();
            $table->json('payment_breakdown')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('report_type');
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};
