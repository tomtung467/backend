<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'food_id',
        'name',
        'description',
        'preparation_time',
        'difficulty_level',
        'is_active',
    ];

    public function food()
    {
        return $this->belongsTo(Food::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_items')->withPivot('quantity', 'unit');
    }

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }
}
