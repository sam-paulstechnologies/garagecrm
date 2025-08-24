<?php

return [
    'driver' => env('WA_DRIVER', 'meta'),
    'meta' => [
        'phone_number_id' => env('WA_PHONE_NUMBER_ID'),
        'business_account_id' => env('WA_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WA_ACCESS_TOKEN'),
    ],
];
