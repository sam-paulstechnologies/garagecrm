<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\Journeys\JourneyEngine;

class StartJourneyForLead
{
    public function handle(LeadCreated $event): void
    {
        app(JourneyEngine::class)->enrollForTrigger(
            $event->lead->company_id,
            'lead.created',
            $event->lead,
            [
                'lead_id' => $event->lead->id,
                'phone'   => $event->lead->phone_norm,
                'name'    => $event->lead->name,
                'source'  => $event->lead->source,
            ]
        );
    }
}
