<?php

return [
    'enabled' => (bool) env('QUICK_SCAN_ENABLED', false),
    'analysis_contact_limit' => (int) env('QUICK_SCAN_ANALYSIS_CONTACT_LIMIT', 500),
    'link_ttl_hours' => (int) env('QUICK_SCAN_LINK_TTL_HOURS', 24),
    'report_ttl_hours' => (int) env('QUICK_SCAN_REPORT_TTL_HOURS', 72),
    'decline_purge_delay_hours' => (int) env('QUICK_SCAN_DECLINE_PURGE_DELAY_HOURS', 24),

    // Fable M2: canonical outer bound on retaining customer-level scan data.
    // Any non-converted scan holding customer data is physically purged once
    // this deadline passes, even if it stalled/failed before report_ready.
    'customer_data_retention_hours' => (int) env('QUICK_SCAN_CUSTOMER_DATA_RETENTION_HOURS', 96),

    // Additional grace applied to abandoned/report-expired scans before the
    // reconciliation sweep purges them.
    'abandoned_purge_grace_hours' => (int) env('QUICK_SCAN_ABANDONED_PURGE_GRACE_HOURS', 24),

    'consent_policy_version' => env('QUICK_SCAN_CONSENT_POLICY_VERSION', '2026-08-14'),
];
