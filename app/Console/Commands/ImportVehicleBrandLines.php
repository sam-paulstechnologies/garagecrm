<?php

namespace App\Console\Commands;

use App\Services\Vehicles\VehicleMasterDataImporter;
use Illuminate\Console\Command;

class ImportVehicleBrandLines extends Command
{
    protected $signature = 'vehicles:import-brand-lines
        {--file=storage/app/private/vehicle_brand_lines.csv : CSV file path relative to project root or absolute path}
        {--dry-run : Preview changes without writing}
        {--apply : Write make/model/alias master data changes}
        {--limit=0 : Maximum CSV data rows to process, 0 means no limit}
        {--show-records : Show row-level actions}
        {--json : Output JSON payload}';

    protected $description = 'Import vehicle brand and model line master data from CSV with dry-run safety.';

    public function handle(VehicleMasterDataImporter $importer): int
    {
        $file = $this->resolveFilePath((string) $this->option('file'));
        $apply = (bool) $this->option('apply');
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        if ($apply && $dryRun) {
            $this->error('Choose either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        if (! $apply) {
            $dryRun = true;
        }

        $result = $apply
            ? $importer->apply($file, $limit)
            : $importer->preview($file, $limit);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return ((int) ($result['summary']['errors'] ?? 0)) > 0
                ? self::FAILURE
                : self::SUCCESS;
        }

        $mode = $apply ? 'apply' : 'dry-run';
        $this->info('Vehicle brand/line master data import');
        $this->line("Mode: {$mode}");
        $this->line("File: {$file}");
        $this->line('Limit: ' . ($limit > 0 ? $limit : 'none'));
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            collect($result['summary'] ?? [])
                ->reject(fn ($value, $key) => $key === 'mode')
                ->map(fn ($value, $key) => [str_replace('_', ' ', (string) $key), $value])
                ->values()
                ->all()
        );

        if ($this->option('show-records')) {
            $this->newLine();
            $this->table(
                ['Row', 'Brand', 'Line', 'Action', 'Reason'],
                collect($result['records'] ?? [])
                    ->map(fn ($record) => [
                        $record['row'] ?? '-',
                        $record['brand'] ?? '-',
                        $record['line'] ?? '-',
                        $record['action'] ?? '-',
                        $record['reason'] ?? '-',
                    ])
                    ->all()
            );
        }

        if ($dryRun) {
            $this->warn('Dry-run only. Re-run with --apply to write master data.');
        }

        return ((int) ($result['summary']['errors'] ?? 0)) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    protected function resolveFilePath(string $file): string
    {
        $file = trim($file);

        if ($file === '') {
            return base_path('storage/app/private/vehicle_brand_lines.csv');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $file) || str_starts_with($file, DIRECTORY_SEPARATOR)) {
            return $file;
        }

        return base_path($file);
    }
}
