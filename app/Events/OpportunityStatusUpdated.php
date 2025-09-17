<?php

namespace App\Events;

use App\Models\Opportunity\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Opportunity $opportunity, public string $status) {}
}
