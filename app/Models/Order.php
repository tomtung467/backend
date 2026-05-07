<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'table_id',
        'status',
        'total_price',
        'subtotal',
        'tax_amount',
        'service_charge',
        'discount_amount',
        'coupon_id',
        'customer_notes',
        'special_requests',
        'created_by_id',
        'source',
        'estimated_completion_time',
        'actual_completion_time',
        'paid_at',
        'payment_requested_at',
    ];

    protected $dates = [
        'estimated_completion_time',
        'actual_completion_time',
        'paid_at',
        'payment_requested_at',
        'deleted_at',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'order_coupon');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
