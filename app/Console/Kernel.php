<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ComputeDailyAiMetrics;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ✅ AI metrics
        $schedule->job(new ComputeDailyAiMetrics(now()->toDateString()))
            ->dailyAt('00:10')
            ->onOneServer()
            ->withoutOverlapping();

        // ✅ Journeys tick
        $schedule->command('journeys:tick')
            ->everyMinute()
            ->onOneServer()
            ->withoutOverlapping();

        // ✅ Booking reminder - 24 hours before booking
        $schedule->command('bookings:send-reminders --type=24h')
            ->dailyAt('10:00')
            ->onOneServer()
            ->withoutOverlapping();

        // ✅ Booking reminder - on the day of booking
        $schedule->command('bookings:send-reminders --type=day-of')
            ->dailyAt('08:00')
            ->onOneServer()
            ->withoutOverlapping();

        // 🔒 Isolated security/retention maintenance (M1/M2). Runs billing grace
        // enforcement + Quick Scan retention purge ONLY — no outbound messaging.
        // In staging the general scheduler stays disabled for outbound safety; a
        // dedicated security-maintenance webjob invokes `security:run-maintenance`
        // instead (see ops/azure/staging/webjobs/sayaraforce-staging-security-maintenance).
        $schedule->command('security:run-maintenance')
            ->hourly()
            ->onOneServer()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}