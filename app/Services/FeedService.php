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
                            } else if ($subChild->getName() === 'pictureURL') {
                                $productArray[$subChild->getName()][] = (string)$subChild;
                            } else if ($subChild->getName() === 'conditionInfo') {
                                $productArray[$subChild->getName()] = (string)$subChild->condition;
                            } else {
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
        $data = file_get_contents($this->feedUrl);
        $equal = $this->isEqualToLocal($data);
        if ($equal) {
            return response(['message' => 'No updates available']);
        }
//        $data = file_get_contents('feed.xml');
        $feedData = $this->getFeedData($data);

        $products = app(ProductService::class)->getAll();

        $newProducts = array();
        foreach ($feedData as $productData) {
            $sku = $productData['SKU'];
            $product = $products->where('sku', $sku)->first();
            if (!$product) {
                app(ProductService::class)->create($productData);
                $newProducts[] = $productData;
            } else {
                $changes = $this->findChanges($product, $productData);
                $revisingFeed = $this->generateReviseItemFeed($product->listing_id, $changes);
            }
        }

        if (!empty($newProducts)) {
            $listingFeed = $this->generateListItemsFeed($newProducts);
            dd($listingFeed);
        }

        return response(['message' => 'Feed synchronized successfully']);
    }

    private function findChanges($product, $productData): array
    {
        $changes = [];
        foreach ($productData as $key => $value) {
            if ($product->$key !== $value) {
                $changes[] = $key;
            }
        }
        return $changes;
    }

    public function generateReviseItemFeed($listingId, $changes): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<ReviseItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $xml .= '<ItemID>' . $listingId . '</ItemID>';
        foreach ($changes as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $xml .= '</ReviseItemRequest>';

        return $xml;
    }

    public function generateListItemsFeed($productData): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        foreach ($productData as $productDatum) {
            $xml .= '<Title>' . $productDatum['title'] . '</Title>';
            $xml .= '<Description>' . $productDatum['description'] . '</Description>';
            $xml .= '<StartPrice>' . $productDatum['price'] . '</StartPrice>';
        }

        $xml .= '</AddFixedPriceItemRequest>';

        return $xml;
    }


}
