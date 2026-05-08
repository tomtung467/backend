<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (!Schema::hasColumn('tables', 'layout_x')) {
                $table->unsignedInteger('layout_x')->nullable()->after('section');
            }
            if (!Schema::hasColumn('tables', 'layout_y')) {
                $table->unsignedInteger('layout_y')->nullable()->after('layout_x');
            }
            if (!Schema::hasColumn('tables', 'shape')) {
                $table->string('shape', 20)->default('rectangle')->after('layout_y');
            }
            if (!Schema::hasColumn('tables', 'merged_into_table_id')) {
                $table->foreignId('merged_into_table_id')->nullable()->after('shape')->constrained('tables')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            if (Schema::hasColumn('tables', 'merged_into_table_id')) {
                $table->dropConstrainedForeignId('merged_into_table_id');
            }
            foreach (['shape', 'layout_y', 'layout_x'] as $column) {
                if (Schema::hasColumn('tables', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
