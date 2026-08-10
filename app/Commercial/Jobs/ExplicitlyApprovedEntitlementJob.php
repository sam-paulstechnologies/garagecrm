<?php

namespace App\Commercial\Jobs;

interface ExplicitlyApprovedEntitlementJob
{
    public function commercialActionApproved(): bool;
}
