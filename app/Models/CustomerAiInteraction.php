<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAiInteraction extends Model
{
    protected $fillable = [
        'user_id',
        'table_id',
        'session_id',
        'event_type',
        'message',
        'reply',
        'candidate_food_ids',
        'selected_food_ids',
        'metadata',
    ];

    protected $casts = [
        'candidate_food_ids' => 'array',
        'selected_food_ids' => 'array',
        'metadata' => 'array',
    ];
}
