<?php

return [
    // Fail closed everywhere. Staging explicitly opts into the fake provider;
    // production must not gain billing merely by receiving this code.
    'provider' => env('BILLING_PROVIDER', 'disabled'),
    'mode' => env('BILLING_MODE', 'test'),
    'checkout_enabled' => filter_var(env('BILLING_CHECKOUT_ENABLED', false), FILTER_VALIDATE_BOOL),
    'failed_payment' => [
        'grace_days' => (int) env('BILLING_FAILED_PAYMENT_GRACE_DAYS', 7),
    ],
    'fake' => [
        // Dedicated secret only. Never fall back to APP_KEY: an empty secret must
        // fail closed in FakeBillingGateway::verifyWebhook, not silently reuse the
        // application key as a signing secret.
        'webhook_secret' => env('BILLING_FAKE_WEBHOOK_SECRET'),
    ],
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com'),
        'api_version' => env('STRIPE_API_VERSION', '2026-07-29.dahlia'),
        'webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],
];
