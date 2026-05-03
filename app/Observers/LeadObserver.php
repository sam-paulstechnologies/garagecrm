<?php

namespace App\Observers;

use App\Models\Client\Lead;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | LeadCreated is already dispatched from:
        | app/Models/Client/Lead.php
        |
        | Do NOT dispatch it here again.
        | Keeping this observer empty prevents duplicate LeadCreated events,
        | which were causing duplicate WhatsApp outbound messages.
        */
    }
}