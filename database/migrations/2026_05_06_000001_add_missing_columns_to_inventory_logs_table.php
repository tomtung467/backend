<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_logs', 'ingredient_id')) {
                $table->foreignId('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            }
            if (!Schema::hasColumn('inventory_logs', 'action_type')) {
                $table->string('action_type')->default('adjustment');
            }
            if (!Schema::hasColumn('inventory_logs', 'quantity_change')) {
                $table->decimal('quantity_change', 12, 4)->default(0);
            }
            if (!Schema::hasColumn('inventory_logs', 'reference_type')) {
                $table->string('reference_type')->nullable();
            }
            if (!Schema::hasColumn('inventory_logs', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }
            if (!Schema::hasColumn('inventory_logs', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('inventory_logs', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            foreach (['ingredient_id', 'action_type', 'quantity_change', 'reference_type', 'reference_id', 'notes', 'created_by'] as $column) {
                if (Schema::hasColumn('inventory_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
