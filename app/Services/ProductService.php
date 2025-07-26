<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAll()
    {
        return Product::all();
    }

    public function create($productData)
    {
        return Product::create([
            'title' => $productData['title'],
            'description' => $productData['description'],
            'primary_category' => $productData['primary_category'],
            'brand' => $productData['brand'],
            'model' => $productData['model'],
            'images' => $productData['images'],
        ]);
    }
}
