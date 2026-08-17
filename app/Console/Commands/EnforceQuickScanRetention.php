<?php

namespace App\Console\Commands;

use App\QuickScan\QuickScanRetentionEnforcer;
use Illuminate\Console\Command;

class EnforceQuickScanRetention extends Command
{
    protected $signature = 'quick-scan:enforce-retention {--dry-run} {--batch=200}';

    protected $description = 'Physically purge Quick Scans past their permitted customer-data retention (due-but-lost + abandoned/expired). No outbound.';

    public function handle(QuickScanRetentionEnforcer $enforcer): int
    {
        $result = $enforcer->enforce((bool) $this->option('dry-run'), (int) $this->option('batch'));

        $this->info(sprintf(
            'Quick Scan retention: due=%d abandoned=%d purged=%d errors=%d%s',
            $result['due'],
            $result['abandoned'],
            $result['purged'],
            $result['errors'],
            $result['dry_run'] ? ' (dry-run)' : '',
        ));

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
