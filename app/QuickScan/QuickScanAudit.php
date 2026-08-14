<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanEvent;
use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\User;

final class QuickScanAudit
{
    private const SAFE_CONTEXT_KEYS = [
        'status', 'previous_status', 'count', 'contacts_discovered', 'contacts_analysed',
        'deterministic_excluded', 'ai_calls', 'analysis_limit', 'connection_mode',
        'consent_policy_version', 'outcome', 'purge_due', 'reason_code', 'field',
        'idempotent', 'converted_company_id', 'duration_ms',
    ];

    public function record(
        QuickScanWorkspace $scan,
        string $event,
        ?User $actor = null,
        array $context = [],
    ): QuickScanEvent {
        return QuickScanEvent::query()->create([
            'quick_scan_workspace_id' => $scan->id,
            'actor_id' => $actor?->id,
            'event' => $event,
            'context' => array_intersect_key($context, array_flip(self::SAFE_CONTEXT_KEYS)) ?: null,
            'created_at' => now(),
        ]);
    }
}
