<?php

namespace App\Services;

use App\Models\Product;
use GuzzleHttp\Exception\GuzzleException;

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

    /**
     * @param string $xml
     * @param mixed $value
     * @return string
     */
    public function getShippingOptions(string $xml, mixed $value): string
    {
        $xml .= '<ShippingDetails>';
        $xml .= '<ShippingType>Flat</ShippingType>';
        $count = 1;
        $shippingOption = $value[0];
//        foreach ($value as $shippingOption) {
            $xml .= '<ShippingServiceOptions>';
            $xml .= '<ShippingServicePriority>' . $count . '</ShippingServicePriority>';
            $xml .= '<ShippingService>UPSGround</ShippingService>';
            $xml .= '<ShippingServiceCost>' . $shippingOption['ShippingServiceCost'] . '</ShippingServiceCost>';
//            $xml .= '<ShipToLocation>' . $shippingOption['ShipToLocation'] . '</ShipToLocation>';
            $xml .= '</ShippingServiceOptions>';
//        }
        $xml .= '</ShippingDetails>';
        return $xml;
    }

    public function getShippingPolicies()
    {
        return '<ReturnPolicy>
	      <ReturnsAcceptedOption>ReturnsAccepted</ReturnsAcceptedOption>
	      <RefundOption>MoneyBack</RefundOption>
	      <ReturnsWithinOption>Days_30</ReturnsWithinOption>
	      <ShippingCostPaidByOption>Buyer</ShippingCostPaidByOption>
	    </ReturnPolicy>';
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


    /**
     * @throws GuzzleException
     */
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

        $newProducts = array();

        $listingFeed = null;
        $revisingFeed = null;

        foreach ($feedData as $productData) {
            $sku = $productData['SKU'];
            $product = $products->where('sku', $sku)->first();
            if (!$product) {
//                app(ProductService::class)->create($productData);
                $newProducts[] = $productData;
            } else {
                $changes = $this->findChanges($product, $productData);
                if(!empty($changes)){
                    app(ProductService::class)->update($product->id,$changes);
                    $revisingFeed = $this->generateReviseItemFeed($product->listing_id, $changes);
                }
            }
        }
//        dd($newProducts);
        if (!empty($newProducts)) {
            $listingFeed = $this->generateListItemsFeed($newProducts);

        }
        if($revisingFeed){
            $response = app(ApiService::class)->reviseItem($revisingFeed);
            dd($response);
        }

        if($listingFeed){
            $response = app(ApiService::class)->listItems($listingFeed);
            dd($response);
        }

        return response(['message' => 'Feed synchronized successfully']);
    }

    private function findChanges($product, $productData): array
    {
        $changes = [];
        foreach ($productData as $key => $value) {
            if ($key == 'product_brand' && $product->brand !== $value){
                $changes['brand'] = $key;
            }
            elseif ($key == 'product_model_name' && $product->model !== $value){
                $changes['model'] = $key;
            }
            elseif ($key == 'pictureURL' && $product->images !== $value){
                $changes['images'] = $value;
            }
            elseif ($key == 'conditionInfo' && $product->condition !== $value){
                $changes['condition'] = $value;
            }
            elseif ($key == 'ShippingDetails' && $product->shipping_details != $value){
                $changes['shipping_details'] = $value;
            }
            elseif ($key == 'description' && $product->description !== $value){
                $changes['description'] = $value;
            }
            elseif ($key == 'title' && $product->title !== $value){
                $changes['title'] = $value;
            }
            elseif ($key == 'price' && $product->price != $value){
                $changes['price'] = $value;
            }
            elseif ($key == 'stock' && $product->stock != $value){
                $changes['stock'] = $value;
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
            if($key == 'condition'){
                $xml .= '<' . \App\Enums\Product::FIELD_MAPPING[$key] . '>' . \App\Enums\Product::CONDITIONS[strtolower(str_replace(' ','_',$value))] . '</' . \App\Enums\Product::FIELD_MAPPING[$key] . '>';
            }
            elseif($key == 'category_id'){
                $xml .= '<PrimaryCategory>';
                $xml .= '<CategoryID>'.$value.'</CategoryID>';
                $xml .= '</PrimaryCategory>';
            }
            elseif($key == 'images'){
                $xml .= '<PictureDetails>';
                foreach ($value as $url) {
                    $xml .= '<PictureURL>' . $url . '</PictureURL>';
                }
                $xml .= '</PictureDetails>';
            }
            elseif($key == 'shipping_details'){
                $xml = $this->getShippingOptions($xml, $value);
            }
            else{
                $xml .= '<' . \App\Enums\Product::FIELD_MAPPING[$key] . '>' . $value . '</' . \App\Enums\Product::FIELD_MAPPING[$key] . '>';
            }
        }
        if(@$changes['brand'] || @$changes['model']){
            $xml .= '<ItemSpecifics>';
            if($changes['brand']){
                $xml.= '<NameValueList>';
                $xml.= '<Name>Brand</Name>';
                $xml.= '<Value>'.$changes['brand'].'</Value>';
                $xml.= '</NameValueList>';
            }
            if($changes['model']){
                $xml.= '<NameValueList>';
                $xml.= '<Name>Model</Name>';
                $xml.= '<Value>'.$changes['model'].'</Value>';
                $xml.= '</NameValueList>';
            }
            $xml .= '</ItemSpecifics>';
        }

        $xml .= '</ReviseItemRequest>';

        return $xml;
    }

    public function generateListItemsFeed($productDataArray): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $xml .= '<ErrorLanguage>en_US</ErrorLanguage>';
        $xml .= '<WarningLevel>High</WarningLevel>';
        $productDataArray = [$productDataArray[0]];
        foreach ($productDataArray as $productData) {
            $categoryId = '177';
            $conditionId = \App\Enums\Product::CONDITIONS[strtolower(str_replace(' ', '_', $productData['conditionInfo']))] ?? 1000;
            $shippingOptions = $productData['ShippingDetails'] ?? array();
            $stock = 1;

            $xml .= '<Item>';
            $xml .= '<Title>' . $productData['title'] . '</Title>';
//            $xml .= '<Description>' . $productData['description'] . '</Description>';
            $xml .= '<Description>A Test Description</Description>';
            $xml .= '<PrimaryCategory>';
            $xml .= '<CategoryID>'.$categoryId.'</CategoryID>';
            $xml .= '</PrimaryCategory>';
            $xml .= '<StartPrice>' . $productData['price'] . '</StartPrice>';
            $xml .= '<Quantity>' . $stock . '</Quantity>'; // Quantity
            $xml .= '<ConditionID>'.$conditionId.'</ConditionID>';
            $xml .= '<Country>US</Country>';
            $xml .= '<Currency>USD</Currency>';
            $xml .= '<DispatchTimeMax>3</DispatchTimeMax>';
            $xml .= '<ListingDuration>GTC</ListingDuration>';
            $xml .= '<ListingType>FixedPriceItem</ListingType>';
            $xml .= '<PostalCode>95125</PostalCode>';

            if(!empty($shippingOptions)){
                $xml = $this->getShippingOptions($xml, $shippingOptions);
            }

            $xml .= $this->getShippingPolicies();

            $xml .= '<PictureDetails>';
            foreach ($productData['pictureURL'] as $url) {
                $xml .= '<PictureURL>' . $url . '</PictureURL>';
            }
            $xml .= '</PictureDetails>';


            $xml .= '<ItemSpecifics>';

            $xml.= '<NameValueList>';
            $xml.= '<Name>Brand</Name>';
            $xml.= '<Value>'.$productData['product_brand'].'</Value>';
            $xml.= '</NameValueList>';

            $xml.= '<NameValueList>';
            $xml.= '<Name>Model</Name>';
            $xml.= '<Value>'.$productData['product_model_name'].'</Value>';
            $xml.= '</NameValueList>';

            $xml.= '<NameValueList>';
            $xml.= '<Name>Processor</Name>';
            $xml.= '<Value>Not specified</Value>';
            $xml.= '</NameValueList>';

            $xml.= '<NameValueList>';
            $xml.= '<Name>Processor</Name>';
            $xml.= '<Value>Not specified</Value>';
            $xml.= '</NameValueList>';

            $xml.= '<NameValueList>';
            $xml.= '<Name>Screen Size</Name>';
            $xml.= '<Value>Not specified</Value>';
            $xml.= '</NameValueList>';

            $xml .= '</ItemSpecifics>';

            $xml .= '</Item>';
        }

        $xml .= '</AddFixedPriceItemRequest>';

        return $xml;
    }




}
