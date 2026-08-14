<?php

use App\Commercial\Capabilities;
use App\Commercial\Plans;

$introductoryPaidCycles = 3;

$core = array_merge(Capabilities::CORE, [
    'whatsapp_connect', 'whatsapp_inbound', 'whatsapp_manual_reply', 'ai_observational',
    'whatsapp_history_review', 'whatsapp_history_intelligence', 'whatsapp_history_import',
]);

$service = array_merge($core, Capabilities::SERVICE, [
    'whatsapp_transactional', 'whatsapp_marketing',
]);

$growth = array_merge($service, Capabilities::OPERATIONS, Capabilities::GROWTH, [
    'campaign_attribution', 'ai_recommendations',
]);

$performance = array_merge($growth, Capabilities::PERFORMANCE, [
    'ai_priority_scoring',
]);

$aiPro = array_merge($performance, [
    'ai_action_execution', 'ai_automated_followup', 'ai_reactivation',
]);

return [
    'catalogue_version' => '2026-launch-v1',
    'effective_from' => '2026-08-01 00:00:00',
    'ai_metering' => [
        // A dedicated staging/production secret is preferred. APP_KEY is a safe
        // per-environment fallback and keeps raw customer identifiers out of the
        // commercial usage ledger.
        'hmac_key' => env('AI_METERING_HMAC_KEY') ?: env('APP_KEY'),
        'max_analysis_runs_per_customer_period' => (int) env('AI_MAX_ANALYSIS_RUNS_PER_CUSTOMER_PERIOD', 100),
    ],
    'plans' => [
        Plans::FREE => [
            'name' => 'Free', 'rank' => 0, 'description' => 'See what you are missing.',
            'list_amount' => 0, 'promotional_amount' => 0,
            'capabilities' => $core,
            'limits' => [
                'limit.users' => 1, 'limit.locations' => 1, 'limit.whatsapp_numbers' => 1,
                'limit.ai_monitored_customers' => 25, 'limit.whatsapp_history_contacts' => 25,
                'limit.campaigns_per_period' => 0, 'limit.campaign_recipients' => 0,
                'limit.active_workflows' => 0,
            ],
            'modes' => ['whatsapp_history_intelligence' => 'preview'],
        ],
        Plans::SERVICE => [
            'name' => 'Service', 'rank' => 10, 'description' => 'Turn garage WhatsApp into a booking system.',
            'list_amount' => 399, 'promotional_amount' => 199,
            'capabilities' => $service,
            'limits' => [
                'limit.users' => 3, 'limit.locations' => 1, 'limit.whatsapp_numbers' => 1,
                'limit.ai_monitored_customers' => 300, 'limit.whatsapp_history_contacts' => 300,
                'limit.campaigns_per_period' => 1, 'limit.campaign_recipients' => 100,
                'limit.active_workflows' => 0,
            ],
            'modes' => [
                'whatsapp_transactional' => 'approval_required',
                'whatsapp_marketing' => 'approval_required',
                'whatsapp_history_intelligence' => 'basic',
            ],
        ],
        Plans::GROWTH => [
            'name' => 'Growth', 'rank' => 20, 'description' => 'Run the garage customer and operational pipeline.',
            'list_amount' => 1999, 'promotional_amount' => 999,
            'capabilities' => $growth,
            'limits' => [
                'limit.users' => 10, 'limit.locations' => 2, 'limit.whatsapp_numbers' => 2,
                'limit.ai_monitored_customers' => 1000, 'limit.whatsapp_history_contacts' => 1000,
                'limit.campaigns_per_period' => 5, 'limit.campaign_recipients' => 500,
                'limit.active_workflows' => 5,
            ],
            'modes' => [
                'whatsapp_transactional' => 'approval_required',
                'whatsapp_marketing' => 'approval_required',
                'whatsapp_history_intelligence' => 'full',
            ],
        ],
        Plans::PERFORMANCE => [
            'name' => 'Performance', 'rank' => 30, 'description' => 'Know what actually generates business.',
            'list_amount' => 2999, 'promotional_amount' => 1499,
            'capabilities' => $performance,
            'limits' => [
                'limit.users' => 20, 'limit.locations' => 5, 'limit.whatsapp_numbers' => 3,
                'limit.ai_monitored_customers' => 2500, 'limit.whatsapp_history_contacts' => 2500,
                'limit.campaigns_per_period' => 20, 'limit.campaign_recipients' => 2000,
                'limit.active_workflows' => 20,
            ],
            'modes' => [
                'whatsapp_transactional' => 'approval_required',
                'whatsapp_marketing' => 'approval_required',
                'whatsapp_history_intelligence' => 'advanced',
            ],
        ],
        Plans::AI_PRO => [
            'name' => 'AI Pro', 'rank' => 40, 'description' => 'Let controlled AI run the next action.',
            'list_amount' => 3999, 'promotional_amount' => 1999,
            'capabilities' => $aiPro,
            // Null allowances are deliberately entitlement-enabled custom/fair-use
            // limits. They must not be presented as a permanent public promise.
            'limits' => [
                'limit.users' => null, 'limit.locations' => null, 'limit.whatsapp_numbers' => null,
                'limit.ai_monitored_customers' => null, 'limit.whatsapp_history_contacts' => null,
                'limit.campaigns_per_period' => null, 'limit.campaign_recipients' => null,
                'limit.active_workflows' => null,
            ],
            'modes' => [
                'ai_action_execution' => 'approval_required',
                'ai_automated_followup' => 'approval_required',
                'ai_reactivation' => 'approval_required',
                'whatsapp_ai_autonomous' => 'disabled',
                'whatsapp_marketing' => 'approval_required',
                'whatsapp_history_intelligence' => 'ai_assisted',
                'limit.users' => 'custom',
                'limit.locations' => 'custom',
                'limit.whatsapp_numbers' => 'custom',
                'limit.ai_monitored_customers' => 'fair_use',
                'limit.whatsapp_history_contacts' => 'fair_use',
                'limit.campaigns_per_period' => 'fair_use',
                'limit.campaign_recipients' => 'custom',
                'limit.active_workflows' => 'fair_use',
            ],
        ],
    ],
    'pricing' => [
        'currency' => 'AED',
        'interval' => 'month',
        // Introductory eligibility is consumed only by verified paid monthly
        // invoices. Calendar age, checkout returns and failed invoices do not count.
        'promotion_duration_months' => $introductoryPaidCycles,
        'promotion_duration_paid_cycles' => $introductoryPaidCycles,
        'renewal_behavior' => 'standard_after_promotion',
    ],
    'staging_assignments' => [
        'tenant-a@staging.sayaraforce.test' => Plans::AI_PRO,
        'tenant-b@staging.sayaraforce.test' => Plans::SERVICE,
    ],
];
