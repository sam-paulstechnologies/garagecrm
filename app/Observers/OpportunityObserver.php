<?php

namespace App\Observers;

use App\Models\Client\Opportunity;
use App\Events\OpportunityStatusUpdated;

class OpportunityObserver
{
    // Adjust the field name if your column is different (e.g., 'stage')
    protected const STATUS_FIELDS = ['status', 'stage', 'stage_name'];

    public function updated(Opportunity $opp): void
    {
        $statusField = collect(self::STATUS_FIELDS)->first(function ($f) use ($opp) {
            return $opp->isDirty($f) || $opp->wasChanged($f);
        });

        if ($statusField) {
            $new = $opp->getAttribute($statusField);

            if ($new) {
                event(new OpportunityStatusUpdated($opp, strtolower((string) $new)));
            }
        }
    }
}