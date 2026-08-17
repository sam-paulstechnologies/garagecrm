<?php

$packagedCommitPath = base_path('bootstrap/deployed-commit');
$packagedCommit = is_file($packagedCommitPath)
    ? trim((string) file_get_contents($packagedCommitPath))
    : null;

if (! is_string($packagedCommit) || preg_match('/^[0-9a-f]{40}$/', $packagedCommit) !== 1) {
    $packagedCommit = null;
}

return [
    'expected_host' => env('STAGING_EXPECTED_HOST', 'staging.sayaraforce.com'),
    'expected_database' => env('STAGING_EXPECTED_DB_DATABASE', 'sayaraforce_staging'),
    'schema_baseline_approved' => filter_var(env('STAGING_SCHEMA_BASELINE_APPROVED', false), FILTER_VALIDATE_BOOL),
    'schema_validation_mode' => filter_var(env('STAGING_SCHEMA_VALIDATION_MODE', false), FILTER_VALIDATE_BOOL),

    // Defence-in-depth safety flag (M31). Set STAGING_SAFETY_ENFORCED=true on any
    // box that must behave as staging-guarded regardless of APP_ENV. This keeps
    // outbound + provider-asset guards ACTIVE even if APP_ENV drifts (unset,
    // mislabeled) so safety never rests on a single environment string.
    'safety_mode' => filter_var(env('STAGING_SAFETY_ENFORCED', false), FILTER_VALIDATE_BOOL),

    'production' => [
        // Denylists of REAL production identifiers. The operator MUST populate
        // these on the staging box; while the guard is active an empty required
        // denylist is treated as a configuration failure (fail closed) — see
        // App\Support\Staging\StagingSafety::denylistReadiness(). No production
        // values are shipped in the repository. Populate via:
        //   database_hosts    => STAGING_PRODUCTION_DB_HOST_DENYLIST
        //   waba_ids          => STAGING_META_PRODUCTION_WABA_ID_DENYLIST
        //   phone_number_ids  => STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST
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
        'commit' => $packagedCommit ?? env('DEPLOYED_COMMIT'),
        'time' => env('DEPLOYED_AT'),
    ],
];
