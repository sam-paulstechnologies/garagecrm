<?php

return [
    // Production rollout begins at "off", then "audit", then "required_admins".
    'two_factor_enforcement' => env('TWO_FACTOR_ENFORCEMENT', 'off'),
    'mandatory_roles' => [
        'platform' => ['super_admin', 'platform_admin'],
        'tenant' => ['admin'],
    ],
    'step_up_window_minutes' => (int) env('SECURITY_STEP_UP_WINDOW_MINUTES', 15),
    'challenge_attempts_per_minute' => (int) env('TWO_FACTOR_CHALLENGE_ATTEMPTS_PER_MINUTE', 5),

    /*
    |--------------------------------------------------------------------------
    | Browser response hardening
    |--------------------------------------------------------------------------
    | Keep this policy deliberately compatible with the existing Meta popup and
    | Stripe-hosted checkout. Inline-script removal and nonce-based CSP are
    | tracked as a separate hardening milestone.
    */
    'content_security_policy' => env(
        'SECURITY_CONTENT_SECURITY_POLICY',
        "object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https://checkout.stripe.com"
    ),
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | Private tenant uploads
    |--------------------------------------------------------------------------
    */
    'private_upload_disk' => env('PRIVATE_UPLOAD_DISK', env('FILESYSTEM_DISK', 'local')),
    'upload_max_bytes' => (int) env('SECURITY_UPLOAD_MAX_BYTES', 20 * 1024 * 1024),
    'upload_allowed_mimes' => [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ],
];
