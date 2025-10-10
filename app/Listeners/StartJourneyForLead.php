<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\Journeys\JourneyEngine;

class StartJourneyForLead
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        app(JourneyEngine::class)->enrollForTrigger(
            companyId: $lead->company_id,
            trigger: 'lead.created',
            enrollable: $lead,
            context: [
                'lead_id' => $lead->id,
                'name'    => $lead->name ?? trim(($lead->first_name ?? '').' '.($lead->last_name ?? '')),
                'phone'   => $lead->whatsapp ?? $lead->phone ?? null,
                'source'  => $lead->source ?? null,
            ]
        );
    }
}
