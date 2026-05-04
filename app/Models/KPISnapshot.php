<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPISnapshot extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'orders_processed',
        'avg_processing_time',
        'customer_satisfaction_score',
        'sales_amount',
        'tips_received',
    ];

    protected $dates = [
        'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
