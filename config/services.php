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
    | Meta / Facebook Shared Configuration
    |--------------------------------------------------------------------------
    | Used by:
    | - Meta Lead Ads
    | - WhatsApp Cloud API webhook signature validation
    | - SF-WA Connect embedded signup
    |--------------------------------------------------------------------------
    */

    'meta' => [
        'app_id'     => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),

        'graph_base' => env('META_GRAPH_BASE', 'https://graph.facebook.com'),

        /*
        |--------------------------------------------------------------------------
        | Keep both names because different parts of the app may read either.
        |--------------------------------------------------------------------------
        */
        'api_version'   => env('META_GRAPH_VERSION', env('WHATSAPP_META_API_VERSION', 'v20.0')),
        'graph_version' => env('META_GRAPH_VERSION', env('WHATSAPP_META_API_VERSION', 'v20.0')),

        /*
        |--------------------------------------------------------------------------
        | SF-WA Connect
        |--------------------------------------------------------------------------
        */
        'whatsapp_embedded_signup_config_id' => env('META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID'),
        'whatsapp_verify_token'              => env('META_WHATSAPP_VERIFY_TOKEN', env('META_VERIFY_TOKEN')),
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
            'graph_base'  => env('WHATSAPP_META_GRAPH_BASE', env('META_GRAPH_BASE', 'https://graph.facebook.com')),
            'api_version' => env('WHATSAPP_META_API_VERSION', env('META_GRAPH_VERSION', 'v20.0')),

            /*
            |--------------------------------------------------------------------------
            | SF-WA Connect
            |--------------------------------------------------------------------------
            */
            'embedded_signup_config_id' => env('META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID'),
            'verify_token'              => env('META_WHATSAPP_VERIFY_TOKEN', env('META_VERIFY_TOKEN')),
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
    | This is for Meta Lead Ads only.
    | Kept for backward compatibility with existing Meta lead form code.
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
    | Google Ads Lead Forms
    |--------------------------------------------------------------------------
    | This is for Google Ads Lead Form webhook capture.
    |
    | v1 does not need Google OAuth.
    | Garage enters:
    | - Webhook URL: https://app.sayaraforce.com/api/v1/webhooks/google/leads
    | - Webhook Key: lead_sources.form_token
    |--------------------------------------------------------------------------
    */

    'google_leads' => [
        'default_webhook_key' => env('GOOGLE_LEADS_WEBHOOK_KEY'),
        'source_type'         => env('GOOGLE_LEADS_SOURCE_TYPE', 'google'),
        'default_source_name' => env('GOOGLE_LEADS_DEFAULT_SOURCE_NAME', 'Google Ads'),
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
    | SayaraForce Public Website
    |--------------------------------------------------------------------------
    | These values are intentionally optional. The public demo form stores a
    | local enquiry until founder-approved CRM/WhatsApp routing is enabled.
    |--------------------------------------------------------------------------
    */

    'sayaraforce' => [
        'public_whatsapp_click_url' => env('SAYARAFORCE_PUBLIC_WHATSAPP_CLICK_URL'),
        'ga4_measurement_id' => env('SAYARAFORCE_GA4_MEASUREMENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared cURL CA Bundle
    |--------------------------------------------------------------------------
    */

    'curl_ca_bundle' => env('CURL_CA_BUNDLE'),

];
