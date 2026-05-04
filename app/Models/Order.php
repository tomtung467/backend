<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'table_id',
        'employee_id',
        'total_price',
        'status',
        'completed_at',
    ];

    protected $dates = [
        'completed_at',
        'deleted_at',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'order_coupon');
    }
}
