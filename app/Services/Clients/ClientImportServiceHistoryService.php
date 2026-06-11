<?php

namespace App\Services\Clients;

use App\Models\Client\ClientImportBatch;
use App\Models\Client\ClientImportRow;
use App\Models\Vehicle\VehicleServiceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientImportServiceHistoryService
{
    public function dryRun(ClientImportBatch $batch): array
    {
        return $this->process($batch, false);
    }

    public function createFromAppliedRows(ClientImportBatch $batch, ?int $userId = null): array
    {
        return $this->process($batch, true, $userId);
    }

    private function process(ClientImportBatch $batch, bool $apply, ?int $userId = null): array
    {
        $summary = [
            'mode' => $apply ? 'apply' : 'dry_run',
            'eligible_applied_rows' => 0,
            'histories_to_create' => 0,
            'histories_created' => 0,
            'duplicate_existing_histories' => 0,
            'skipped_rows' => 0,
            'errors' => [],
            'records' => [],
        ];

        $rows = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->where('review_status', 'applied')
            ->orderBy('row_number')
            ->get();

        $summary['eligible_applied_rows'] = $rows->count();

        foreach ($rows as $row) {
            $payload = $row->normalized_payload ?? [];

            if (! $row->client_match_id) {
                $summary['skipped_rows']++;
                $summary['records'][] = $this->record($row, 'skipped_missing_client', 'Applied row has no client_match_id.');
                continue;
            }

            if (! $this->hasUsefulServiceHistory($payload)) {
                $summary['skipped_rows']++;
                $summary['records'][] = $this->record($row, 'skipped_no_service_history', 'No last service/activity fields were present.');
                continue;
            }

            $existing = $this->existingHistoryForRow($row);

            if ($existing) {
                $summary['duplicate_existing_histories']++;
                $summary['records'][] = $this->record($row, 'duplicate_existing', 'Service history already exists for this import row.', $existing->id);
                continue;
            }

            if (! $apply) {
                $summary['histories_to_create']++;
                $summary['records'][] = $this->record($row, 'create_history', 'Would create imported service history.');
                continue;
            }

            DB::transaction(function () use ($row, $payload, $batch, $userId, &$summary) {
                $history = VehicleServiceHistory::create([
                    'company_id' => $batch->company_id,
                    'client_id' => $row->client_match_id,
                    'vehicle_id' => $row->vehicle_match_id,
                    'source_type' => 'client_import_row',
                    'source_id' => $row->id,
                    'service_type' => $payload['last_service_type'] ?? null,
                    'service_date' => $this->dateOrNull($payload['last_service_date'] ?? null),
                    'mileage' => $this->unsignedIntOrNull($payload['last_mileage'] ?? null),
                    'invoice_amount' => $this->decimalOrNull($payload['last_invoice_amount'] ?? null),
                    'currency' => 'AED',
                    'notes' => $payload['notes'] ?? null,
                    'raw_payload' => $row->raw_payload ?? $payload,
                    'meta' => [
                        'created_from' => 'client_import',
                        'created_from_batch_id' => $batch->id,
                        'created_by' => $userId,
                        'insurance_expiry_date' => $payload['insurance_expiry_date'] ?? null,
                        'mulkia_expiry_date' => $payload['mulkia_expiry_date'] ?? null,
                    ],
                ]);

                $summary['histories_created']++;
                $summary['records'][] = $this->record($row, 'created_history', 'Imported service history created.', $history->id);
            });
        }

        if ($apply) {
            $meta = $batch->meta ?? [];
            $meta['last_service_history_summary'] = $summary;
            $meta['last_service_history_created_at'] = now()->toDateTimeString();
            $batch->update(['meta' => $meta]);
        }

        return $summary;
    }

    private function existingHistoryForRow(ClientImportRow $row): ?VehicleServiceHistory
    {
        return VehicleServiceHistory::query()
            ->where('company_id', $row->company_id)
            ->where('source_type', 'client_import_row')
            ->where('source_id', $row->id)
            ->first();
    }

    private function hasUsefulServiceHistory(array $payload): bool
    {
        return filled($payload['last_service_date'] ?? null)
            || filled($payload['last_service_type'] ?? null)
            || filled($payload['last_mileage'] ?? null)
            || filled($payload['last_invoice_amount'] ?? null)
            || filled($payload['notes'] ?? null);
    }

    private function record(ClientImportRow $row, string $action, string $reason, ?int $historyId = null): array
    {
        return [
            'row_number' => $row->row_number,
            'row_id' => $row->id,
            'action' => $action,
            'reason' => $reason,
            'history_id' => $historyId,
            'client_id' => $row->client_match_id,
            'vehicle_id' => $row->vehicle_match_id,
        ];
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

    private function unsignedIntOrNull(mixed $value): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $number = (int) preg_replace('/[^\d]/', '', (string) $value);

        return $number > 0 ? $number : null;
    }

    private function decimalOrNull(mixed $value): ?float
    {
        if (! filled($value)) {
            return null;
        }

        $number = preg_replace('/[^\d.]/', '', (string) $value);

        return is_numeric($number) ? round((float) $number, 2) : null;
    }
}
