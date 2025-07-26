<?php

namespace App\Services;

use App\Models\Credential;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Session;

class CredentialService
{
    public function renewTokens($data): void
    {
        $data['environment'] = Session::get('ebay_env', 'production');
        $credential = Credential::where([
            'environment' => $data['environment']
        ])->firstOrNew();
        $credential->fill($data);
        $credential->save();
    }

    /**
     * @throws GuzzleException
     */
    public function getAccessToken()
    {
        $env = Session::get('ebay_env', 'production');
        $credentials = Credential::where([
            'environment' => $env
        ])->first();

        $accessToken = $credentials->access_token;
        if ($credentials->access_token_valid_till < now()) {
            $tokens = app(ApiService::class)->getAccessToken($credentials->refresh_token);
            $accessToken = $tokens['access_token'];
            app(CredentialService::class)->renewTokens($tokens);
        }
        return $accessToken;
    }

    public function createCredential($data): Credential
    {
        $data['environment'] = Session::get('ebay_env', 'production');
        $credential = new Credential($data);
        $credential->save();
        return $credential;
    }

    public function setActiveStore($id): void
    {
        Credential::setActiveStore($id);
    }
}
