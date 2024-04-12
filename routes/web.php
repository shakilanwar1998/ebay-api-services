<?php

use Illuminate\Support\Facades\Route;

Route::get('/',function(){
    return response([
        'message' => "Welcome to eBay API services module"
    ]);
});

Route::get('/test',function(\Illuminate\Http\Request $request){
    dd($request->all());
});

Route::get('/auth',[\App\Http\Controllers\AuthenticationController::class,'handleRedirect']);
