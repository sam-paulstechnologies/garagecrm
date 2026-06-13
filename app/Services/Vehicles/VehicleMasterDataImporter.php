<?php

namespace App\Services\Vehicles;

use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Support\Facades\DB;

class VehicleMasterDataImporter
{
    protected array $requiredHeaders = [
        'brand',
        'line',
        'brand_aliases',
        'line_aliases',
        'region',
        'active',
    ];

    public function preview(string $filePath, int $limit = 0): array
    {
        return $this->process($filePath, false, $limit);
    }

    public function apply(string $filePath, int $limit = 0): array
    {
        return $this->process($filePath, true, $limit);
    }

    protected function process(string $filePath, bool $apply, int $limit = 0): array
    {
        $summary = $this->emptySummary($apply);
        $records = [];

        if (! is_file($filePath) || ! is_readable($filePath)) {
            $summary['errors']++;

            return [
                'summary' => $summary,
                'records' => [[
                    'row' => null,
                    'brand' => null,
                    'line' => null,
                    'action' => 'error',
                    'reason' => "CSV file is not readable: {$filePath}",
                ]],
            ];
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            $summary['errors']++;

            return [
                'summary' => $summary,
                'records' => [[
                    'row' => null,
                    'brand' => null,
                    'line' => null,
                    'action' => 'error',
                    'reason' => "CSV file could not be opened: {$filePath}",
                ]],
            ];
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            $summary['errors']++;

            return [
                'summary' => $summary,
                'records' => [[
                    'row' => null,
                    'brand' => null,
                    'line' => null,
                    'action' => 'error',
                    'reason' => 'CSV file is empty.',
                ]],
            ];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
        $missingHeaders = array_values(array_diff($this->requiredHeaders, $headers));
        if ($missingHeaders !== []) {
            fclose($handle);
            $summary['errors']++;

            return [
                'summary' => $summary,
                'records' => [[
                    'row' => 1,
                    'brand' => null,
                    'line' => null,
                    'action' => 'error',
                    'reason' => 'Missing required CSV headers: ' . implode(', ', $missingHeaders),
                ]],
            ];
        }

        $rowNumber = 1;
        $processed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            if ($this->isBlankRow($row)) {
                continue;
            }

            $processed++;
            $summary['rows_read']++;

            $data = $this->combineRow($headers, $row);
            $brand = $this->normalizeName($data['brand'] ?? '');
            $line = $this->normalizeName($data['line'] ?? '');
            $brandAliases = $this->parseAliases($data['brand_aliases'] ?? '');
            $lineAliases = $this->parseAliases($data['line_aliases'] ?? '');

            if ($brand === '' || $line === '') {
                $summary['rows_invalid']++;
                $summary['skipped_rows']++;
                $records[] = $this->record($rowNumber, $brand, $line, 'skip_invalid', 'Brand and line are required.');
                continue;
            }

            $summary['rows_valid']++;

            $make = VehicleMake::query()->where('name', $brand)->first();
            $makeWillBeCreated = ! $make;

            if ($makeWillBeCreated) {
                $summary['makes_to_create']++;
                $records[] = $this->record($rowNumber, $brand, $line, 'create_make', 'Canonical brand does not exist.');
            } else {
                $summary['makes_reused']++;
                $records[] = $this->record($rowNumber, $brand, $line, 'reuse_make', "Existing make ID {$make->id}.");
            }

            $brandDuplicateWarnings = $this->detectBrandDuplicates($brand, $brandAliases, $make?->id);
            foreach ($brandDuplicateWarnings as $warning) {
                $summary['possible_duplicate_brands']++;
                $records[] = $this->record($rowNumber, $brand, $line, 'possible_duplicate', $warning);
            }

            $existingMakeAliases = $make ? $this->aliasesFrom($make->alias) : [];
            $makeAliasesToAdd = $this->aliasesToAdd($existingMakeAliases, $brandAliases, $brand);
            if ($makeAliasesToAdd !== []) {
                $summary['aliases_to_add'] += count($makeAliasesToAdd);
                $records[] = $this->record($rowNumber, $brand, $line, 'add_alias', 'Brand aliases: ' . implode(', ', $makeAliasesToAdd));
            }

            if ($apply) {
                DB::transaction(function () use (
                    &$make,
                    $brand,
                    $makeWillBeCreated,
                    $makeAliasesToAdd,
                    $existingMakeAliases,
                    &$summary
                ): void {
                    if (! $make) {
                        $make = VehicleMake::query()->create([
                            'name' => $brand,
                            'alias' => array_values($makeAliasesToAdd),
                        ]);

                        $summary['makes_created']++;
                        $summary['aliases_added'] += count($makeAliasesToAdd);

                        return;
                    }

                    if ($makeAliasesToAdd !== []) {
                        $make->alias = $this->mergeAliases($existingMakeAliases, $makeAliasesToAdd);
                        $make->save();
                        $summary['aliases_added'] += count($makeAliasesToAdd);
                    }
                });
            }

            $model = $make
                ? VehicleModel::query()->where('make_id', $make->id)->where('name', $line)->first()
                : null;

            if (! $make && ! $apply) {
                $model = null;
            }

            $modelWillBeCreated = ! $model;
            if ($modelWillBeCreated) {
                $summary['models_to_create']++;
                $records[] = $this->record($rowNumber, $brand, $line, 'create_model', 'Canonical line does not exist for this brand.');
            } else {
                $summary['models_reused']++;
                $records[] = $this->record($rowNumber, $brand, $line, 'reuse_model', "Existing model ID {$model->id}.");
            }

            if ($make) {
                $modelDuplicateWarnings = $this->detectModelDuplicates($make->id, $line, $lineAliases, $model?->id);
                foreach ($modelDuplicateWarnings as $warning) {
                    $summary['possible_duplicate_models']++;
                    $records[] = $this->record($rowNumber, $brand, $line, 'possible_duplicate', $warning);
                }
            }

            $existingModelAliases = $model ? $this->aliasesFrom($model->alias) : [];
            $modelAliasesToAdd = $this->aliasesToAdd($existingModelAliases, $lineAliases, $line);
            if ($modelAliasesToAdd !== []) {
                $summary['aliases_to_add'] += count($modelAliasesToAdd);
                $records[] = $this->record($rowNumber, $brand, $line, 'add_alias', 'Line aliases: ' . implode(', ', $modelAliasesToAdd));
            }

            if ($apply) {
                DB::transaction(function () use (
                    &$model,
                    $make,
                    $line,
                    $modelAliasesToAdd,
                    $existingModelAliases,
                    &$summary
                ): void {
                    if (! $make) {
                        return;
                    }

                    if (! $model) {
                        $model = VehicleModel::query()->create([
                            'make_id' => $make->id,
                            'name' => $line,
                            'alias' => array_values($modelAliasesToAdd),
                        ]);

                        $summary['models_created']++;
                        $summary['aliases_added'] += count($modelAliasesToAdd);

                        return;
                    }

                    if ($modelAliasesToAdd !== []) {
                        $model->alias = $this->mergeAliases($existingModelAliases, $modelAliasesToAdd);
                        $model->save();
                        $summary['aliases_added'] += count($modelAliasesToAdd);
                    }
                });
            }
        }

        fclose($handle);

        return [
            'summary' => $summary,
            'records' => $records,
        ];
    }

    protected function emptySummary(bool $apply): array
    {
        return [
            'mode' => $apply ? 'apply' : 'dry-run',
            'rows_read' => 0,
            'rows_valid' => 0,
            'rows_invalid' => 0,
            'makes_to_create' => 0,
            'makes_created' => 0,
            'models_to_create' => 0,
            'models_created' => 0,
            'makes_reused' => 0,
            'models_reused' => 0,
            'aliases_to_add' => 0,
            'aliases_added' => 0,
            'possible_duplicate_brands' => 0,
            'possible_duplicate_models' => 0,
            'skipped_rows' => 0,
            'errors' => 0,
        ];
    }

    protected function combineRow(array $headers, array $row): array
    {
        $row = array_pad($row, count($headers), '');

        return array_combine($headers, array_slice($row, 0, count($headers))) ?: [];
    }

    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeHeader(string $header): string
    {
        return strtolower(trim($header));
    }

    protected function normalizeName(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $value;
    }

    protected function parseAliases(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($alias) => $this->normalizeName((string) $alias))
            ->filter()
            ->unique(fn ($alias) => mb_strtolower($alias))
            ->values()
            ->all();
    }

    protected function aliasesFrom(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($alias) => $this->normalizeName((string) $alias))->filter()->values()->all();
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->aliasesFrom($decoded);
            }
        }

        return [];
    }

    protected function aliasesToAdd(array $existingAliases, array $incomingAliases, string $canonicalName): array
    {
        $existingKeys = collect([...$existingAliases, $canonicalName])
            ->map(fn ($alias) => mb_strtolower($this->normalizeName((string) $alias)))
            ->filter()
            ->all();

        return collect($incomingAliases)
            ->reject(fn ($alias) => in_array(mb_strtolower($alias), $existingKeys, true))
            ->unique(fn ($alias) => mb_strtolower($alias))
            ->values()
            ->all();
    }

    protected function mergeAliases(array $existingAliases, array $aliasesToAdd): array
    {
        return collect([...$existingAliases, ...$aliasesToAdd])
            ->map(fn ($alias) => $this->normalizeName((string) $alias))
            ->filter()
            ->unique(fn ($alias) => mb_strtolower($alias))
            ->values()
            ->all();
    }

    protected function detectBrandDuplicates(string $brand, array $aliases, ?int $currentMakeId): array
    {
        $warnings = [];
        $names = collect([$brand, ...$aliases])
            ->map(fn ($value) => mb_strtolower($this->normalizeName((string) $value)))
            ->filter()
            ->unique()
            ->values();

        $matches = VehicleMake::query()
            ->when($currentMakeId, fn ($query) => $query->where('id', '!=', $currentMakeId))
            ->get(['id', 'name', 'alias'])
            ->filter(function (VehicleMake $make) use ($names) {
                $searchNames = collect([$make->name, ...$this->aliasesFrom($make->alias)])
                    ->map(fn ($value) => mb_strtolower($this->normalizeName((string) $value)))
                    ->filter();

                return $searchNames->intersect($names)->isNotEmpty()
                    || $this->compactName($make->name) === $this->compactName($names->first() ?? '');
            });

        foreach ($matches as $match) {
            $warnings[] = "Possible duplicate brand with existing make ID {$match->id}: {$match->name}.";
        }

        return $warnings;
    }

    protected function detectModelDuplicates(int $makeId, string $line, array $aliases, ?int $currentModelId): array
    {
        $warnings = [];
        $names = collect([$line, ...$aliases])
            ->map(fn ($value) => mb_strtolower($this->normalizeName((string) $value)))
            ->filter()
            ->unique()
            ->values();

        $matches = VehicleModel::query()
            ->where('make_id', $makeId)
            ->when($currentModelId, fn ($query) => $query->where('id', '!=', $currentModelId))
            ->get(['id', 'name', 'alias'])
            ->filter(function (VehicleModel $model) use ($names) {
                $searchNames = collect([$model->name, ...$this->aliasesFrom($model->alias)])
                    ->map(fn ($value) => mb_strtolower($this->normalizeName((string) $value)))
                    ->filter();

                return $searchNames->intersect($names)->isNotEmpty()
                    || $this->compactName($model->name) === $this->compactName($names->first() ?? '');
            });

        foreach ($matches as $match) {
            $warnings[] = "Possible duplicate line with existing model ID {$match->id}: {$match->name}.";
        }

        return $warnings;
    }

    protected function compactName(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($value)) ?? '';
    }

    protected function record(?int $row, ?string $brand, ?string $line, string $action, string $reason): array
    {
        return [
            'row' => $row,
            'brand' => $brand,
            'line' => $line,
            'action' => $action,
            'reason' => $reason,
        ];
    }
}
