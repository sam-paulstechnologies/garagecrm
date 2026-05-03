<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyStepExecutor;

class JourneysTick extends Command
{
    protected $signature = 'journeys:tick {--company_id=} {--limit=200}';
    protected $description = 'Advance due journey enrollments (WAIT steps)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit') ?: 200;

        $q = JourneyEnrollment::query()
            ->where('status', 'active')
            ->whereNotNull('context');

        if ($cid = $this->option('company_id')) {
            $q->where('company_id', (int) $cid);
        }

        // JSON wake filter (works in MySQL 5.7+/8 with JSON)
        $due = $q->whereRaw("JSON_EXTRACT(context, '$._wake_at') IS NOT NULL")
            ->get()
            ->filter(function ($enr) {
                $wakeAt = data_get($enr->context ?? [], '_wake_at');
                if (!$wakeAt) return false;
                return now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($wakeAt));
            })
            ->take($limit);

        $count = 0;

        foreach ($due as $enr) {
            app(JourneyStepExecutor::class)->execute($enr);
            $count++;
        }

        $this->info("JourneysTick: advanced {$count} enrollment(s).");
        return self::SUCCESS;
    }
}
