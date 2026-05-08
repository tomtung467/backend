<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->json('ingredients')->nullable()->after('allergens');
            $table->json('nutrition')->nullable()->after('ingredients');
            $table->json('diet_tags')->nullable()->after('nutrition');
            $table->json('taste_profile')->nullable()->after('diet_tags');
            $table->json('best_for')->nullable()->after('taste_profile');
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn([
                'ingredients',
                'nutrition',
                'diet_tags',
                'taste_profile',
                'best_for',
            ]);
        });
    }
};
