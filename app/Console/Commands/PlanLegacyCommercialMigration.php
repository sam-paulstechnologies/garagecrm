<?php

namespace App\Console\Commands;

use App\Commercial\LegacyCommercialMigrationPlanner;
use Illuminate\Console\Command;

class PlanLegacyCommercialMigration extends Command
{
    protected $signature = 'commercial:plan-legacy-migration
        {--json : Emit aggregate machine-readable output}
        {--include-company-ids : Include numeric IDs for a separately approved review workflow}';

    protected $description = 'Read-only commercial migration inventory; never changes plans, subscriptions, or entitlements';

    public function handle(LegacyCommercialMigrationPlanner $planner): int
    {
        $report = $planner->plan((bool) $this->option('include-company-ids'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->info('Legacy commercial migration inventory (read-only)');
        $this->line('Companies scanned: '.$report['companies_scanned']);
        foreach ($report['classifications'] as $classification => $count) {
            $this->line($classification.': '.$count);
        }
        $this->warn('Automatic changes: 0. Every legacy contract requires shadow evaluation and explicit approval.');

        return self::SUCCESS;
    }
}
