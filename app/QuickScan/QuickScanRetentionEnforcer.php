<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanWorkspace;
use Illuminate\Support\Facades\Log;

/**
 * Fable remediation (M2): deterministic Quick Scan retention reconciliation.
 *
 * The delayed decline-purge job is not enough on its own: a lost job, or a scan
 * that reaches report_ready and is never accepted/declined, would retain raw
 * customer PII indefinitely. This sweep finds scans past their permitted
 * retention state and purges them physically via the existing QuickScanPurge
 * contract, while NEVER touching accepted (converted) scans or already-purged
 * ones. It performs no outbound messaging.
 */
final class QuickScanRetentionEnforcer
{
    public function __construct(
        private readonly QuickScanPurge $purge,
        private readonly QuickScanAudit $audit,
    ) {}

    /**
     * @return array{due:int, abandoned:int, purged:int, errors:int, dry_run:bool}
     */
    public function enforce(bool $dryRun = false, int $batch = 200): array
    {
        $batch = max(1, min(1000, $batch));
        $now = now();
        $graceHours = max(0, (int) config('quick_scan.abandoned_purge_grace_hours', 24));

        // Bucket 1: a purge was scheduled and is due, but the scan is not purged
        // (e.g. the delayed job was lost or exhausted its retries).
        $due = QuickScanWorkspace::query()
            ->where('status', '!=', 'purged')
            ->whereNotNull('purge_scheduled_at')
            ->where('purge_scheduled_at', '<=', $now)
            ->orderBy('id')->limit($batch)->get();

        // Bucket 2: abandoned/expired scans that still hold customer data, were
        // never accepted (converted to CRM), and never had a purge scheduled.
        $abandoned = QuickScanWorkspace::query()
            ->whereNull('purge_scheduled_at')
            ->whereNotIn('status', ['accepted', 'purged', 'purging'])
            ->whereNotNull('report_expires_at')
            ->where('report_expires_at', '<=', $now->copy()->subHours($graceHours))
            ->orderBy('id')->limit($batch)->get();

        $result = [
            'due' => $due->count(),
            'abandoned' => $abandoned->count(),
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

        foreach ($abandoned as $scan) {
            try {
                $scan->forceFill(['purge_scheduled_at' => now()])->save();
                $this->audit->record($scan, 'quick_scan.retention_reconciled', context: [
                    'reason_code' => 'abandoned_expired',
                ]);
                $this->purge->purge($scan, force: true);
                $result['purged']++;
            } catch (\Throwable $e) {
                $result['errors']++;
                Log::warning('Quick Scan retention purge (abandoned) failed.', ['scan_id' => $scan->id]);
            }
        }

        return $result;
    }
}
