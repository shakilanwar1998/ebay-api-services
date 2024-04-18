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

        return Product::updateOrCreate([
            'sku' => $productData['sku']
        ], [
            'title' => $productData['title'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'brand' => $productData['brand'],
            'model' => $productData['model'],
            'condition' => $productData['condition'],
            'images' => $productData['images'],
            'stock' => $productData['stock'] ?? 1,
            'shipping_details' => $productData['shipping_details'],
            'category_id' => $productData['category_id'],
            'postal_code' => $productData['postal_code'],
            'specifications' => $productData['specifications']
        ]);
    }

    public function update($id,$data)
    {
        $product = Product::find($id);
        $product->fill($data);
        $product->save();
    }
}
