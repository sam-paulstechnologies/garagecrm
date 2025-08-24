<?php

return [
    'driver' => env('PAYMENTS_DRIVER', 'aps'),
    'aps' => [
        'merchant_id' => env('APS_MERCHANT_ID'),
        'access_code' => env('APS_ACCESS_CODE'),
        'sha_request' => env('APS_SHA_REQUEST'),
        'sha_response' => env('APS_SHA_RESPONSE'),
        'mode' => env('APS_MODE', 'sandbox'), // sandbox|live
        'return_url' => env('APP_URL').'/payments/aps/return',
    ],
];
