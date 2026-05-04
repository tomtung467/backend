<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableMerge extends Model
{
    protected $fillable = [
        'primary_table_id',
        'merged_table_ids',
        'merged_at',
        'merged_by',
        'unmerged_at',
        'notes',
    ];

    protected $casts = [
        'merged_table_ids' => 'json',
    ];

    protected $dates = [
        'merged_at',
        'unmerged_at',
    ];

    public function primaryTable()
    {
        return $this->belongsTo(Table::class, 'primary_table_id');
    }

    public function mergedBy()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
