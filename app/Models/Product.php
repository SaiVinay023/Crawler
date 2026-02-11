<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 
        'price', 
        'source_url',
        'category', 
        'attributes'
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
    ];

    // Added the relationship requirement here
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}