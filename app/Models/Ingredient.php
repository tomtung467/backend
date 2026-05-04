<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    //
    protected $fillable = [
        'item_name',
        'quantity',
        'unit',
    ];
    public function foods()
    {
        return $this->belongsToMany(Food::class, 'food_ingredient')->withPivot('amount_used');
    }
}
