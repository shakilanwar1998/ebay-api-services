<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;

    protected string $redirectUri;

    protected string $scopes = "https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly";

    public function __construct()
    {
        $this->clientId = config('ebay.client_id');
        $this->clientSecret = config('ebay.client_secret');
        $this->baseUrl = config('ebay.baseUrl');
        $this->redirectUri = config('ebay.redirect_uri');
    }

    /**
     * @throws GuzzleException
     */
    public function getAccessToken($refreshToken=null)
    {
        $refreshToken = "v^1.1#i^1#p^3#r^1#I^3#f^0#t^Ul4xMF8wOjNBREE0RUI5OEIyMkFFMkU1NEZDOEIwMERDQUNBMThCXzBfMSNFXjEyODQ=";
        $client = new Client();
        $authUrl = $this->baseUrl.'identity/v1/oauth2/token';
        $response  = $client->post($authUrl,  [
            'form_params' => [
                "refresh_token" => $refreshToken,
                "grant_type" => "refresh_token",
                "scope" => $this->scopes,
            ],

            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode("$this->clientId:$this->clientSecret"),
            ]
        ]);
        return json_decode($response->getBody()->getContents())->access_token;
    }

    /**
     * @throws GuzzleException
     */
    public function getTokensByCode($code): bool|array
    {
        $client = new Client();
        $authUrl = $this->baseUrl.'identity/v1/oauth2/token';
        $response  = $client->post($authUrl,  [
            'form_params'=> [
                "code"=>$code,
                "grant_type"=>"authorization_code",
                "redirect_uri" => $this->redirectUri,
                "scope"=> $this->scopes,
            ],

            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '.base64_encode("$this->clientId:$this->clientSecret"),
            ]
        ]);
        $response = json_decode($response->getBody()->getContents());

        return $response->refresh_token ? ['refresh_token' => $response->refresh_token, 'access_token' => $response->access_token] : false;
    }
}
