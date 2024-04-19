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
            $shippingOptions = (array)$product->ShippingDetails;
            $postalCode = $shippingOptions['PostalCode'];
            unset($shippingOptions['PostalCode']);

            $specifications = (string)$product->productInformation->specifications;
            $json_string = str_replace("'", '"', $specifications);

            $specifications = json_decode($json_string,true);

            $resultArray[] = [
                'title' => (string)$product->productInformation->title,
                'description' => (string)$product->productInformation->description->productDescription,
                'images' => (array)$product->productInformation->pictureURL,
                'sku' => (string)$product->SKU,
                'price' => (float)$product->price,
                'model' => (string)$product->product_model_name,
                'brand' => (string)$product->product_brand,
                'stock'  => (int)$product->productInformation->quantity,
                'condition' => (int)$product->productInformation->conditionInfo->ConditionID,
                'shipping_details' => json_decode(json_encode($shippingOptions['ShippingServiceOptions']),true),
                'postal_code' => $postalCode,
                'category_id' => '177',
                'specifications' => $specifications
            ];
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
            $xml .= '<ShippingService>NL_StandardDelivery</ShippingService>';
            $xml .= '<ShippingServiceCost>' . $shippingOption['ShippingServiceCost'] . '</ShippingServiceCost>';
            $xml .= '<ShippingServiceAdditionalCost currencyID="EUR">0.00</ShippingServiceAdditionalCost>';
            $xml .= '</ShippingServiceOptions>';
//        }
        $xml .= '</ShippingDetails>';
        return $xml;
    }

    private function getShippingPolicies(): string
    {
        return '<ReturnPolicy>
	      <ReturnsAcceptedOption>ReturnsAccepted</ReturnsAcceptedOption>
	      <ReturnsWithinOption>Days_30</ReturnsWithinOption>
	      <ShippingCostPaidByOption>Buyer</ShippingCostPaidByOption>
	    </ReturnPolicy>';

        //	      <RefundOption>MoneyBack</RefundOption>
    }

    /**
     * @param string $xml
     * @param $productData
     * @param mixed $specifications
     * @return string
     */
    public function getSpecifications(string $xml, $productData, mixed $specifications): string
    {
        $xml .= '<ItemSpecifics>';
        $xml .= '<NameValueList>';
        $xml .= '<Name>Merk</Name>';
        $xml .= '<Value>' . $productData['brand'] . '</Value>';
        $xml .= '</NameValueList>';

        $xml .= '<NameValueList>';
        $xml .= '<Name>Model</Name>';
        $xml .= '<Value>' . $productData['model'] . '</Value>';
        $xml .= '</NameValueList>';

        foreach ($specifications ?? array() as $key => $specification) {
            $key = ($key == "Screen size" ? "Schermgrootte" : $key);
            $xml .= '<NameValueList>';
            $xml .= '<Name>' . $key . '</Name>';
            $xml .= '<Value>' . $specification . '</Value>';
            $xml .= '</NameValueList>';
        }

        $xml .= '</ItemSpecifics>';
        return $xml;
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
        $data = file_get_contents($this->feedUrl);
//        $equal = $this->isEqualToLocal($data);
//        if ($equal) {
//            return response(['message' => 'No updates available']);
//        }
//        $data = file_get_contents('feed.xml');
        $feedData = $this->getFeedData($data);

        $products = app(ProductService::class)->getAll();

        $newProducts = array();
        $updatedProducts = array();

        foreach ($feedData as $productData)
        {
            $sku = $productData['sku'];
            $product = $products->where('sku', $sku)->first();
            if (!$product || !$product->listing_id) {
                $product = app(ProductService::class)->create($productData);
                $listingFeed = $this->generateListItemsFeed($productData);
                $response = app(ApiService::class)->listItems($listingFeed);
                $listingId = $this->extractListingId($response);
                app(ProductService::class)->update($product->id,['listing_id' => $listingId]);
                $newProducts[] = 'https://sandbox.ebay.com/itm/'.$listingId;
            } else {
                $changes = $this->findChanges($product, $productData);
                if(!empty($changes)){
                    $product = app(ProductService::class)->update($product->id,$changes);
                    $revisingFeed = $this->generateReviseItemFeed($product, $changes);
                    $response = app(ApiService::class)->reviseItem($revisingFeed);
                    dd($response);
                    $listingId = $this->extractListingId($response);
                    $updatedProducts[] = 'https://sandbox.ebay.com/itm/'.$listingId;
                }
            }
        }


        if(empty($updatedProducts) && empty($newProducts)){
            $allProducts = array();
            foreach ($products as $product) {
                $allProducts[] = 'https://sandbox.ebay.com/itm/'.$product->listing_id;
            }
            return response([
                'message' => 'No updates available',
                'all_products' => $allProducts
            ]);
        }
        return response([
            'message' => 'Feed synchronized successfully',
            'new_products' => $newProducts,
            'updated_products' => $updatedProducts
        ]);
    }

    private function extractListingId($xmlResponse): string
    {
        $xml = simplexml_load_string($xmlResponse);
        $xml->registerXPathNamespace('ns', 'urn:ebay:apis:eBLBaseComponents');
        $listingId = $xml->xpath('//ns:ItemID');
        if(!isset($listingId[0])){
            return $this->extractDuplicateListingId($xmlResponse);
        }else{
            return (string) $listingId[0];
        }
    }

    private function extractDuplicateListingId($xmlResponse): string
    {

        $xml = simplexml_load_string($xmlResponse);
        $xml->registerXPathNamespace('ns', 'urn:ebay:apis:eBLBaseComponents');
        $itemId = $xml->xpath('//ns:Errors/ns:ErrorParameters[@ParamID="1"]/ns:Value');
        return $itemId[0] ?? 0;
    }

    private function findChanges($product, $productData): array
    {
        $changes = [];
        foreach ($productData as $key => $value) {
            if ($product->{$key} != $value) {
                $changes[$key] = $value;
            }
        }
        return $changes;
    }

    public function generateReviseItemFeed($product, $changes): string
    {
        $listingId = $product->listing_id;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $xml .= '<Item>';
        $xml .= '<ItemID>' . $listingId . '</ItemID>';
        foreach ($changes as $key => $value) {
            if($key == 'category_id'){
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
            elseif($key == 'price'){
                $xml .= '<StartPrice>' . $value . '</StartPrice>';
                $xml .= '<Currency>EUR</Currency>';
            }
            elseif($key == 'shipping_details'){
                $xml = $this->getShippingOptions($xml, $value);
            }
            elseif ($key == 'description'){
                $xml .= '<Description><![CDATA['.$value.']]></Description>';
            }
            elseif(!in_array($key,['brand','model','specifications'])){
                $xml .= '<' . \App\Enums\Product::FIELD_MAPPING[$key] . '>' . $value . '</' . \App\Enums\Product::FIELD_MAPPING[$key] . '>';
            }
        }
        if(@$changes['brand'] || @$changes['model'] || @$changes['specifications']){
            $specifications = $product->specifications ?? array();
            $changes['brand'] = $product->brand;
            $changes['model'] = $product->model;
            $xml = $this->getSpecifications($xml, $changes, $specifications);
        }
        $xml .= '</Item>';
        $xml .= '</ReviseFixedPriceItemRequest>';

        return $xml;
    }

    public function generateListItemsFeed($productData): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
        $xml .= '<ErrorLanguage>en_US</ErrorLanguage>';
        $xml .= '<WarningLevel>High</WarningLevel>';
//        $productDataArray = [$productDataArray[0]];

        $shippingOptions = $productData['shipping_details'] ?? array();
        $stock = $productData['stock'];

        $xml .= '<Item>';
        $xml .= '<Title>' . $productData['title'] . '</Title>';
//            $xml .= '<Description>' . $productData['description'] . '</Description>';
        $xml .= '<Description><![CDATA['.$productData['description'].']]></Description>';
        $xml .= '<PrimaryCategory>';
        $xml .= '<CategoryID>'.$productData['category_id'].'</CategoryID>';
        $xml .= '</PrimaryCategory>';
        $xml .= '<StartPrice>' . $productData['price'] . '</StartPrice>';
        $xml .= '<Quantity>' . $stock . '</Quantity>'; // Quantity
        $xml .= '<ConditionID>'.$productData['condition'].'</ConditionID>';
        $xml .= '<Country>NL</Country>';
        $xml .= '<Currency>EUR</Currency>';
        $xml .= '<DispatchTimeMax>3</DispatchTimeMax>';
        $xml .= '<ListingDuration>GTC</ListingDuration>';
        $xml .= '<ListingType>FixedPriceItem</ListingType>';
        $xml .= '<PostalCode>'.$productData['postal_code'].'</PostalCode>';

        if(!empty($shippingOptions)){
            $xml = $this->getShippingOptions($xml, $shippingOptions);
        }

        $xml .= $this->getShippingPolicies();

        $xml .= '<PictureDetails>';
        foreach ($productData['images'] as $url) {
            $xml .= '<PictureURL>' . $url . '</PictureURL>';
        }
        $xml .= '</PictureDetails>';
        $specifications = $productData['specifications'] ?? array();

        $xml = $this->getSpecifications($xml, $productData, $specifications);

        $xml .= '</Item>';


        $xml .= '</AddFixedPriceItemRequest>';

        return $xml;
    }




}
