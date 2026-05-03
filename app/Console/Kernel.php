<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ComputeDailyAiMetrics;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ✅ AI metrics (existing)
        $schedule->job(new ComputeDailyAiMetrics(now()->toDateString()))
            ->dailyAt('00:10')
            ->onOneServer()
            ->withoutOverlapping();

        // ✅ Journeys tick (Phase 8B - WAIT steps runner)
        $schedule->command('journeys:tick')
            ->everyMinute()
            ->onOneServer()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
