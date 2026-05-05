<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JourneyEnrollment;
use App\Jobs\ExecuteJourneyStep;

class RunJourneyEngine extends Command
{
    protected $signature = 'journeys:run {--company_id=}';
    protected $description = 'Execute due journey steps';

    public function handle(): int
    {
        $now = now();
        $companyId = $this->option('company_id');

        $query = JourneyEnrollment::where('status', 'active')
            ->whereNotNull('context->_wake_at')
            ->where('context->_wake_at', '<=', $now->toISOString());

        if ($companyId) {
            $query->where('company_id', (int) $companyId);
        }

        $query->chunkById(100, function ($rows) {
            foreach ($rows as $enr) {
                ExecuteJourneyStep::dispatch($enr->id, $enr->company_id);
            }
        });

        return Command::SUCCESS;
    }
}