<?php

return [

    'paths' => ['*'], // allow all paths temporarily

    'allowed_methods' => ['*'], // allow all HTTP methods

    'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'], // React dev server origins

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // allow all headers

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // must be false if you're not using auth session cookies

];
