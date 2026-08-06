<?php

return [
    'expected_host' => env('STAGING_EXPECTED_HOST', 'staging.sayaraforce.com'),
    'expected_database' => env('STAGING_EXPECTED_DB_DATABASE', 'sayaraforce_staging'),
    'schema_baseline_approved' => filter_var(env('STAGING_SCHEMA_BASELINE_APPROVED', false), FILTER_VALIDATE_BOOL),

    'production' => [
        'app_urls' => env('STAGING_PRODUCTION_APP_URL_DENYLIST', 'https://sayaraforce.com,https://app.sayaraforce.com'),
        'database_hosts' => env('STAGING_PRODUCTION_DB_HOST_DENYLIST', ''),
        'waba_ids' => env('STAGING_META_PRODUCTION_WABA_ID_DENYLIST', ''),
        'phone_number_ids' => env('STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST', ''),
    ],

    'meta' => [
        'allowed_waba_ids' => env('STAGING_META_ALLOWED_WABA_IDS', ''),
        'allowed_phone_number_ids' => env('STAGING_META_ALLOWED_PHONE_NUMBER_IDS', ''),
        'allow_legacy_company_resolution' => filter_var(
            env('STAGING_ALLOW_LEGACY_COMPANY_RESOLUTION', false),
            FILTER_VALIDATE_BOOL
        ),
    ],

    'communications' => [
        'whatsapp_outbound_enabled' => filter_var(env('STAGING_WHATSAPP_OUTBOUND_ENABLED', false), FILTER_VALIDATE_BOOL),
        'sms_outbound_enabled' => filter_var(env('STAGING_SMS_OUTBOUND_ENABLED', false), FILTER_VALIDATE_BOOL),
        'allowed_phone_recipients' => env('STAGING_MESSAGE_RECIPIENT_ALLOWLIST', ''),
        'allowed_email_recipients' => env('STAGING_EMAIL_RECIPIENT_ALLOWLIST', ''),
        'allowed_email_domains' => env('STAGING_EMAIL_DOMAIN_ALLOWLIST', ''),
    ],

    'deployment' => [
        'branch' => env('DEPLOYED_BRANCH'),
        'commit' => env('DEPLOYED_COMMIT'),
        'time' => env('DEPLOYED_AT'),
    ],
];
