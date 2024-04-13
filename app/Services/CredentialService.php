<?php

namespace App\Services;

use App\Models\Credential;

class CredentialService
{
    public function renewTokens($refreshToken, $accessToken): void
    {
        Credential::updateOrCreate([],['refresh_token' => $refreshToken, 'access_token' => $accessToken]);
    }
}
