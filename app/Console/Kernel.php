<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ComputeDailyAiMetrics;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Materialize yesterday’s metrics at 00:10 (or today if you prefer)
        $schedule->job(new ComputeDailyAiMetrics(now()->toDateString()))
            ->dailyAt('00:10')
            ->onOneServer()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
