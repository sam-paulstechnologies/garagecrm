<?php

namespace App\Listeners\Lead;

use App\Events\LeadCreated;
use App\Services\Marketing\{TriggerEngine, CampaignDispatcher};
use App\Models\Marketing\Campaign;

class FireAutomationTriggers
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;
        $companyId = (int) $lead->company_id;

        $engine = app(TriggerEngine::class);
        $dispatcher = app(CampaignDispatcher::class);

        foreach ($engine->for('lead.created', $companyId) as $trigger) {
            if ($engine->passes($trigger->conditions, ['lead' => $lead->toArray()])) {
                $campaign = Campaign::find($trigger->campaign_id);
                if ($campaign && $campaign->status === 'active') {
                    $en = $dispatcher->enroll($companyId, $campaign, get_class($lead), $lead->id);
                    $dispatcher->tick($en); // execute first step immediately
                }
            }
        }
    }
}
