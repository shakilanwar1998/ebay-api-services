<?php

namespace App\Services;

use App\Models\Product;

class FeedService
{
    protected string $feedUrl;

    public function __construct()
    {
        $this->feedUrl = config('ebay.feed_url');
    }
    public function getFeedData($content): array
    {
        $xml = simplexml_load_string($content);

        $resultArray = [];

        foreach ($xml->product as $product) {
            $productArray = [];

            foreach ($product->children() as $child) {
                if ($child->count() > 0) {
                    if ($child->getName() === 'productInformation') {
                        foreach ($child->children() as $subChild) {
                            if ($subChild->getName() === 'description') {
                                $productArray[$subChild->getName()] = (string)$subChild->productDescription;
                            } else if ($subChild->getName() === 'pictureURL'){
                                $productArray[$subChild->getName()][] = (string)$subChild;
                            } else if ($subChild->getName() === 'conditionInfo'){
                                $productArray[$subChild->getName()] = (string)$subChild->condition;
                            } else{
                                $productArray[$subChild->getName()] = (string)$subChild;
                            }
                        }
                    } elseif ($child->getName() === 'ShippingDetails') {
                        $productArray[$child->getName()] = [];
                        foreach ($child->ShippingServiceOptions as $shippingOption) {
                            $shippingArray = [];
                            foreach ($shippingOption->children() as $option) {
                                $shippingArray[$option->getName()] = (string)$option;
                            }
                            $productArray[$child->getName()][] = $shippingArray;
                        }
                    } else {
                        $productArray[$child->getName()] = (string)$child;
                    }
                } else {
                    $productArray[$child->getName()] = (string)$child;
                }
            }

            $resultArray[] = $productArray;
        }

        return $resultArray;
    }

    private function isEqualToLocal($fileContent): bool
    {
        $localFilePath = 'feed.xml';
        if (file_exists($localFilePath)) {
            $localFileContent = file_get_contents($localFilePath);
            if ($localFileContent === $fileContent) {
                return true;
            } else {
                file_put_contents($localFilePath, $fileContent);
                return false;
            }
        } else {
            file_put_contents($localFilePath, $fileContent);
            return false;
        }
    }


    public function syncFeedWithDB()
    {
//        $data = file_get_contents($this->feedUrl);
//        $equal = $this->isEqualToLocal($data);
//        if ($equal) {
//            return response(['message' => 'No updates available']);
//        }
        $data = file_get_contents('feed.xml');
        $feedData = $this->getFeedData($data);

        $products = app(ProductService::class)->getAll();

        foreach ($feedData as $productData) {
            $sku = $productData['SKU'];
            $product = $products->where('sku', $sku)->first();

            if ($product) {
                // Product found, update its details
                $product->update([
                    'title' => $productData['title'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'brand' => $productData['product_brand'],
                    'model' => $productData['product_model_name'],
                    // Update other fields as needed
                ]);

                // Update shipping details
                $product->shipping_details()->delete(); // Delete existing shipping details
                foreach ($productData['ShippingDetails'] as $shippingDetail) {
                    $product->shipping_details()->create($shippingDetail);
                }
            } else {
                // Product not found, create a new one
                Product::create([
                    'sku' => $sku,
                    'title' => $productData['title'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'brand' => $productData['product_brand'],
                    'model' => $productData['product_model_name'],
                    // Set other fields as needed
                ]);
            }
        }

        return response(['message' => 'Feed synchronized successfully']);
    }

}
