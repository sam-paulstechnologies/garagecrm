<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mail / Slack
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
    | OpenAI
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'api_key'  => env('OPENAI_API_KEY'),
        'timeout'  => (int) env('OPENAI_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp SaaS Safe Configuration
    |--------------------------------------------------------------------------
    */

    'whatsapp' => [

        // Default fallback provider
        'provider' => env('WHATSAPP_PROVIDER', 'meta'),

        'meta' => [
            'graph_base'  => env('WHATSAPP_META_GRAPH_BASE', 'https://graph.facebook.com'),
            'api_version' => env('WHATSAPP_META_API_VERSION', 'v20.0'),
        ],

        'twilio' => [
            'base_uri' => env('TWILIO_BASE_URI', 'https://api.twilio.com'),
        ],

        'gupshup' => [
            'base_uri' => env('GUPSHUP_BASE_URI', 'https://api.gupshup.io'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Lead Ads / Facebook Lead Forms
    |--------------------------------------------------------------------------
    | This is for Meta Lead Ads only, not WhatsApp Cloud API.
    |--------------------------------------------------------------------------
    */

    'meta_leads' => [
        'app_id'        => env('META_APP_ID'),
        'app_secret'    => env('META_APP_SECRET'),
        'verify_token'  => env('META_VERIFY_TOKEN'),
        'graph_base'    => env('META_GRAPH_BASE', 'https://graph.facebook.com'),
        'graph_version' => env('META_GRAPH_VERSION', 'v20.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Leads Config
    |--------------------------------------------------------------------------
    */

    'leads' => [
        'dedupe_days' => env('LEADS_DEDUPE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared cURL CA Bundle
    |--------------------------------------------------------------------------
    */

    'curl_ca_bundle' => env('CURL_CA_BUNDLE'),

];