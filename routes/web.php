<?php

use App\Services\ApiService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/',function(){
    return response([
        'message' => "Welcome to eBay API services module"
    ]);
});

Route::get('/auth',[\App\Http\Controllers\AuthenticationController::class,'handleRedirect']);


Route::get('/update',function(){
    return app(\App\Services\FeedService::class)->syncFeedWithDB();
});

Route::get('/list',function(){
    return app(\App\Services\ProductService::class)->getItemIds();
});

Route::get('/test',function(){
    $token = "v^1.1#i^1#p^3#I^3#f^0#r^1#t^Ul4xMF83OjE4NEJEMUZDNTA3MkRBNUQzMUIzODQ2Q0NDMTM5RTVBXzBfMSNFXjI2MA==";
    $tokens = app(ApiService::class)->getAccessToken($token);
//    $authorizationCode = "v^1.1#i^1#p^3#r^1#I^3#f^0#t^Ul41XzQ6NDNERDAwRjU2RDZBNjkxM0Q4NEQ0MDg1RjE3NDdERDRfMF8xI0VeMjYw";
//    $tokens = (new \App\Services\ApiService())->getTokensByCode($authorizationCode);
    dd($tokens);
});

Route::get('/ebay/oauth/start', function () {
    $env = Session::get('ebay_env', 'production');
    $baseAuthUrl = $env === 'sandbox'
        ? 'https://auth.sandbox.ebay.com/oauth2/authorize'
        : 'https://auth.ebay.com/oauth2/authorize';
    // TODO: Add your client_id, redirect_uri, and scopes as needed
    $query = http_build_query([
        'client_id' => config('ebay.client_id'),
        'redirect_uri' => config('ebay.redirect_uri'),
        'response_type' => 'code',
        'scope' => 'https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.fulfillment',
        'state' => csrf_token(),
    ]);
    return redirect($baseAuthUrl . '?' . $query);
})->name('ebay.oauth.start');

Route::get('products/import/template', function () {
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
    ];
    $columns = [
        'sku', 'listing_id', 'title', 'description', 'category_id', 'local_category_name', 'price', 'stock', 'brand', 'model', 'images', 'condition', 'shipping_details', 'postal_code', 'specifications', 'exceptions'
    ];
    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, $columns);
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);
    return response($csv, 200, $headers);
})->name('products.import.template');
