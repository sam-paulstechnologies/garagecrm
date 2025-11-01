<?php
// config/services.php

return [

    // --- Mail / Slack (unchanged) ---
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend'   => ['key' => env('RESEND_KEY')],
    'ses'      => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'slack'    => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI (for NLP/intent & entity extraction)
    |--------------------------------------------------------------------------
    */
    'openai' => [
        // kept here for convenience; NlpService also reads straight from env()
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model'    => env('OPENAI_MODEL', 'gpt-5.1-mini'),
        'api_key'  => env('OPENAI_API_KEY'),
        // optional sane default timeout for HTTP calls (seconds)
        'timeout'  => (int) env('OPENAI_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (SaaS-friendly)
    |--------------------------------------------------------------------------
    | Provider chosen via WHATSAPP_PROVIDER=twilio|meta|gupshup
    | All credentials come from env (tenant data like phone numbers/templates
    | should live in DB, not here).
    */
    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),

        // Twilio (account-level creds only)
        'twilio' => [
            'sid'      => env('TWILIO_SID'),
            'token'    => env('TWILIO_TOKEN'),
            'from'     => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
            'base_uri' => env('TWILIO_API_BASE', 'https://api.twilio.com'),
        ],

        // Meta Cloud API (account-level; per-tenant phone_id/token live in DB if needed)
        'meta' => [
            'graph'        => env('WHATSAPP_GRAPH_BASE', 'https://graph.facebook.com'),
            'version'      => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
            'token'        => env('WHATSAPP_META_ACCESS_TOKEN'), // optional app-level token
            'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'supersecret'),
        ],

        // Gupshup (account-level)
        'gupshup' => [
            'app'      => env('GUPSHUP_APPNAME'),
            'key'      => env('GUPSHUP_APIKEY'),
            'base_uri' => env('GUPSHUP_API_BASE', 'https://api.gupshup.io'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Lead Ads (SaaS-friendly)
    |--------------------------------------------------------------------------
    | Tenant-specific page tokens / form ids must be in DB.
    */
    'meta' => [
        'app_id'        => env('META_APP_ID'),
        'app_secret'    => env('META_APP_SECRET'),
        'verify_token'  => env('META_VERIFY_TOKEN', env('WHATSAPP_VERIFY_TOKEN', 'supersecret')),
        'graph_base'    => env('META_GRAPH_BASE', 'https://graph.facebook.com'),
        'graph_version' => env('META_GRAPH_VERSION', 'v19.0'),
    ],

    'leads' => [
        'dedupe_days' => env('LEADS_DEDUPE_DAYS', 30),
    ],

    // Shared cURL CA bundle (CLI + FPM) – optional
    'curl_ca_bundle' => env('CURL_CA_BUNDLE', 'C:/php/extras/ssl/cacert.pem'),
];
