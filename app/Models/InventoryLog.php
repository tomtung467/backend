<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    protected $fillable = [
        'ingredient_id',
        'action_type',
        'quantity_change',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
