<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Fable remediation (M1/M2): the isolated security & retention maintenance entry
 * point. It runs ONLY safe maintenance — billing grace enforcement and Quick
 * Scan retention purge — and deliberately does NOT touch any outbound task
 * (journeys tick, booking reminders, campaigns, WhatsApp/SMS/email). This lets a
 * dedicated maintenance schedule/webjob run these controls in staging without
 * re-enabling the general (outbound-capable) scheduler.
 */
class RunSecurityMaintenance extends Command
{
    protected $signature = 'security:run-maintenance {--dry-run}';

    protected $description = 'Run isolated security/retention maintenance (billing grace + Quick Scan retention). No outbound messaging.';

    public function handle(): int
    {
        $options = $this->option('dry-run') ? ['--dry-run' => true] : [];

        $this->line('Running isolated security/retention maintenance (no outbound)...');

        $billing = $this->call('billing:enforce-grace-periods', $options);
        $retention = $this->call('quick-scan:enforce-retention', $options);

        return ($billing === self::SUCCESS && $retention === self::SUCCESS)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
