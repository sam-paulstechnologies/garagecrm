<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanWorkspace;
use Illuminate\Support\Facades\Log;

/**
 * Fable remediation (M2): deterministic Quick Scan retention reconciliation.
 *
 * The delayed decline-purge job is not enough on its own: a lost job, a scan
 * that reaches report_ready and is never accepted/declined, or a scan that
 * ingests customer history and then STALLS or FAILS before report_ready would
 * retain raw customer PII indefinitely. This sweep finds scans past their
 * permitted retention state and purges them physically via the existing
 * QuickScanPurge contract, while NEVER touching accepted (converted) scans or
 * already-purged ones. It performs no outbound messaging and is idempotent.
 *
 * Terminal/never-purge states: `accepted` (and any scan with a
 * converted_company_id) hand their data to the tenant's own quarantine and are
 * excluded from every bucket; `purged`/`purging` are already handled.
 */
final class QuickScanRetentionEnforcer
{
    /** Statuses whose customer data must never be swept by reconciliation. */
    private const PROTECTED_STATUSES = ['accepted', 'purged', 'purging'];

    public function __construct(
        private readonly QuickScanPurge $purge,
        private readonly QuickScanAudit $audit,
    ) {}

    /**
     * @return array{due:int, abandoned:int, stalled:int, purged:int, errors:int, dry_run:bool}
     */
    public function enforce(bool $dryRun = false, int $batch = 200): array
    {
        $batch = max(1, min(1000, $batch));
        $now = now();
        $graceHours = max(0, (int) config('quick_scan.abandoned_purge_grace_hours', 24));
        $retentionHours = max(1, (int) config('quick_scan.customer_data_retention_hours', 96));

        // Canonical customer-data deadline (derived, no schema change): a scan
        // must be purged once its retention anchor is older than the retention
        // window. The anchor is when customer history began flowing in
        // (history_sync_started_at, set at ingestion), falling back to created_at.
        $deadlineCutoff = $now->copy()->subHours($retentionHours);

        // Bucket 1: a purge was scheduled and is due, but the scan is not purged
        // (e.g. the delayed job was lost or exhausted its retries).
        $due = QuickScanWorkspace::query()
            ->where('status', '!=', 'purged')
            ->whereNull('converted_company_id')
            ->whereNotNull('purge_scheduled_at')
            ->where('purge_scheduled_at', '<=', $now)
            ->orderBy('id')->limit($batch)->get();

        // Bucket 2: report-ready scans whose report window expired, were never
        // accepted (converted to CRM), and never had a purge scheduled.
        $abandoned = QuickScanWorkspace::query()
            ->whereNull('purge_scheduled_at')
            ->whereNull('converted_company_id')
            ->whereNotIn('status', self::PROTECTED_STATUSES)
            ->whereNotNull('report_expires_at')
            ->where('report_expires_at', '<=', $now->copy()->subHours($graceHours))
            ->orderBy('id')->limit($batch)->get();

        // Bucket 3: stalled/failed/abandoned scans that ingested customer history
        // but never reached report_ready (report_expires_at NULL) and never had a
        // purge scheduled. Gated on actually holding customer data, and swept once
        // the derived retention deadline passes so PII can never be retained
        // forever. The anchor comparison keeps in-flight scans inside the window.
        $stalled = QuickScanWorkspace::query()
            ->whereNull('purge_scheduled_at')
            ->whereNull('converted_company_id')
            ->whereNull('report_expires_at')
            ->whereNotIn('status', self::PROTECTED_STATUSES)
            ->where(function ($query): void {
                $query->whereHas('messages')->orWhereHas('candidates');
            })
            ->where(function ($query) use ($deadlineCutoff): void {
                $query->where(function ($anchored) use ($deadlineCutoff): void {
                    $anchored->whereNotNull('history_sync_started_at')
                        ->where('history_sync_started_at', '<=', $deadlineCutoff);
                })->orWhere(function ($fallback) use ($deadlineCutoff): void {
                    $fallback->whereNull('history_sync_started_at')
                        ->where('created_at', '<=', $deadlineCutoff);
                });
            })
            ->orderBy('id')->limit($batch)->get();

        $result = [
            'due' => $due->count(),
            'abandoned' => $abandoned->count(),
            'stalled' => $stalled->count(),
            'purged' => 0,
            'errors' => 0,
            'dry_run' => $dryRun,
        ];

        if ($dryRun) {
            return $result;
        }

        foreach ($due as $scan) {
            try {
                $this->purge->purge($scan);
                $result['purged']++;
            } catch (\Throwable $e) {
                $result['errors']++;
                Log::warning('Quick Scan retention purge (due) failed.', ['scan_id' => $scan->id]);
            }
        }

        foreach ([
            'abandoned_expired' => $abandoned,
            'stalled_no_report' => $stalled,
        ] as $reasonCode => $scans) {
            foreach ($scans as $scan) {
                try {
                    $scan->forceFill(['purge_scheduled_at' => now()])->save();
                    $this->audit->record($scan, 'quick_scan.retention_reconciled', context: [
                        'reason_code' => $reasonCode,
                    ]);
                    $this->purge->purge($scan, force: true);
                    $result['purged']++;
                } catch (\Throwable $e) {
                    $result['errors']++;
                    Log::warning('Quick Scan retention purge ('.$reasonCode.') failed.', ['scan_id' => $scan->id]);
                }
            }
        }

        return $result;
    }
}
