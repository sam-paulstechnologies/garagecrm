<?php

namespace App\Services\Opportunities;

use App\Models\Client\Opportunity;
use App\Enums\OpportunityStatus;

class OpportunityCompletionService
{
    /**
     * Determine if opportunity has all required data.
     */
    public function isComplete(Opportunity $opportunity): bool
    {
        $hasVehicle =
            ($opportunity->vehicle_make_id && $opportunity->vehicle_model_id)
            || ($opportunity->other_make && $opportunity->other_model);

        $hasService = !empty($opportunity->service_type);

        return $hasVehicle && $hasService;
    }

    /**
     * Update AI status based on completeness.
     */
    public function syncStatus(Opportunity $opportunity): void
    {
        if ($this->isComplete($opportunity)) {
            $opportunity->update([
                'ai_status' => OpportunityStatus::READY_FOR_BOOKING->value,
            ]);
        } else {
            $opportunity->update([
                'ai_status' => OpportunityStatus::DETAILS_IN_PROGRESS->value,
            ]);
        }

        $this->log($opportunity);
    }

    /**
     * Log AI opportunity status changes.
     */
    protected function log(Opportunity $opportunity): void
    {
        logger()->build([
            'driver' => 'single',
            'path'   => storage_path('logs/ai-opportunity.log'),
        ])->info('AI Opportunity Status Updated', [
            'opportunity_id' => $opportunity->id,
            'client_id'      => $opportunity->client_id,
            'ai_status'      => $opportunity->ai_status,
            'service_type'   => $opportunity->service_type,
            'vehicle'        => $opportunity->vehicle_label,
            'updated_at'     => now()->toDateTimeString(),
        ]);
    }
}
