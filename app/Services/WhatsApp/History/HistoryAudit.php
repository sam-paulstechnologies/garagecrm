<?php

namespace App\Services\WhatsApp\History;

use App\Models\WhatsApp\WhatsAppHistoryAuditLog;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;

class HistoryAudit
{
    private const ALLOWED_CONTEXT = [
        'count', 'status', 'classification', 'retention_level', 'decision', 'source',
        'new_clients', 'matched_clients', 'messages_imported', 'failed_records',
        'limit', 'used', 'remaining', 'reason_code', 'remove_history',
    ];

    public function record(
        int $companyId,
        string $event,
        ?WhatsAppHistoryImportBatch $batch = null,
        ?WhatsAppHistoryCandidate $candidate = null,
        ?int $actorId = null,
        array $context = [],
    ): void {
        WhatsAppHistoryAuditLog::query()->create([
            'company_id' => $companyId,
            'batch_id' => $batch?->id,
            'candidate_id' => $candidate?->id,
            'actor_id' => $actorId,
            'event' => $event,
            'context' => array_intersect_key($context, array_flip(self::ALLOWED_CONTEXT)),
            'created_at' => now(),
        ]);
    }
}
