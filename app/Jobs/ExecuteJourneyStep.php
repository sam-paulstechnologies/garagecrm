<?php

namespace App\Jobs;

use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyStepExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteJourneyStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $enrollmentId,
        public ?int $companyId = null
    ) {}

    public function handle(JourneyStepExecutor $executor): void
    {
        $query = JourneyEnrollment::query();

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        $enrollment = $query->find($this->enrollmentId);

        if (!$enrollment) {
            return;
        }

        $executor->execute($enrollment);
    }
}