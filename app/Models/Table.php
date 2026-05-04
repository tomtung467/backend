<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'table_number',
        'capacity',
        'status',
        'section',
        'notes',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reservations()
    {
        return $this->hasMany(TableReservation::class);
    }

    public function merges()
    {
        return $this->hasMany(TableMerge::class, 'primary_table_id');
    }
}
