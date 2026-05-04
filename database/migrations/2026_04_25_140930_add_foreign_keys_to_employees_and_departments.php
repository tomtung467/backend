<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temporarily disable foreign key checks to handle circular dependencies
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Add department_id foreign key to employees
            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->onDelete('set null');
            });

            // Add manager_id foreign key to departments
            Schema::table('departments', function (Blueprint $table) {
                $table->foreign('manager_id')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('set null');
            });
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign('departments_manager_id_foreign');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign('employees_department_id_foreign');
        });
    }
};
