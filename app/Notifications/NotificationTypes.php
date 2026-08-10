<?php

namespace App\Notifications;

final class NotificationTypes
{
    public const ALL = [
        'new_enquiry',
        'high_intent_lead',
        'follow_up_due',
        'booking_upcoming',
        'missed_follow_up',
        'next_service_due',
        'billing_attention',
    ];

    public static function isKnown(string $type): bool
    {
        return in_array($type, self::ALL, true);
    }
}
