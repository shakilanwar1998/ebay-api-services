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
            'sku' => $productData['SKU']
        ], [
            'title' => $productData['title'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'brand' => $productData['product_brand'],
            'model' => $productData['product_model_name'],
            'condition' => $productData['conditionInfo'],
            'images' => $productData['pictureURL'],
            'stock' => $productData['stock'] ?? 1,
            'shipping_details' => $productData['ShippingDetails']
        ]);
    }

    public function update($id,$data)
    {
        $product = Product::find($id);
        $product->fill($data);
        $product->save();
    }
}
