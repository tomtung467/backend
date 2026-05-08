<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';
    protected $fillable = [
        'name',
        'price',
        'category_id',
        'is_available',
        'description',
        'image_url',
        'recipe_id',
        'preparation_time',
        'spicy_level',
        'calories',
        'allergens',
        'ingredients',
        'nutrition',
        'diet_tags',
        'taste_profile',
        'best_for',
        'is_popular',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_popular' => 'boolean',
        'allergens' => 'array',
        'ingredients' => 'array',
        'nutrition' => 'array',
        'diet_tags' => 'array',
        'taste_profile' => 'array',
        'best_for' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function aiProfile()
    {
        return $this->hasOne(FoodAiProfile::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
