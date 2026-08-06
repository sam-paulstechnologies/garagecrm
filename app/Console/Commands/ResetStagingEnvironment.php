<?php

namespace App\Console\Commands;

use App\Support\Staging\StagingSafety;
use Database\Seeders\StagingSyntheticSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetStagingEnvironment extends Command
{
    protected $signature = 'staging:reset {--confirm= : Must be exactly RESET-STAGING}';

    protected $description = 'Wipe only the approved staging database, migrate, and seed synthetic test records.';

    public function handle(StagingSafety $safety): int
    {
        if (! hash_equals('RESET-STAGING', (string) $this->option('confirm'))) {
            $this->error('Reset refused: pass --confirm=RESET-STAGING.');

            return self::FAILURE;
        }

        try {
            $safety->assertRuntimeIsolated(destructive: true);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $exitCode = Artisan::call('migrate:fresh', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
        $this->output->write(Artisan::output());
        if ($exitCode !== self::SUCCESS) {
            return self::FAILURE;
        }

        $exitCode = Artisan::call('db:seed', [
            '--class' => StagingSyntheticSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]);
        $this->output->write(Artisan::output());

        return $exitCode === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}
