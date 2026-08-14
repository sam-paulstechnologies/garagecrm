<?php

namespace App\Commercial;

final class Capabilities
{
    public const CORE = [
        'clients', 'vehicles', 'leads', 'opportunities', 'bookings', 'calendar', 'inbox',
    ];

    public const SERVICE = [
        'service_dashboard', 'transactional_booking_reminders', 'follow_up_reminders',
        'next_service_reminders',
    ];

    public const OPERATIONS = [
        'jobs', 'workshop', 'invoices', 'payments', 'team_management',
    ];

    public const GROWTH = [
        'management_dashboard', 'staff_performance', 'source_attribution', 'retention_insights',
    ];

    public const PERFORMANCE = [
        'campaign_attribution', 'campaign_intelligence', 'marketing_roi', 'advanced_reports',
        'advanced_retention_insights',
    ];

    public const AI = [
        'ai_observational', 'ai_recommendations', 'ai_priority_scoring', 'ai_action_execution',
        'ai_automated_followup', 'ai_reactivation',
    ];

    public const WHATSAPP = [
        'whatsapp_connect', 'whatsapp_inbound', 'whatsapp_manual_reply', 'whatsapp_transactional',
        'whatsapp_marketing', 'whatsapp_ai_autonomous', 'whatsapp_history_review',
        'whatsapp_history_intelligence', 'whatsapp_history_import',
    ];

    public const LIMITS = [
        'limit.users', 'limit.locations', 'limit.whatsapp_numbers', 'limit.ai_monitored_customers',
        'limit.whatsapp_history_contacts', 'limit.campaigns_per_period',
        'limit.campaign_recipients', 'limit.active_workflows',
    ];

    /** Platform capabilities can never be granted by a tenant plan. */
    public const PLATFORM = [
        'platform.garages.manage', 'platform.users.manage', 'platform.audit.view',
        'platform.messaging.diagnostics', 'platform.operations.manage',
    ];

    /** @return list<string> */
    public static function tenant(): array
    {
        return array_values(array_unique(array_merge(
            self::CORE,
            self::SERVICE,
            self::OPERATIONS,
            self::GROWTH,
            self::PERFORMANCE,
            self::AI,
            self::WHATSAPP,
            self::LIMITS,
        )));
    }

    public static function isTenant(string $capability): bool
    {
        return in_array($capability, self::tenant(), true);
    }

    public static function isPlatform(string $capability): bool
    {
        return in_array($capability, self::PLATFORM, true);
    }

    public static function isLimit(string $capability): bool
    {
        return in_array($capability, self::LIMITS, true);
    }
}
