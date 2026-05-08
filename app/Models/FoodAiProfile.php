<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodAiProfile extends Model
{
    protected $fillable = [
        'food_id',
        'search_text',
        'embedding',
        'embedding_model',
        'content_hash',
        'embedded_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'embedded_at' => 'datetime',
    ];

    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
