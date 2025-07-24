<?php

use App\Services\ApiService;
use Illuminate\Support\Facades\Route;

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
