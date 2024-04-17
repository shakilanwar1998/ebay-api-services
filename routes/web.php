<?php

use Illuminate\Support\Facades\Route;

Route::get('/',function(){
    return response([
        'message' => "Welcome to eBay API services module"
    ]);
});

Route::get('/auth',[\App\Http\Controllers\AuthenticationController::class,'handleRedirect']);


Route::get('/test',function(){
//    phpinfo();
    return app(\App\Services\FeedService::class)->syncFeedWithDB();
});

