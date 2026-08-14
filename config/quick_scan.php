<?php

return [
    'enabled' => (bool) env('QUICK_SCAN_ENABLED', false),
    'analysis_contact_limit' => (int) env('QUICK_SCAN_ANALYSIS_CONTACT_LIMIT', 500),
    'link_ttl_hours' => (int) env('QUICK_SCAN_LINK_TTL_HOURS', 24),
    'report_ttl_hours' => (int) env('QUICK_SCAN_REPORT_TTL_HOURS', 72),
    'decline_purge_delay_hours' => (int) env('QUICK_SCAN_DECLINE_PURGE_DELAY_HOURS', 24),
    'consent_policy_version' => env('QUICK_SCAN_CONSENT_POLICY_VERSION', '2026-08-14'),
];
