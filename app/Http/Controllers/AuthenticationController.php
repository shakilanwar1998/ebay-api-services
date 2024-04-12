<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    public ApiService $service;

    public function __construct(ApiService $apiService)
    {
        $this->service = $apiService;
    }
    public function handleRedirect(Request $request){
//        $authorizationCode = $request->code;
        $authorizationCode = "v^1.1#i^1#f^0#r^1#I^3#p^3#t^Ul41XzU6MzNGMjczOURBRjYxRUZGOUZFMTFEQUVGRkVERDcyRkFfMl8xI0VeMTI4NA==";
        if(!$authorizationCode) return response(['message' => 'No authorization code'],400);

        $token = $this->service->getAccessToken($authorizationCode);
        dd($token);
    }
}
