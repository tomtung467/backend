<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * DEPRECATED: See 2026_04_25_140937_add_coupon_foreign_key_to_orders.php
     * This migration is no longer used (replaced with correctly timestamped version).
     */
    public function up(): void
    {
        // Migration moved to 2026_04_25_140937_add_coupon_foreign_key_to_orders.php
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op - foreign key managed by 2026_04_25_140937_add_coupon_foreign_key_to_orders.php
    }
};
