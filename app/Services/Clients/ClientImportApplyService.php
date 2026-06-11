<?php

namespace App\Services\Clients;

use App\Models\Client\Client;
use App\Models\Client\ClientImportBatch;
use App\Models\Client\ClientImportRow;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ClientImportApplyService
{
    public function dryRun(ClientImportBatch $batch): array
    {
        return $this->process($batch, false);
    }

    public function apply(ClientImportBatch $batch, ?int $appliedBy = null): array
    {
        return $this->process($batch, true, $appliedBy);
    }

    private function process(ClientImportBatch $batch, bool $apply, ?int $appliedBy = null): array
    {
        $summary = $this->emptySummary($apply ? 'apply' : 'dry_run');

        $rows = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->where('review_status', 'approved')
            ->where('validation_status', '!=', 'invalid')
            ->orderBy('row_number')
            ->get();

        $summary['rows_eligible'] = $rows->count();
        $summary['rows_skipped'] = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->where(function ($query) {
                $query->where('review_status', '!=', 'approved')
                    ->orWhere('validation_status', 'invalid');
            })
            ->count();

        foreach ($rows as $row) {
            if ($row->review_status === 'applied') {
                $summary['rows_skipped']++;
                $summary['records'][] = $this->record($row, 'skip', 'Row already applied.');
                continue;
            }

            $payload = $row->normalized_payload ?? [];

            if (! $this->hasMinimumClientData($payload)) {
                $summary['rows_skipped']++;
                $summary['errors'][] = "Row #{$row->row_number} missing required name and phone/WhatsApp.";
                $summary['records'][] = $this->record($row, 'skip', 'Missing required client data.');
                continue;
            }

            if ($apply) {
                DB::transaction(function () use ($row, $payload, $batch, $appliedBy, &$summary) {
                    $result = $this->applyRow($row, $payload, $batch, $appliedBy);
                    $this->mergeResult($summary, $result);
                });
            } else {
                $result = $this->planRow($row, $payload, $batch);
                $this->mergeResult($summary, $result);
            }
        }

        if ($apply) {
            $this->refreshBatchAfterApply($batch, $summary);
        }

        return $summary;
    }

    private function applyRow(ClientImportRow $row, array $payload, ClientImportBatch $batch, ?int $appliedBy): array
    {
        $result = $this->emptyRowResult();

        [$client, $clientAction] = $this->createOrReuseClient($payload, $batch->company_id);
        $result['client_action'] = $clientAction;
        $result[$this->summaryKeyForAppliedClientAction($clientAction)]++;

        [$vehicle, $vehicleAction] = $this->createOrReuseVehicle($payload, $batch->company_id, $client->id);
        $result['vehicle_action'] = $vehicleAction;
        $result[$this->summaryKeyForAppliedVehicleAction($vehicleAction)]++;

        $warnings = $row->warnings ?? [];
        $warnings[] = 'Applied to CRM clients/vehicles only on ' . now()->toDateTimeString() . '.';

        if ($vehicleAction === 'skipped_missing_data') {
            $warnings[] = 'Vehicle was not created because make/model data was missing.';
        }

        $row->update([
            'review_status' => 'applied',
            'client_match_id' => $client->id,
            'vehicle_match_id' => $vehicle?->id,
            'warnings' => array_values(array_unique($warnings)),
        ]);

        $result['rows_applied'] = 1;
        $result['records'] = [
            [
                'row_number' => $row->row_number,
                'client_id' => $client->id,
                'vehicle_id' => $vehicle?->id,
                'client_action' => $clientAction,
                'vehicle_action' => $vehicleAction,
                'applied_by' => $appliedBy,
            ],
        ];

        return $result;
    }

    private function planRow(ClientImportRow $row, array $payload, ClientImportBatch $batch): array
    {
        $client = $this->findMatchingClient($payload, $batch->company_id);
        $clientAction = $client ? ($this->clientWouldUpdate($client, $payload) ? 'updated' : 'reused') : 'created';

        $vehicleAction = 'skipped_missing_data';

        if ($this->hasVehicleData($payload)) {
            $make = $this->findOrCreateMake($payload['vehicle_make'] ?? null, false);
            $model = $make ? $this->findOrCreateModel($make, $payload['vehicle_model'] ?? null, false) : null;

            if ($make && $model && $client) {
                $vehicle = $this->findMatchingVehicle($payload, $batch->company_id, $client->id, $make->id, $model->id);
                $vehicleAction = $vehicle ? ($this->vehicleWouldUpdate($vehicle, $payload) ? 'updated' : 'reused') : 'created';
            } else {
                $vehicleAction = 'created';
            }
        }

        return array_merge($this->emptyRowResult(), [
            'records' => [
                [
                    'row_number' => $row->row_number,
                    'client_action' => $clientAction,
                    'vehicle_action' => $vehicleAction,
                    'client_id' => $client?->id,
                    'vehicle_id' => null,
                ],
            ],
            $this->summaryKeyForClientAction($clientAction) => 1,
            $this->summaryKeyForVehicleAction($vehicleAction) => 1,
        ]);
    }

    private function createOrReuseClient(array $payload, int $companyId): array
    {
        $client = $this->findMatchingClient($payload, $companyId);

        if (! $client) {
            return [
                Client::create([
                    'company_id' => $companyId,
                    'name' => $payload['name'],
                    'phone' => $payload['phone'] ?: ($payload['whatsapp'] ?? null),
                    'whatsapp' => $payload['whatsapp'] ?: ($payload['phone'] ?? null),
                    'email' => $payload['email'] ?? null,
                    'source' => $payload['source'] ?: 'client_import',
                    'status' => $payload['status'] ?: 'active',
                    'is_vip' => $this->isTruthy($payload['is_vip'] ?? null),
                    'preferred_channel' => $this->validPreferredChannel($payload['preferred_channel'] ?? null),
                    'notes' => $payload['notes'] ?? null,
                ]),
                'created',
            ];
        }

        $updates = [];

        foreach (['email', 'whatsapp', 'preferred_channel', 'source'] as $field) {
            if (blank($client->{$field}) && filled($payload[$field] ?? null)) {
                $updates[$field] = $field === 'preferred_channel'
                    ? $this->validPreferredChannel($payload[$field])
                    : $payload[$field];
            }
        }

        if (! $client->is_vip && $this->isTruthy($payload['is_vip'] ?? null)) {
            $updates['is_vip'] = true;
        }

        if (filled($payload['notes'] ?? null) && ! Str::contains((string) $client->notes, (string) $payload['notes'])) {
            $updates['notes'] = trim(implode("\n\n", array_filter([
                $client->notes,
                'Imported note: ' . $payload['notes'],
            ])));
        }

        if ($updates) {
            $client->update($updates);

            return [$client->fresh(), 'updated'];
        }

        return [$client, 'reused'];
    }

    private function createOrReuseVehicle(array $payload, int $companyId, int $clientId): array
    {
        if (! $this->hasVehicleData($payload)) {
            return [null, 'skipped_missing_data'];
        }

        $make = $this->findOrCreateMake($payload['vehicle_make'] ?? null, true);
        $model = $this->findOrCreateModel($make, $payload['vehicle_model'] ?? null, true);

        if (! $make || ! $model) {
            return [null, 'skipped_missing_data'];
        }

        $vehicle = $this->findMatchingVehicle($payload, $companyId, $clientId, $make->id, $model->id);

        if (! $vehicle) {
            return [
                Vehicle::create([
                    'company_id' => $companyId,
                    'client_id' => $clientId,
                    'make_id' => $make->id,
                    'model_id' => $model->id,
                    'plate_number' => $payload['plate_number'] ?? null,
                    'year' => $payload['vehicle_year'] ?? null,
                    'current_mileage' => $payload['last_mileage'] ?? null,
                    'insurance_expiry_date' => $this->dateOrNull($payload['insurance_expiry_date'] ?? null),
                    'registration_expiry_date' => $this->dateOrNull($payload['mulkia_expiry_date'] ?? null),
                ]),
                'created',
            ];
        }

        $updates = [];

        $map = [
            'plate_number' => 'plate_number',
            'vehicle_year' => 'year',
            'last_mileage' => 'current_mileage',
            'insurance_expiry_date' => 'insurance_expiry_date',
            'mulkia_expiry_date' => 'registration_expiry_date',
        ];

        foreach ($map as $payloadField => $vehicleField) {
            if (! Schema::hasColumn('vehicles', $vehicleField)) {
                continue;
            }

            if (blank($vehicle->{$vehicleField}) && filled($payload[$payloadField] ?? null)) {
                $updates[$vehicleField] = Str::endsWith($vehicleField, '_date')
                    ? $this->dateOrNull($payload[$payloadField])
                    : $payload[$payloadField];
            }
        }

        if ($updates) {
            $vehicle->update($updates);

            return [$vehicle->fresh(), 'updated'];
        }

        return [$vehicle, 'reused'];
    }

    private function findMatchingClient(array $payload, int $companyId): ?Client
    {
        $phone = Client::normalizePhone($payload['phone'] ?? null);
        $whatsapp = Client::normalizePhone($payload['whatsapp'] ?? null);
        $email = Client::normalizeEmail($payload['email'] ?? null);

        if ($phone || $whatsapp) {
            return Client::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($phone, $whatsapp) {
                    foreach (array_filter([$phone, $whatsapp]) as $number) {
                        $query->orWhere('phone_norm', $number)
                            ->orWhere('phone', $number)
                            ->orWhere('whatsapp', $number);
                    }
                })
                ->first();
        }

        if ($email) {
            return Client::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($email) {
                    $query->where('email_norm', $email)
                        ->orWhere('email', $email);
                })
                ->first();
        }

        return null;
    }

    private function findMatchingVehicle(array $payload, int $companyId, int $clientId, int $makeId, int $modelId): ?Vehicle
    {
        if (filled($payload['plate_number'] ?? null)) {
            $vehicle = Vehicle::query()
                ->where('company_id', $companyId)
                ->where('client_id', $clientId)
                ->where('plate_number', $payload['plate_number'])
                ->first();

            if ($vehicle) {
                return $vehicle;
            }
        }

        return Vehicle::query()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->where('make_id', $makeId)
            ->where('model_id', $modelId)
            ->when(filled($payload['vehicle_year'] ?? null), fn ($query) => $query->where('year', $payload['vehicle_year']))
            ->first();
    }

    private function findOrCreateMake(?string $name, bool $apply): ?VehicleMake
    {
        $name = $this->cleanName($name);

        if (! $name) {
            return null;
        }

        $make = VehicleMake::query()->where('name', $name)->first();

        if ($make || ! $apply) {
            return $make ?: new VehicleMake(['name' => $name]);
        }

        return VehicleMake::create(['name' => $name]);
    }

    private function findOrCreateModel(?VehicleMake $make, ?string $name, bool $apply): ?VehicleModel
    {
        $name = $this->cleanName($name);

        if (! $make || ! $name) {
            return null;
        }

        $model = VehicleModel::query()
            ->where('make_id', $make->id)
            ->where('name', $name)
            ->first();

        if ($model || ! $apply) {
            return $model ?: new VehicleModel(['make_id' => $make->id, 'name' => $name]);
        }

        return VehicleModel::create([
            'make_id' => $make->id,
            'name' => $name,
        ]);
    }

    private function hasMinimumClientData(array $payload): bool
    {
        return filled($payload['name'] ?? null)
            && (filled($payload['phone'] ?? null) || filled($payload['whatsapp'] ?? null));
    }

    private function hasVehicleData(array $payload): bool
    {
        return filled($payload['vehicle_make'] ?? null)
            && filled($payload['vehicle_model'] ?? null);
    }

    private function clientWouldUpdate(Client $client, array $payload): bool
    {
        if (! $client->is_vip && $this->isTruthy($payload['is_vip'] ?? null)) {
            return true;
        }

        foreach (['email', 'whatsapp', 'preferred_channel', 'source'] as $field) {
            if (blank($client->{$field}) && filled($payload[$field] ?? null)) {
                return true;
            }
        }

        return filled($payload['notes'] ?? null) && ! Str::contains((string) $client->notes, (string) $payload['notes']);
    }

    private function vehicleWouldUpdate(Vehicle $vehicle, array $payload): bool
    {
        $map = [
            'plate_number' => 'plate_number',
            'vehicle_year' => 'year',
            'last_mileage' => 'current_mileage',
            'insurance_expiry_date' => 'insurance_expiry_date',
            'mulkia_expiry_date' => 'registration_expiry_date',
        ];

        foreach ($map as $payloadField => $vehicleField) {
            if (blank($vehicle->{$vehicleField}) && filled($payload[$payloadField] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function refreshBatchAfterApply(ClientImportBatch $batch, array $summary): void
    {
        $approvedRemaining = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->where('review_status', 'approved')
            ->where('validation_status', '!=', 'invalid')
            ->count();

        $meta = $batch->meta ?? [];
        $meta['last_apply_summary'] = $summary;
        $meta['last_applied_at'] = now()->toDateTimeString();

        $batch->update([
            'status' => $approvedRemaining === 0 ? 'applied' : 'reviewed',
            'meta' => $meta,
        ]);
    }

    private function mergeResult(array &$summary, array $result): void
    {
        foreach ($result as $key => $value) {
            if ($key === 'records') {
                array_push($summary['records'], ...$value);
                continue;
            }

            if (is_int($value) && array_key_exists($key, $summary)) {
                $summary[$key] += $value;
            }
        }
    }

    private function emptySummary(string $mode): array
    {
        return [
            'mode' => $mode,
            'rows_eligible' => 0,
            'rows_applied' => 0,
            'rows_skipped' => 0,
            'clients_to_create' => 0,
            'clients_to_reuse' => 0,
            'clients_to_update' => 0,
            'clients_created' => 0,
            'clients_reused' => 0,
            'clients_updated' => 0,
            'vehicles_to_create' => 0,
            'vehicles_to_reuse' => 0,
            'vehicles_to_update' => 0,
            'vehicles_created' => 0,
            'vehicles_reused' => 0,
            'vehicles_updated' => 0,
            'vehicles_skipped_missing_data' => 0,
            'errors' => [],
            'records' => [],
        ];
    }

    private function emptyRowResult(): array
    {
        return [
            'rows_applied' => 0,
            'clients_to_create' => 0,
            'clients_to_reuse' => 0,
            'clients_to_update' => 0,
            'clients_created' => 0,
            'clients_reused' => 0,
            'clients_updated' => 0,
            'vehicles_to_create' => 0,
            'vehicles_to_reuse' => 0,
            'vehicles_to_update' => 0,
            'vehicles_created' => 0,
            'vehicles_reused' => 0,
            'vehicles_updated' => 0,
            'vehicles_skipped_missing_data' => 0,
            'records' => [],
        ];
    }

    private function summaryKeyForClientAction(string $action): string
    {
        return match ($action) {
            'created' => 'clients_to_create',
            'updated' => 'clients_to_update',
            default => 'clients_to_reuse',
        };
    }

    private function summaryKeyForVehicleAction(string $action): string
    {
        return match ($action) {
            'created' => 'vehicles_to_create',
            'updated' => 'vehicles_to_update',
            'reused' => 'vehicles_to_reuse',
            default => 'vehicles_skipped_missing_data',
        };
    }

    private function summaryKeyForAppliedClientAction(string $action): string
    {
        return match ($action) {
            'created' => 'clients_created',
            'updated' => 'clients_updated',
            default => 'clients_reused',
        };
    }

    private function summaryKeyForAppliedVehicleAction(string $action): string
    {
        return match ($action) {
            'created' => 'vehicles_created',
            'updated' => 'vehicles_updated',
            'reused' => 'vehicles_reused',
            default => 'vehicles_skipped_missing_data',
        };
    }

    private function record(ClientImportRow $row, string $action, string $reason): array
    {
        return [
            'row_number' => $row->row_number,
            'action' => $action,
            'reason' => $reason,
        ];
    }

    private function validPreferredChannel(?string $value): ?string
    {
        $value = Str::lower(trim((string) $value));

        return in_array($value, ['email', 'phone', 'whatsapp'], true) ? $value : null;
    }

    private function isTruthy(mixed $value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'yes', 'true', 'vip'], true);
    }

    private function cleanName(?string $value): ?string
    {
        $value = Str::of((string) $value)->squish()->toString();

        return $value === '' ? null : $value;
    }

    private function dateOrNull(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
