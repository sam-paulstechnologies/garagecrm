<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),
    'guard' => ['web'],
    // Bearer tokens are short-lived by default. First-party sessions are not
    // affected, and callers may use a stricter per-token expiry.
    'expiration' => (int) env('SANCTUM_EXPIRATION', 60),
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'sf_'),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
