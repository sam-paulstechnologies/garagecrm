<?php

namespace App\Listeners\Lead;

use App\Events\LeadCreated;
use App\Models\AutomationLog;

class LeadWelcomeAndFollowup
{
    public function handle(LeadCreated $event): void
    {
        AutomationLog::create([
            'company_id'      => $event->lead->company_id,
            'entity_type'     => get_class($event->lead),
            'entity_id'       => $event->lead->id,
            'automation_type' => 'system',
            'action'          => 'LEAD_CREATED',
            'meta'            => [
                'source' => $event->lead->source,
            ],
        ]);
    }
}
