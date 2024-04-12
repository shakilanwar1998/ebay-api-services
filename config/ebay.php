<?php

return [
    'client_id' => env('EBAY_CLIENT_ID',''),
    'client_secret' => env('EBAY_CLIENT_SECRET',''),
    'redirect_uri' => env('EBAY_REDIRECT_URI',''),
    'baseUrl' => env('ebay_sandbox')?'https://api.sandbox.ebay.com/':'https://api.ebay.com/'
];