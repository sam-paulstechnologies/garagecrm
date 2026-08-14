<?php

namespace App\Services\WhatsApp\History;

use App\Commercial\EntitlementService;
use App\Models\WhatsApp\WhatsAppHistoryContactUsage;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use Illuminate\Support\Facades\DB;

class HistoryBatchCounters
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function refresh(WhatsAppHistoryImportBatch $batch): WhatsAppHistoryImportBatch
    {
        $query = $batch->candidates();
        $discovered = (clone $query)->where('unsupported', false)->count();
        $limit = $this->entitlements->limit($batch->company_id, 'limit.whatsapp_history_contacts');
        $used = WhatsAppHistoryContactUsage::query()->where('company_id', $batch->company_id)->count();
        $available = $limit === null ? $discovered : min($discovered, max(0, $limit - $used) +
            (clone $query)->whereIn('intelligence_status', ['analysed', 'deterministic'])->count());

        $batch->forceFill([
            'contacts_discovered' => $discovered,
            'contacts_eligible' => $available,
            'contacts_analysed' => (clone $query)->whereIn('intelligence_status', ['analysed', 'deterministic'])->count(),
            'pending_review' => (clone $query)->where('review_decision', 'pending')->count(),
            'track_selected' => (clone $query)->where('review_decision', 'track')->count(),
            'dont_track_selected' => (clone $query)->where('review_decision', 'dont_track')->count(),
            'new_clients' => (clone $query)->where('import_status', 'created_client')->count(),
            'matched_clients' => (clone $query)->where('import_status', 'matched_client')->count(),
            'messages_imported' => (int) DB::table('message_logs as ml')
                ->join('whatsapp_history_candidates as whc', 'whc.id', '=', 'ml.whatsapp_history_candidate_id')
                ->where('whc.whatsapp_history_import_batch_id', $batch->id)
                ->where('ml.is_historical', true)
                ->count(),
            'excluded_contacts' => (clone $query)->where(fn ($q) => $q->where('unsupported', true)->orWhere('review_decision', 'dont_track'))->count(),
            'failed_records' => (clone $query)->whereIn('intelligence_status', ['analysis_failed'])->count()
                + (clone $query)->where('import_status', 'failed')->count(),
        ])->save();

        return $batch->fresh();
    }
}
