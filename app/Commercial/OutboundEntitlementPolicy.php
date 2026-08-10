<?php

namespace App\Commercial;

use App\Models\System\Company;
use Illuminate\Auth\Access\AuthorizationException;

class OutboundEntitlementPolicy
{
    public const MANUAL = 'manual';
    public const TRANSACTIONAL = 'transactional';
    public const MARKETING = 'marketing';
    public const AI_AUTONOMOUS = 'ai_autonomous';

    private const CAPABILITIES = [
        self::MANUAL => 'whatsapp_manual_reply',
        self::TRANSACTIONAL => 'whatsapp_transactional',
        self::MARKETING => 'whatsapp_marketing',
        self::AI_AUTONOMOUS => 'whatsapp_ai_autonomous',
    ];

    public function __construct(private readonly EntitlementService $entitlements) {}

    public function assertAllowed(Company|int $company, ?string $purpose): void
    {
        $capability = self::CAPABILITIES[$purpose ?? ''] ?? null;

        if (! $capability || ! $this->entitlements->can($company, $capability)) {
            throw new AuthorizationException('Outbound WhatsApp purpose is not entitled for this tenant.');
        }
    }

    public function purposeForEvent(string $eventKey): string
    {
        $event = strtolower(trim($eventKey));

        if (str_starts_with($event, 'campaign.') || str_starts_with($event, 'journey.')) {
            return self::MARKETING;
        }

        if (str_contains($event, 'reactivation') || str_contains($event, 'retention.')) {
            return self::MARKETING;
        }

        return self::TRANSACTIONAL;
    }
}
