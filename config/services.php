<?php
// config/services.php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Providers (Meta / Twilio / Gupshup)
    |--------------------------------------------------------------------------
    | Select provider via WHATSAPP_PROVIDER=meta|twilio|gupshup
    */
    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),

        // Meta (Cloud API)
        'meta' => [
            'phone_id' => env('WHATSAPP_META_PHONE_ID'),
            'token'    => env('WHATSAPP_META_ACCESS_TOKEN', env('WHATSAPP_ACCESS_TOKEN')), // fallback
            'graph'    => env('WHATSAPP_GRAPH_BASE', 'https://graph.facebook.com/v20.0'),
        ],

        // Verify webhook token
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'supersecret'),

        // Twilio
        'twilio' => [
            'from'  => env('TWILIO_WHATSAPP_FROM'),
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
        ],

        // Gupshup
        'gupshup' => [
            'app' => env('GUPSHUP_APPNAME'),
            'key' => env('GUPSHUP_APIKEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Lead Ads (SaaS-friendly, app-level only)
    |--------------------------------------------------------------------------
    | Per-tenant Page tokens + Form IDs are stored in DB (e.g., meta_pages).
    | Do NOT put per-tenant tokens in .env for SaaS.
    */
    'meta' => [
        'app_id'        => env('META_APP_ID'),
        'app_secret'    => env('META_APP_SECRET'),
        'verify_token'  => env('META_VERIFY_TOKEN', env('WHATSAPP_VERIFY_TOKEN', 'supersecret')),
        'graph_version' => env('META_GRAPH_VERSION', 'v19.0'),
        'graph_base'    => env('META_GRAPH_BASE', 'https://graph.facebook.com'),

        // Deprecated (kept here as reference; do not use for SaaS):
        // 'access_token'  => env('META_ACCESS_TOKEN'),
        // 'form_id'       => env('META_FORM_ID'),
    ],

    'leads' => [
        // Consider same person (email/phone) a duplicate within this window (days)
        'dedupe_days' => env('LEADS_DEDUPE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | cURL / SSL CA bundle (used by HTTP requests)
    |--------------------------------------------------------------------------
    | Guarantees both web + CLI use the same certificate file.
    */
    'curl_ca_bundle' => env('CURL_CA_BUNDLE', 'C:/php/extras/ssl/cacert.pem'),

];
