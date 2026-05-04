<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TableReservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'table_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'reservation_time',
        'number_of_guests',
        'special_requests',
        'status',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $dates = [
        'reservation_time',
        'cancelled_at',
        'deleted_at',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
