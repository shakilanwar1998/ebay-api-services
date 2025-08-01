<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Session;

class ApiService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;

    protected string $redirectUri;

    protected string $scopes = "https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.fulfillment";


    public function __construct()
    {
        $this->clientId = config('ebay.client_id');
        $this->clientSecret = config('ebay.client_secret');
        $this->redirectUri = config('ebay.redirect_uri');
        $env = Session::get('ebay_env', 'production');
        $this->baseUrl = $env === 'sandbox'
            ? 'https://api.sandbox.ebay.com/'
            : 'https://api.ebay.com/';
    }

    /**
     * @throws GuzzleException
     */
    public function getAccessToken($refreshToken): bool|array
    {
        $client = new Client();
        $authUrl = $this->baseUrl . 'identity/v1/oauth2/token';

        try {
            $response = $client->post($authUrl, [
                'form_params' => [
                    "refresh_token" => $refreshToken,
                    "grant_type" => "refresh_token",
                    "scope" => $this->scopes,
                    "redirect_uri" => $this->redirectUri,
                ],

                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode("$this->clientId:$this->clientSecret"),
                ]
            ]);
            $response = json_decode($response->getBody()->getContents());
            $accessExpiresAt = now();
            $accessExpiresAt = $accessExpiresAt->addSeconds($response->expires_in);

            return $response->access_token ? [
                'access_token' => $response->access_token,
                'access_token_valid_till' => $accessExpiresAt
            ] : false;

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                echo "HTTP Status Code: $statusCode\n";
                echo "Error Body: $body\n";
            } else {
                echo "Error occurred without a response.\n";
            }
            return false;
        }
    }

    /**
     * @throws GuzzleException
     */
    public function getTokensByCode($code): bool|array
    {
        $client = new Client();
        $authUrl = $this->baseUrl . 'identity/v1/oauth2/token';
        $response = $client->post($authUrl, [
            'form_params' => [
                "code" => $code,
                "grant_type" => "authorization_code",
                "redirect_uri" => $this->redirectUri,
                "scope" => $this->scopes,
            ],

            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode("$this->clientId:$this->clientSecret"),
            ]
        ]);
        $response = json_decode($response->getBody()->getContents());

        $rfExpiresAt = now();
        $accessExpiresAt = now();
        $rfExpiresAt = $rfExpiresAt->addSeconds($response->refresh_token_expires_in);
        $accessExpiresAt = $accessExpiresAt->addSeconds($response->expires_in);

        return $response->refresh_token ? [
            'refresh_token' => $response->refresh_token,
            'access_token' => $response->access_token,
            'rf_token_valid_till' => $rfExpiresAt,
            'access_token_valid_till' => $accessExpiresAt
        ] : false;
    }

    /**
     * Get APP access token for eBay Taxonomy API and cache it
     * @throws GuzzleException
     */
    public function getAppAccessToken(): string
    {
        // Check if we have a cached token
        $cachedToken = \Cache::get('ebay_app_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        $client = new Client();
        $baseUrl = $this->getBaseUrlForActiveStore();
        $authUrl = $baseUrl . 'identity/v1/oauth2/token';

        try {
            $response = $client->post($authUrl, [
                'form_params' => [
                    "grant_type" => "client_credentials",
                    "scope" => "https://api.ebay.com/oauth/api_scope",
                ],

                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode("$this->clientId:$this->clientSecret"),
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents());

            if ($responseData->access_token) {
                // Cache the token for 1 hour (or less if token expires sooner)
                $expiresIn = min($responseData->expires_in, 3600); // Cache for max 1 hour
                \Cache::put('ebay_app_token', $responseData->access_token, $expiresIn);

                return $responseData->access_token;
            }

            throw new \Exception('Failed to get APP access token');

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                \Log::error("eBay APP Token Error - Status: $statusCode, Body: $body");
            } else {
                \Log::error("eBay APP Token Error - No response");
            }
            throw new \Exception('Failed to get APP access token: ' . $e->getMessage());
        }
    }

    /**
     * Get base URL for the active store's environment
     */
    private function getBaseUrlForActiveStore(): string
    {
        // Get the active credential's environment
        $credential = \App\Models\Credential::where('is_active', true)->first();

        if (!$credential) {
            // Fallback to session environment or default to production
            $env = Session::get('ebay_env', 'production');
            return $env === 'sandbox'
                ? 'https://api.sandbox.ebay.com/'
                : 'https://api.ebay.com/';
        }

        // Return appropriate base URL based on environment
        return $credential->environment === 'sandbox'
            ? 'https://api.sandbox.ebay.com/'
            : 'https://api.ebay.com/';
    }

    /**
     * @throws GuzzleException
     */
    public function listItems($data): string
    {
        return $this->makeRequest('AddFixedPriceItem', $data);
    }

    /**
     * @throws GuzzleException
     */
    public function reviseItem($data): string
    {
        return $this->makeRequest('ReviseFixedPriceItem', $data);
    }

    /**
     * Fetch the eBay store name using the Sell Account API.
     */
    public function getStoreName($accessToken): ?string
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->get('https://api.ebay.com/sell/account/v1/store', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            return $data['storeName'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @throws GuzzleException
     */
    protected function makeRequest($callName, $data): string
    {
        $client = new Client();
        $apiUrl = $this->baseUrl . 'ws/api.dll';
        $accessToken = app(CredentialService::class)->getAccessToken();

        $response = $client->post($apiUrl, [
            'headers' => [
                'X-EBAY-API-COMPATIBILITY-LEVEL' => 967,
                'X-EBAY-API-CALL-NAME' => $callName,
                'X-EBAY-API-SITEID' => '146',
                'X-EBAY-API-DETAIL-LEVEL' => '0',
                'X-EBAY-API-IAF-TOKEN' => $accessToken,
                'Content-Type' => 'text/xml',
            ],
            'body' => $data,
            'verify' => false
        ]);

        return $response->getBody()->getContents();
    }
}
