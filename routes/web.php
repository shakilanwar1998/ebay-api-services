<?php

use Illuminate\Support\Facades\Route;

Route::get('/',function(){
    return response([
        'message' => "Welcome to eBay API services module"
    ]);
});

Route::get('/auth',[\App\Http\Controllers\AuthenticationController::class,'handleRedirect']);


Route::get('/test',function(){
    // Start building the XML string
    $xmlString = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
    $xmlString .= '<EndItemsRequest xmlns="urn:ebay:apis:eBLBaseComponents">' . "\n";
    $xmlString .= "\t" . '<ErrorLanguage>en_US</ErrorLanguage>' . "\n";
    $xmlString .= "\t" . '<WarningLevel>High</WarningLevel>' . "\n";

// Array of items
    $items = array(
        "110554872771",
        "110554884434",
        "110554884435",
        "110554884436",
        "110554884437",
        "110554884438",
        "110554884470",
        "110554884471",
        "110554884472",
        "110554884474",
        "110554884475",
        "110554884476",
        "110554884477",
        "110554884478",
        "110554884479",
        "110554884480",
        "110554884481",
        "110554884482",
        "110554884483",
        "110554884484",
        "110554884485",
        "110554884486",
        "110554884487",
        "110554884489",
        "110554884490",
        "110554884491",
        "110554884492",
        "110554884493",
        "110554884494",
        "110554884495",
        "110554884494",
        "110554884497",
        "110554884498",
        "110554884499",
        "110554884500",
        "110554884501",
        "110554884502",
        "110554883671"
    );

// Loop through each item and add it to the XML
    foreach ($items as $index => $itemID) {
        $xmlString .= "\t" . '<EndItemRequestContainer>' . "\n";
        $xmlString .= "\t\t" . '<MessageID>Listing' . ($index + 1) . '</MessageID>' . "\n";
        $xmlString .= "\t\t" . '<EndingReason>NotAvailable</EndingReason>' . "\n";
        $xmlString .= "\t\t" . '<ItemID>' . $itemID . '</ItemID>' . "\n";
        $xmlString .= "\t" . '</EndItemRequestContainer>' . "\n";
    }

    $xmlString .= '</EndItemsRequest>';

// Output the generated XML
    dd($xmlString);

//    return app(\App\Services\FeedService::class)->syncFeedWithDB();
});

