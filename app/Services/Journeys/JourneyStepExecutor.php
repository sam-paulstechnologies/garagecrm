<?php

namespace App\Services\Journeys;

use App\Models\JourneyEnrollment;
use App\Models\AutomationLog;

class JourneyStepExecutor
{
    public function execute(JourneyEnrollment $enrollment): void
    {
        if ($enrollment->status !== 'active') {
            return;
        }

        app(JourneyEngine::class)->advance($enrollment);

        AutomationLog::create([
            'company_id'      => $enrollment->company_id,
            'entity_type'     => $enrollment->enrollable_type,
            'entity_id'       => $enrollment->enrollable_id,
            'automation_type' => 'journey',
            'action'          => 'STEP_EXECUTED',
            'meta'            => [
                'journey_id' => $enrollment->journey_id,
                'step'       => $enrollment->current_step_position,
                'context'    => $enrollment->context,
            ],
        ]);
    }
}
