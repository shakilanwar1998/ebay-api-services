<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAll()
    {
        return Product::all();
    }

    public function create($productData){
        return Product::updateOrCreate([
            'sku' =>  $productData['sku']
        ],[
            'title' => $productData['title'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'brand' => $productData['product_brand'],
            'model' => $productData['product_model_name'],
        ]);
    }
}
