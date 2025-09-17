<?php

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
        'provider'     => env('WHATSAPP_PROVIDER', 'meta'),

        // Meta (Cloud API)
        'meta' => [
            'phone_id' => env('WHATSAPP_META_PHONE_ID'),
            'token'    => env('WHATSAPP_META_ACCESS_TOKEN', env('WHATSAPP_ACCESS_TOKEN')), // fallback to old var
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

];
