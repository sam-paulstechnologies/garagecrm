<?php
// app/Console/Kernel.php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Meta Lead import (adjust company/limit via App Settings if needed)
        $schedule->command('leads:import-meta --company=1 --limit=50')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/meta_cron.log'));

        // Journeys: wake enrollments whose WAIT step is due
        $schedule->command('journeys:wake')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/journeys_wake.log'));

        // Campaigns: dispatch queued/scheduled WhatsApp sends
        $schedule->command('campaigns:dispatch --limit=200')
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/campaigns_dispatch.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // automatically loads all artisan commands in app/Console/Commands/
        $this->load(__DIR__.'/Commands');
    }
}
