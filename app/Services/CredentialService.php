<?php

namespace App\Services;

use App\Models\Credential;
use GuzzleHttp\Exception\GuzzleException;

class CredentialService
{
    public function renewTokens($data): void
    {
        $credential = Credential::firstOrNew();
        $credential->fill($data);
        $credential->save();
    }

    /**
     * @throws GuzzleException
     */
    public function getAccessToken()
    {
        $credentials = Credential::first();
        $accessToken = $credentials->access_token;
        if($credentials->access_token_valid_till < now()){
            $tokens = app(ApiService::class)->getAccessToken($credentials->refresh_token);
            $accessToken = $tokens['access_token'];
            app(CredentialService::class)->renewTokens($tokens);
        }
        return $accessToken;
    }
}
