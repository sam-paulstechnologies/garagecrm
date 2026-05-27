<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */
    'public_disk' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    'allowed_mimes' => env('DOC_ALLOWED_MIMES', 'pdf,jpg,jpeg,png'),
    'max_size_mb' => (int) env('DOC_MAX_SIZE_MB', 20),

    /*
    |--------------------------------------------------------------------------
    | Behavior
    |--------------------------------------------------------------------------
    */
    'auto_dedupe' => true,

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'inbox_page_size' => 20,

    /*
    |--------------------------------------------------------------------------
    | HTTP Fetching
    |--------------------------------------------------------------------------
    | Keep remote attachment URL fetching disabled by default.
    | Base64 uploads are safer because the verified provider sends the content
    | directly instead of asking our server to fetch arbitrary URLs.
    |--------------------------------------------------------------------------
    */
    'http_timeout_seconds' => (int) env('DOC_HTTP_TIMEOUT_SECONDS', 30),
    'http_user_agent' => env('DOC_HTTP_USER_AGENT', 'GarageCRM/1.0'),

    'allow_remote_attachment_urls' => (bool) env('DOC_ALLOW_REMOTE_ATTACHMENT_URLS', false),

    'allowed_attachment_hosts' => array_filter(array_map(
        fn ($host) => strtolower(trim($host)),
        explode(',', env('DOC_ALLOWED_ATTACHMENT_HOSTS', ''))
    )),

    /*
    |--------------------------------------------------------------------------
    | Webhook Verification
    |--------------------------------------------------------------------------
    | Email inbound webhook must fail closed.
    | Add DOC_EMAIL_WEBHOOK_SECRET in .env before enabling this route.
    |--------------------------------------------------------------------------
    */
    'email_webhook_secret' => env('DOC_EMAIL_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Legacy Verification Toggles
    |--------------------------------------------------------------------------
    | Kept for backward compatibility, but the email inbound controller now
    | requires DOC_EMAIL_WEBHOOK_SECRET and fails closed if missing.
    |--------------------------------------------------------------------------
    */
    'verify_twilio_signature' => (bool) env('DOC_VERIFY_TWILIO_SIGNATURE', false),
    'verify_email_signature' => true,
];