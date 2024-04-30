<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'listing_id',
        'title',
        'description',
        'category_id',
        'local_category_name',
        'price',
        'stock',
        'brand',
        'model',
        'images',
        'condition',
        'shipping_details',
        'postal_code',
        'specifications',
        'exceptions'
    ];

    protected $casts = [
        'images' => 'array',
        'shipping_details' => 'array',
        'specifications' => 'array',
        'exceptions' => 'array'
    ];
}
