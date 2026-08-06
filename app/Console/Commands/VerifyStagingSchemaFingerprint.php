<?php

namespace App\Console\Commands;

use App\Support\Staging\SchemaFingerprint;
use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;

class VerifyStagingSchemaFingerprint extends Command
{
    protected $signature = 'staging:schema-fingerprint {--verify : Fail unless the current schema matches the approved manifest fingerprint}';

    protected $description = 'Calculate or verify the staging MySQL structural fingerprint without reading application rows';

    public function handle(StagingSafety $safety, SchemaFingerprint $fingerprint): int
    {
        $safety->assertRuntimeIsolated();

        $hash = $fingerprint->hash();
        $this->line("Schema fingerprint: {$hash}");

        if (! $this->option('verify')) {
            return self::SUCCESS;
        }

        $manifestPath = database_path('schema/mysql-schema.manifest.json');
        if (! is_file($manifestPath)) {
            $this->error('Schema fingerprint verification failed: manifest is missing.');

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $expected = strtolower(trim((string) ($manifest['validation']['cycle_two_fingerprint'] ?? '')));
        $approved = (bool) ($manifest['staging_schema_baseline_approved'] ?? false);

        if (! $approved || $expected === '' || ! hash_equals($expected, $hash)) {
            $this->error('Schema fingerprint verification failed: the database differs from the approved canonical schema.');

            return self::FAILURE;
        }

        $this->info('Schema fingerprint matches the approved canonical schema.');

        return self::SUCCESS;
    }
}
