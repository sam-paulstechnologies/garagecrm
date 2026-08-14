<?php

namespace App\QuickScan;

use App\Jobs\PurgeQuickScan as PurgeQuickScanJob;
use App\Models\QuickScan\QuickScanWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QuickScanPurge
{
    public function __construct(private readonly QuickScanAudit $audit) {}

    public function decline(QuickScanWorkspace $scan): QuickScanWorkspace
    {
        if (in_array($scan->status, ['declined', 'purging', 'purged'], true)) {
            return $scan;
        }
        $scan->forceFill([
            'status' => 'declined', 'outcome' => 'not_now', 'declined_at' => now(),
            'purge_scheduled_at' => now()->addHours(max(0, (int) config('quick_scan.decline_purge_delay_hours', 24))),
        ])->save();
        $this->audit->record($scan, 'quick_scan.declined', context: [
            'status' => 'declined', 'outcome' => 'not_now', 'purge_due' => true,
        ]);
        if (! app()->runningUnitTests()) {
            PurgeQuickScanJob::dispatch($scan->id)->delay($scan->purge_scheduled_at);
        }

        return $scan->fresh();
    }

    public function purge(QuickScanWorkspace $scan, bool $force = false): QuickScanWorkspace
    {
        if ($scan->status === 'purged') {
            return $scan;
        }
        if (! $force && (! $scan->purge_scheduled_at || $scan->purge_scheduled_at->isFuture())) {
            return $scan;
        }

        return DB::transaction(function () use ($scan): QuickScanWorkspace {
            $scan = QuickScanWorkspace::query()->lockForUpdate()->findOrFail($scan->id);
            $scan->forceFill(['status' => 'purging'])->save();
            $metrics = (array) $scan->report_metrics;
            $metrics['evidence'] = [];

            $scan->messages()->delete();
            $scan->candidates()->delete();
            $scan->providerEvents()->update([
                'payload' => null, 'provider_event_hash' => null, 'processed_at' => now(), 'updated_at' => now(),
            ]);
            $scan->providerSessions()->update([
                'access_token' => null, 'waba_id' => null, 'phone_number_id' => null,
                'business_id' => null, 'display_phone_number' => null, 'waba_hash' => null,
                'phone_number_hash' => null, 'state_encrypted' => encrypt(Str::random(64)),
                'status' => 'purged', 'updated_at' => now(),
            ]);

            $replacement = Str::random(64);
            $scan->forceFill([
                'status' => 'purged', 'purged_at' => now(), 'revoked_at' => now(),
                'claimed_whatsapp_number' => null, 'claimed_whatsapp_hash' => null,
                'staff_number_hashes' => null, 'failure_detail' => null,
                'access_token_hash' => hash('sha256', $replacement),
                'access_token_encrypted' => $replacement,
                'report_metrics' => $metrics,
            ])->save();
            $this->audit->record($scan, 'quick_scan.purged', context: [
                'status' => 'purged', 'contacts_discovered' => $metrics['conversations_discovered'] ?? 0,
                'contacts_analysed' => $metrics['conversations_analysed'] ?? 0,
            ]);

            return $scan->fresh();
        }, 3);
    }
}
