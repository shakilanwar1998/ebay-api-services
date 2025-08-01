<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'title',
        'description',
        'primary_category',
        'brand',
        'model',
        'images',
        'category_id',
        'aspects',
    ];

    protected $casts = [
        'images' => 'array',
        'aspects' => 'array',
    ];

    public function productStoreListings()
    {
        return $this->hasMany(ProductStoreListing::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Credential::class, 'product_store_listings', 'product_id', 'store_id')
            ->withPivot('ebay_listing_id')
            ->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
