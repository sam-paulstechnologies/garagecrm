<?php
// app/Console/Kernel.php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ⏱️ Reconcile Meta leads periodically (safety net for webhooks)
        $schedule->command('leads:import-meta')
            ->everyFifteenMinutes() // change to ->hourly() if you prefer
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
    }
}
