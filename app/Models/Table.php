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
        'layout_x',
        'layout_y',
        'shape',
        'merged_into_table_id',
        'notes',
        'current_customer_count',
        'occupied_since',
        'reserved_until',
        'is_active',
    ];

    protected $casts = [
        'occupied_since' => 'datetime',
        'reserved_until' => 'datetime',
        'is_active' => 'boolean',
        'layout_x' => 'integer',
        'layout_y' => 'integer',
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
