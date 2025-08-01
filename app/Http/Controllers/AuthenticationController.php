<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\CredentialService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class AuthenticationController extends Controller
{
    public ApiService $service;

    public function __construct(ApiService $apiService)
    {
        $this->service = $apiService;
    }

    /**
     * @throws GuzzleException
     */
    public function handleRedirect(Request $request)
    {
        $authorizationCode = $request->code;
        if (!$authorizationCode) return response(['message' => 'No authorization code'], 400);

        $tokens = $this->service->getTokensByCode($authorizationCode);
        $storeName = $this->service->getStoreName($tokens['access_token']);
        $tokens['store_name'] = $storeName;
        app(CredentialService::class)->createCredential($tokens);
        return response([
            'message' => 'Authorization success',
            'store_name' => $storeName,
        ]);
    }
}
