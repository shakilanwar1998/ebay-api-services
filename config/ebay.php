<?php

return [
    'client_id' => env('EBAY_CLIENT_ID',''),
    'client_secret' => env('EBAY_CLIENT_SECRET',''),
    'redirect_uri' => env('EBAY_REDIRECT_URI',''),
    'baseUrl' => env('EBAY_SANDBOX')?'https://api.sandbox.ebay.com/':'https://api.ebay.com/',
    'feed_url' => env('FEED_URL','')
];
