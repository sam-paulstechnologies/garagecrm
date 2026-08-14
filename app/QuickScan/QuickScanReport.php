<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanWorkspace;

final class QuickScanReport
{
    public function __construct(private readonly QuickScanAudit $audit) {}

    public function prepare(QuickScanWorkspace $scan): QuickScanWorkspace
    {
        $query = $scan->candidates()->whereNotNull('analysis_counted_at');
        $metrics = [
            'conversations_discovered' => $scan->candidates()->count(),
            'conversations_analysed' => (clone $query)->count(),
            'likely_customers' => (clone $query)->where('classification', 'likely_customer')->count(),
            'personal_staff_noise' => $scan->candidates()->where(function ($builder): void {
                $builder->where('deterministic_excluded', true)
                    ->orWhereIn('classification', ['possible_personal', 'possible_colleague']);
            })->count(),
            'potential_missed' => (clone $query)->where('potential_missed', true)->count(),
            'retention_customers' => (clone $query)->whereIn('retention_level', ['high', 'medium'])->count(),
            'high_retention' => (clone $query)->where('retention_level', 'high')->count(),
            'quotes_worth_reviewing' => (clone $query)->where('quote_unresolved', true)->count(),
            'service_related' => (clone $query)->where('service_related', true)->count(),
            'insufficient_evidence' => (clone $query)->where('classification', 'unknown')->count(),
            'locked_unanalysed' => $scan->candidates()->where('intelligence_status', 'locked')->count(),
            'evidence' => $this->evidence($scan),
        ];
        $scan->forceFill([
            'status' => 'report_ready',
            'report_metrics' => $metrics,
            'report_ready_at' => now(),
            'report_expires_at' => now()->addHours(max(1, (int) config('quick_scan.report_ttl_hours', 72))),
        ])->save();
        $this->audit->record($scan, 'quick_scan.report_ready', context: [
            'status' => 'report_ready', 'contacts_analysed' => $metrics['conversations_analysed'],
        ]);

        return $scan->fresh();
    }

    private function evidence(QuickScanWorkspace $scan): array
    {
        return $scan->candidates()
            ->whereNotNull('analysis_counted_at')
            ->where(function ($query): void {
                $query->where('retention_level', 'high')->orWhere('potential_missed', true);
            })
            ->orderByRaw("CASE WHEN retention_level = 'high' THEN 0 ELSE 1 END")
            ->limit(3)
            ->get()
            ->values()
            ->map(fn ($candidate, int $index): array => [
                'identity' => 'Customer '.chr(65 + $index).'•••',
                'category' => $candidate->retention_level === 'high' ? 'Retention' : 'Enquiry follow-up',
                'summary' => $candidate->retention_level === 'high'
                    ? (string) $candidate->retention_reason
                    : (string) $candidate->classification_reason,
            ])->all();
    }
}
