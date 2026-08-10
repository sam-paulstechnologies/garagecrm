<?php

namespace App\Console\Commands;

use App\Messaging\MetaUatReadiness;
use Illuminate\Console\Command;

class CheckMetaUatReadiness extends Command
{
    protected $signature = 'staging:meta-readiness {--json : Emit a secret-free machine-readable report}';

    protected $description = 'Check staging Meta/WhatsApp UAT configuration without displaying credentials or contacting Meta';

    public function handle(MetaUatReadiness $readiness): int
    {
        $report = $readiness->report();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info($report['engineering_ready']
            ? 'Meta/WhatsApp engineering readiness passed.'
            : 'Meta/WhatsApp engineering readiness is blocked by staging identity or safety configuration.');
        $this->line($report['live_uat_configuration_ready']
            ? 'The staging-only Meta configuration is complete. Live outbound remains unauthorized.'
            : 'Human-owned Meta configuration is incomplete. No live Meta action was attempted.');

        foreach ($report['checks'] as $name => $passed) {
            $this->line(sprintf('%s: %s', $name, $passed ? 'yes' : 'no'));
        }

        return self::SUCCESS;
    }
}
