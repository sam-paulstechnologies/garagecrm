<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JourneyEnrollment;
use App\Jobs\ExecuteJourneyStep;

class RunJourneyEngine extends Command
{
    protected $signature = 'journeys:run';
    protected $description = 'Execute due journey steps';

    public function handle(): int
    {
        $now = now();

        JourneyEnrollment::where('status', 'active')
            ->whereNotNull('context->_wake_at')
            ->where('context->_wake_at', '<=', $now->toISOString())
            ->chunkById(100, function ($rows) {
                foreach ($rows as $enr) {
                    ExecuteJourneyStep::dispatch($enr->id);
                }
            });

        return Command::SUCCESS;
    }
}
