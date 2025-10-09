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
        // Run Meta lead import every hour
        $schedule->command('leads:import-meta --company=1 --limit=50')
         ->everyFifteenMinutes()
         ->withoutOverlapping()
         ->sendOutputTo(storage_path('logs/meta_cron.log'));

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
