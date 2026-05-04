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
        // REMOVED: Circular dependency fixed - recipe_id column removed from foods table
        // Recipes now has a one-way foreign key to foods which is the correct relationship
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: Up method is a no-op (circular dependency removed)
        // The foreign key was never created by this migration
    }
};
