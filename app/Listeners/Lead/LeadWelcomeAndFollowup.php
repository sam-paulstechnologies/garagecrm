<?php

namespace App\Listeners\Lead;

use App\Events\LeadCreated;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Support\Facades\Bus;

class LeadWelcomeAndFollowup
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;
        if (!$lead || !$lead->phone_norm) return;

        $companyId = (int) ($lead->company_id ?? 1);
        $to = $lead->phone_norm;
        $svc = new SendWhatsAppMessage();

        if (($lead->source ?? null) === 'meta') {
            // 1) New lead (meta)
            $svc->fireEvent($companyId, 'lead.created.meta', $to, [
                'name'    => $lead->name ?? 'there',
                'lead_id' => $lead->id,
            ]);

            // 2) 20-min follow-up
            Bus::dispatch(function() use ($svc, $companyId, $to, $lead) {
                $svc->fireEvent($companyId, 'lead.followup.20m', $to, [
                    'name'    => $lead->name ?? 'there',
                    'lead_id' => $lead->id,
                ]);
            })->delay(now()->addMinutes(20));
        }
    }
}
