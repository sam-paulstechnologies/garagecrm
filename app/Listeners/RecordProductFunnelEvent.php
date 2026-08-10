<?php

namespace App\Listeners;

use App\Commercial\ProductFunnelMilestoneRecorder;
use App\Events\BookingStatusUpdated;
use App\Events\LeadCreated;
use App\Events\OpportunityStatusUpdated;

final class RecordProductFunnelEvent
{
    public function __construct(private readonly ProductFunnelMilestoneRecorder $milestones) {}

    public function handle(LeadCreated|OpportunityStatusUpdated|BookingStatusUpdated $event): void
    {
        if ($event instanceof LeadCreated) {
            $this->milestones->firstLead((int) $event->lead->company_id);

            return;
        }

        if ($event instanceof OpportunityStatusUpdated) {
            $this->milestones->firstOpportunity((int) $event->opportunity->company_id);

            return;
        }

        $this->milestones->firstBooking((int) $event->booking->company_id);
    }
}
