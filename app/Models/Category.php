<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'display_order',
        'is_active',
    ];
    public function foods()
    {
        return $this->hasMany(Food::class);
    }
}
