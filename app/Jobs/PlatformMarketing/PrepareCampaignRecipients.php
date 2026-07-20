<?php

namespace App\Jobs\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Services\PlatformMarketing\CampaignSafetyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareCampaignRecipients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId)
    {
        $this->onConnection('database')->onQueue('platform-marketing-high');
    }

    public function handle(CampaignSafetyService $safety): void
    {
        $safety->prepareRecipients(PlatformMarketingCampaign::query()->findOrFail($this->campaignId));
    }
}
