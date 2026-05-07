<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('table_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('table_reservations', 'pre_order_items')) {
                $table->json('pre_order_items')->nullable()->after('special_requests');
            }
        });
    }

    public function down(): void
    {
        Schema::table('table_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('table_reservations', 'pre_order_items')) {
                $table->dropColumn('pre_order_items');
            }
        });
    }
};
