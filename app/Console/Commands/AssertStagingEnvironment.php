<?php

namespace App\Console\Commands;

use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;

class AssertStagingEnvironment extends Command
{
    protected $signature = 'staging:assert-safe {--destructive} {--require-schema}';

    protected $description = 'Fail unless the runtime is the isolated SayaraForce staging environment.';

    public function handle(StagingSafety $safety): int
    {
        try {
            $safety->assertRuntimeIsolated((bool) $this->option('destructive'));
            if ($this->option('require-schema') && ! config('staging.schema_baseline_approved')) {
                throw new \RuntimeException('Staging schema baseline has not been approved.');
            }
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Staging runtime identity and isolation checks passed.');

        return self::SUCCESS;
    }
}
