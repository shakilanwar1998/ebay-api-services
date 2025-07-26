<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'refresh_token',
        'access_token',
        'environment',
        'app_token',
        'rf_token_valid_till',
        'access_token_valid_till',
        'is_active',
        'store_name',
    ];

    public static function setActiveStore($id)
    {
        // Unset all
        static::query()->update(['is_active' => false]);
        // Set the selected one
        static::where('id', $id)->update(['is_active' => true]);
    }

    public function productStoreListings()
    {
        return $this->hasMany(ProductStoreListing::class, 'store_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_store_listings', 'store_id', 'product_id')
            ->withPivot('ebay_listing_id')
            ->withTimestamps();
    }
}
