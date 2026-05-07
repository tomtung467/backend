<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'unit',
        'current_quantity',
        'min_quantity',
        'max_quantity',
        'unit_cost',
        'is_active',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:4',
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'quantity',
        'min_stock_level',
        'cost_per_unit',
    ];

    public function getQuantityAttribute()
    {
        return $this->current_quantity;
    }

    public function getMinStockLevelAttribute()
    {
        return $this->min_quantity;
    }

    public function getCostPerUnitAttribute()
    {
        return $this->unit_cost;
    }

    public function foods()
    {
        return $this->belongsToMany(Food::class, 'food_ingredient')->withPivot('amount_used');
    }
}
