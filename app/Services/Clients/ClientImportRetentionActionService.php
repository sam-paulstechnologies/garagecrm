<?php

namespace App\Services\Clients;

use App\Models\Client\ClientImportBatch;
use App\Models\Client\ClientImportRow;
use App\Models\Client\RetentionAction;
use App\Models\Vehicle\VehicleServiceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientImportRetentionActionService
{
    private const IMMEDIATE_REVIEW_SEGMENTS = [
        'vip_follow_up',
        'inactive_customer_winback',
    ];

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
            'eligible_rows' => 0,
            'actions_to_create' => 0,
            'actions_created' => 0,
            'duplicate_existing_actions' => 0,
            'skipped_rows' => 0,
            'unclassified_skipped' => 0,
            'missing_client_skipped' => 0,
            'errors' => [],
            'records' => [],
        ];

        $rows = ClientImportRow::query()
            ->where('batch_id', $batch->id)
            ->where('company_id', $batch->company_id)
            ->where('review_status', 'applied')
            ->where('validation_status', '!=', 'invalid')
            ->orderBy('row_number')
            ->get();

        foreach ($rows as $row) {
            $payload = $row->normalized_payload ?? [];

            if (! $row->client_match_id) {
                $summary['missing_client_skipped']++;
                $summary['skipped_rows']++;
                $summary['records'][] = $this->record($row, 'skipped_missing_client', 'Applied row has no client_match_id.');
                continue;
            }

            $segmentCode = trim((string) ($row->suggested_segment_code ?? ''));

            if ($segmentCode === '' || $segmentCode === 'unclassified') {
                $summary['unclassified_skipped']++;
                $summary['skipped_rows']++;
                $summary['records'][] = $this->record($row, 'skipped_unclassified', 'Row has no actionable retention segment.');
                continue;
            }

            if (! $this->hasUsefulTimingOrImmediateSegment($row)) {
                $summary['skipped_rows']++;
                $summary['records'][] = $this->record($row, 'skipped_no_follow_up', 'Row has no follow-up date and no immediate-review segment.');
                continue;
            }

            $summary['eligible_rows']++;

            $existing = $this->existingActionForRow($row);

            if ($existing) {
                $summary['duplicate_existing_actions']++;
                $summary['records'][] = $this->record($row, 'duplicate_existing', 'Retention action already exists for this import row.', $existing->id);
                continue;
            }

            $history = $this->serviceHistoryForRow($row);

            if (! $apply) {
                $summary['actions_to_create']++;
                $summary['records'][] = $this->record($row, 'create_action', 'Would create pending retention action.', null, $history?->id);
                continue;
            }

            DB::transaction(function () use ($row, $payload, $batch, $history, $userId, &$summary) {
                $action = RetentionAction::create([
                    'company_id' => $batch->company_id,
                    'client_id' => $row->client_match_id,
                    'vehicle_id' => $row->vehicle_match_id ?: $history?->vehicle_id,
                    'vehicle_service_history_id' => $history?->id,
                    'source_type' => 'client_import_row',
                    'source_id' => $row->id,
                    'segment_code' => $row->suggested_segment_code,
                    'segment_label' => $row->suggested_segment_label,
                    'last_service_type' => $payload['last_service_type'] ?? $history?->service_type,
                    'last_service_date' => $this->dateOrNull($payload['last_service_date'] ?? $history?->service_date?->toDateString()),
                    'suggested_follow_up_date' => $row->suggested_next_action_date?->toDateString(),
                    'suggested_message' => $row->suggested_message,
                    'status' => 'pending_review',
                    'meta' => [
                        'created_from' => 'client_import',
                        'created_from_batch_id' => $batch->id,
                        'created_by' => $userId,
                        'duplicate_status' => $row->duplicate_status,
                        'row_warnings' => $row->warnings ?? [],
                        'insurance_expiry_date' => $payload['insurance_expiry_date'] ?? null,
                        'mulkia_expiry_date' => $payload['mulkia_expiry_date'] ?? null,
                    ],
                ]);

                $summary['actions_created']++;
                $summary['records'][] = $this->record($row, 'created_action', 'Pending retention action created.', $action->id, $history?->id);
            });
        }

        if ($apply) {
            $meta = $batch->meta ?? [];
            $meta['last_retention_action_summary'] = $summary;
            $meta['last_retention_actions_created_at'] = now()->toDateTimeString();
            $batch->update(['meta' => $meta]);
        }

        return $summary;
    }

    private function existingActionForRow(ClientImportRow $row): ?RetentionAction
    {
        return RetentionAction::query()
            ->where('company_id', $row->company_id)
            ->where('source_type', 'client_import_row')
            ->where('source_id', $row->id)
            ->first();
    }

    private function serviceHistoryForRow(ClientImportRow $row): ?VehicleServiceHistory
    {
        return VehicleServiceHistory::query()
            ->where('company_id', $row->company_id)
            ->where('source_type', 'client_import_row')
            ->where('source_id', $row->id)
            ->first();
    }

    private function hasUsefulTimingOrImmediateSegment(ClientImportRow $row): bool
    {
        if ($row->suggested_next_action_date) {
            return true;
        }

        return in_array((string) $row->suggested_segment_code, self::IMMEDIATE_REVIEW_SEGMENTS, true);
    }

    private function record(ClientImportRow $row, string $action, string $reason, ?int $retentionActionId = null, ?int $historyId = null): array
    {
        return [
            'row_number' => $row->row_number,
            'row_id' => $row->id,
            'action' => $action,
            'reason' => $reason,
            'retention_action_id' => $retentionActionId,
            'vehicle_service_history_id' => $historyId,
            'client_id' => $row->client_match_id,
            'vehicle_id' => $row->vehicle_match_id,
            'segment_code' => $row->suggested_segment_code,
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
}
