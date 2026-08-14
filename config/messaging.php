<?php

return [
    'default_product' => env('MESSAGING_PRODUCT_KEY', 'sayaraforce'),

    'history' => [
        'hmac_key' => env('WHATSAPP_HISTORY_HMAC_KEY') ?: env('APP_KEY'),
        'review_retention_days' => (int) env('WHATSAPP_HISTORY_REVIEW_RETENTION_DAYS', 30),
        'analysis_message_limit' => (int) env('WHATSAPP_HISTORY_ANALYSIS_MESSAGE_LIMIT', 40),
        'analysis_character_limit' => (int) env('WHATSAPP_HISTORY_ANALYSIS_CHARACTER_LIMIT', 12000),
    ],

    'providers' => [
        'meta_whatsapp' => [
            'graph_base' => env('META_GRAPH_BASE', 'https://graph.facebook.com'),
            'api_version' => env('META_GRAPH_API_VERSION', env('META_GRAPH_VERSION', 'v25.0')),
            'app_id' => env('META_APP_ID'),
            'app_secret' => env('META_APP_SECRET'),
            'system_user_id' => env('META_WHATSAPP_SYSTEM_USER_ID'),
            'system_user_access_token' => env('META_WHATSAPP_SYSTEM_USER_ACCESS_TOKEN'),
            'business_app_config_id' => env('META_WHATSAPP_BUSINESS_APP_CONFIG_ID'),
            'cloud_api_config_id' => env('META_WHATSAPP_CLOUD_API_CONFIG_ID', env('META_EMBEDDED_SIGNUP_CONFIG_ID')),
            'embedded_signup_version' => env('META_WHATSAPP_EMBEDDED_SIGNUP_VERSION', 'v4'),
            'session_info_version' => env('META_WHATSAPP_SESSION_INFO_VERSION', '3'),
            'session_ttl_minutes' => (int) env('META_WHATSAPP_SIGNUP_SESSION_TTL', 15),
            'webhook_verify_token' => env('META_WHATSAPP_VERIFY_TOKEN', env('META_VERIFY_TOKEN')),
            'waba_callback_override_enabled' => (bool) env('META_WHATSAPP_WABA_CALLBACK_OVERRIDE_ENABLED', false),
            'webhook_callback_url' => env('META_WHATSAPP_WEBHOOK_CALLBACK_URL'),
            'required_webhook_fields' => [
                'messages',
            ],
            'required_coexistence_webhook_fields' => [
                'history',
                'smb_app_state_sync',
                'smb_message_echoes',
            ],
        ],
    ],
];
