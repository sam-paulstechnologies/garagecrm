<?php

namespace App\Services\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingOptOut;
use App\Models\PlatformMarketing\PlatformMarketingProspect;

class PlatformComplianceService
{
    public function __construct(private PlatformPhoneNormalizer $phoneNormalizer)
    {
    }

    public function canContact(PlatformMarketingProspect $prospect): bool
    {
        if (in_array($prospect->status, ['opted_out', 'blocked', 'invalid'], true)) {
            return false;
        }

        if ($prospect->consent_status !== 'opted_in') {
            return false;
        }

        return ! PlatformMarketingOptOut::query()
            ->where('normalized_phone', $prospect->normalized_phone)
            ->exists();
    }

    public function optOut(PlatformMarketingProspect $prospect, string $reason = 'STOP', string $source = 'whatsapp'): void
    {
        PlatformMarketingOptOut::query()->updateOrCreate(
            ['normalized_phone' => $prospect->normalized_phone],
            [
                'prospect_id' => $prospect->id,
                'reason' => $reason,
                'source' => $source,
                'opted_out_at' => now(),
            ]
        );

        $prospect->forceFill([
            'status' => 'opted_out',
            'consent_status' => 'opted_out',
        ])->save();
    }

    public function isStopMessage(string $body): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $body)));

        return in_array($normalized, ['stop', 'unsubscribe', 'opt out', 'opt-out', 'cancel'], true);
    }

    public function normalizedPhone(?string $number): string
    {
        return $this->phoneNormalizer->normalize($number);
    }
}
