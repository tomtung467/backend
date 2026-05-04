<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_id',
        'invoice_number',
        'subtotal',
        'tax',
        'discount',
        'total',
        'issued_at',
        'due_date',
        'status',
    ];

    protected $dates = [
        'issued_at',
        'due_date',
        'deleted_at',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
