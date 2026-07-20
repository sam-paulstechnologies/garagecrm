<?php

namespace App\Jobs\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Services\PlatformMarketing\CampaignSafetyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchPlatformCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId)
    {
        $this->onConnection('database')->onQueue('platform-marketing');
    }

    public function handle(CampaignSafetyService $safety): void
    {
        $campaign = PlatformMarketingCampaign::query()->findOrFail($this->campaignId);

        $safety->assertLaunchable($campaign);

        $campaign->recipients()
            ->where('status', 'queued')
            ->orderBy('id')
            ->chunkById(max(1, (int) $campaign->batch_size), function ($recipients) {
                foreach ($recipients as $recipient) {
                    SendPlatformWhatsAppMessage::dispatch($recipient->id);
                }
            });

        $campaign->forceFill([
            'status' => 'running',
            'started_at' => $campaign->started_at ?: now(),
        ])->save();
    }
}
