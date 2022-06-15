<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $casts = [
        'enable' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories')->withTimestamps();
    }
}
