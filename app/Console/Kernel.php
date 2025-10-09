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
    // app/Console/Kernel.php
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
        {
            $schedule->command('leads:import-meta')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        }
}
