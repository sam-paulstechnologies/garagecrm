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
    // Baseline ENFORCED policy — clickjacking/base/form protections that are
    // already safe with the current app. Kept enforced so nothing regresses.
    'content_security_policy' => env(
        'SECURITY_CONTENT_SECURITY_POLICY',
        "object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self' https://checkout.stripe.com"
    ),
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | Strong Content-Security-Policy (Fable M3) — staged rollout
    |--------------------------------------------------------------------------
    | A real policy with default-src/script-src that actually mitigates XSS.
    | Because the Meta Embedded Signup flow and some pages still emit inline
    | scripts, this is shipped REPORT-ONLY by default (SECURITY_CSP_ENFORCE=false)
    | so violations are reported without breaking the app. Once inline scripts
    | carry nonces/hashes and Meta UAT confirms compatibility, flip
    | SECURITY_CSP_ENFORCE=true to enforce it (the baseline above is then
    | superseded by this stronger policy).
    */
    'csp' => [
        'enabled' => (bool) env('SECURITY_CSP_ENABLED', true),
        'enforce' => (bool) env('SECURITY_CSP_ENFORCE', false),
        'report_uri' => env('SECURITY_CSP_REPORT_URI'),
        'directives' => [
            'default-src' => "'self'",
            'base-uri' => "'self'",
            'object-src' => "'none'",
            'frame-ancestors' => "'self'",
            'form-action' => "'self' https://checkout.stripe.com",
            'script-src' => "'self' https://connect.facebook.net https://js.stripe.com",
            'style-src' => "'self' 'unsafe-inline'",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' data:",
            'connect-src' => "'self' https://graph.facebook.com",
            'frame-src' => "'self' https://js.stripe.com https://www.facebook.com https://web.facebook.com https://staticxx.facebook.com",
        ],
        'append' => 'upgrade-insecure-requests',
    ],

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
