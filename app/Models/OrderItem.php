<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    //
    protected $fillable = [
        'order_id',
        'food_id',
        'quantity',
        'unit_price',
        'total_price',
        'special_notes',
        'status',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
