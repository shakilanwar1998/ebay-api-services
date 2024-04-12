<?php

use Illuminate\Support\Facades\Route;

Route::get('/auth',[\App\Http\Controllers\AuthenticationController::class,'handleRedirect']);
