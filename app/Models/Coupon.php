<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'max_usage',
        'used_count',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $dates = [
        'valid_from',
        'valid_to',
        'deleted_at',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_coupon');
    }
}
