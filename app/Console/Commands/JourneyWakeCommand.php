<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyEngine;
use Illuminate\Support\Carbon;

class JourneyWakeCommand extends Command
{
    protected $signature = 'journeys:wake';
    protected $description = 'Resume journey enrollments whose WAIT time has elapsed';

    public function handle(): int
    {
        $now = now();

        // Filter in PHP to keep JSON logic simple. For scale, move _wake_at to a column.
        $due = JourneyEnrollment::where('status','active')
            ->whereNotNull('context')
            ->get()
            ->filter(fn($e) => ($e->context['_wake_at'] ?? null) && $now->gte(Carbon::parse($e->context['_wake_at'])));

        $engine = app(JourneyEngine::class);

        foreach ($due as $enr) {
            $ctx = $enr->context; unset($ctx['_wake_at']);
            $enr->update(['context' => $ctx]);
            $engine->advance($enr);
        }

        $this->info('Processed: '.count($due));
        return self::SUCCESS;
    }
}
