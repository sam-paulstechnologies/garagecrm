<?php

namespace App\Commercial;

final class ProductEvents
{
    public const REGISTRATION_STARTED = 'registration_started';

    public const REGISTRATION_COMPLETED = 'registration_completed';

    public const WHATSAPP_ONBOARDING_STARTED = 'whatsapp_onboarding_started';

    public const WHATSAPP_CONNECTED = 'whatsapp_connected';

    public const FIRST_INBOUND = 'first_inbound';

    public const FIRST_LEAD = 'first_lead';

    public const FIRST_OPPORTUNITY = 'first_opportunity';

    public const FIRST_BOOKING = 'first_booking';

    public const AI_USAGE_50 = 'ai_usage_50';

    public const AI_USAGE_80 = 'ai_usage_80';

    public const AI_USAGE_100 = 'ai_usage_100';

    public const UPGRADE_VIEWED = 'upgrade_viewed';

    public const CHECKOUT_STARTED = 'checkout_started';

    public const SUBSCRIPTION_ACTIVATED = 'subscription_activated';

    public const SUBSCRIPTION_FAILED = 'subscription_failed';

    public const PLAN_UPGRADED = 'plan_upgraded';

    public const PLAN_DOWNGRADED = 'plan_downgraded';

    public const SUBSCRIPTION_CANCELLED = 'subscription_cancelled';

    public const ALL = [
        self::REGISTRATION_STARTED, self::REGISTRATION_COMPLETED,
        self::WHATSAPP_ONBOARDING_STARTED, self::WHATSAPP_CONNECTED,
        self::FIRST_INBOUND, self::FIRST_LEAD, self::FIRST_OPPORTUNITY, self::FIRST_BOOKING,
        self::AI_USAGE_50, self::AI_USAGE_80, self::AI_USAGE_100,
        self::UPGRADE_VIEWED, self::CHECKOUT_STARTED, self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_FAILED, self::PLAN_UPGRADED, self::PLAN_DOWNGRADED,
        self::SUBSCRIPTION_CANCELLED,
    ];

    public const PROPERTIES = [
        self::REGISTRATION_STARTED => [],
        self::REGISTRATION_COMPLETED => ['plan_code'],
        self::WHATSAPP_ONBOARDING_STARTED => ['connection_mode'],
        self::WHATSAPP_CONNECTED => ['connection_mode'],
        self::FIRST_INBOUND => ['channel'],
        self::FIRST_LEAD => ['source'],
        self::FIRST_OPPORTUNITY => ['source'],
        self::FIRST_BOOKING => ['source'],
        self::AI_USAGE_50 => ['threshold', 'used', 'limit'],
        self::AI_USAGE_80 => ['threshold', 'used', 'limit'],
        self::AI_USAGE_100 => ['threshold', 'used', 'limit'],
        self::UPGRADE_VIEWED => ['current_plan', 'target_plan', 'capability'],
        self::CHECKOUT_STARTED => ['current_plan', 'target_plan', 'provider'],
        self::SUBSCRIPTION_ACTIVATED => ['plan_code', 'provider'],
        self::SUBSCRIPTION_FAILED => ['plan_code', 'provider'],
        self::PLAN_UPGRADED => ['from_plan', 'to_plan', 'provider'],
        self::PLAN_DOWNGRADED => ['from_plan', 'to_plan', 'provider'],
        self::SUBSCRIPTION_CANCELLED => ['plan_code', 'provider'],
    ];
}
