<?php

namespace App\Events;

use App\Models\Lead\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Lead $lead) {}
}
