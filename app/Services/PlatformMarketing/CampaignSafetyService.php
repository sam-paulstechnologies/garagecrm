<?php

namespace App\Services\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Models\PlatformMarketing\PlatformMarketingCampaignRecipient;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CampaignSafetyService
{
    public function __construct(private PlatformComplianceService $compliance)
    {
    }

    public function prepareRecipients(PlatformMarketingCampaign $campaign): array
    {
        if (blank($campaign->template_name)) {
            throw ValidationException::withMessages([
                'template_name' => 'An approved WhatsApp template is required before recipients can be queued.',
            ]);
        }

        $segment = $campaign->segment()->with('prospects')->first();

        if (! $segment) {
            throw ValidationException::withMessages([
                'segment_id' => 'Select a segment before preparing a campaign.',
            ]);
        }

        $summary = [
            'eligible' => 0,
            'suppressed' => 0,
            'duplicates' => 0,
        ];

        foreach ($segment->prospects as $prospect) {
            if (! $this->compliance->canContact($prospect)) {
                $summary['suppressed']++;
                continue;
            }

            $idempotencyKey = hash('sha256', $campaign->id.'|'.$prospect->normalized_phone);

            $recipient = PlatformMarketingCampaignRecipient::query()->firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'campaign_id' => $campaign->id,
                    'prospect_id' => $prospect->id,
                    'normalized_phone' => $prospect->normalized_phone,
                    'status' => 'queued',
                    'queued_at' => now(),
                ]
            );

            $recipient->wasRecentlyCreated ? $summary['eligible']++ : $summary['duplicates']++;
        }

        $campaign->forceFill([
            'safety_snapshot' => $summary,
        ])->save();

        return $summary;
    }

    public function assertLaunchable(PlatformMarketingCampaign $campaign): void
    {
        if (! in_array($campaign->status, ['approved', 'scheduled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Campaign must be approved or scheduled before launch.',
            ]);
        }

        if ($campaign->recipients()->where('status', 'queued')->doesntExist()) {
            throw ValidationException::withMessages([
                'recipients' => 'No eligible queued recipients are available.',
            ]);
        }
    }
}
