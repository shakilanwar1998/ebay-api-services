<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStoreListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'ebay_listing_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Credential::class, 'store_id');
    }
} 