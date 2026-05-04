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
        'is_popular',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
