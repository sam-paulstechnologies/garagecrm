<?php

namespace App\Observers;

use App\Models\Lead\Lead;
use App\Events\LeadCreated;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        event(new LeadCreated($lead));
    }
}
