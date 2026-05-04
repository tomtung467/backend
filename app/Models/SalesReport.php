<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'total_revenue',
        'total_orders',
        'avg_order_value',
        'top_dishes',
        'top_customers',
        'payment_breakdown',
        'data',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'top_dishes' => 'json',
        'top_customers' => 'json',
        'payment_breakdown' => 'json',
        'data' => 'json',
    ];
}
