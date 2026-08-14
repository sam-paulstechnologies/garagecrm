<?php

namespace App\Services\WhatsApp\History;

use App\Models\MessageLog;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppTrackingPreference;
use Illuminate\Support\Facades\DB;

class HistoryReview
{
    public function __construct(
        private readonly HistoryBatchCounters $counters,
        private readonly HistoryAudit $audit,
    ) {}

    public function decide(
        WhatsAppHistoryCandidate $candidate,
        User $actor,
        string $decision,
        bool $removeImportedHistory = false,
    ): WhatsAppHistoryCandidate {
        abort_unless(in_array($decision, ['track', 'dont_track'], true), 422);

        return DB::transaction(function () use ($candidate, $actor, $decision, $removeImportedHistory): WhatsAppHistoryCandidate {
            $candidate = WhatsAppHistoryCandidate::query()->with('batch')->lockForUpdate()->findOrFail($candidate->id);
            abort_unless((int) $candidate->company_id === (int) $actor->company_id, 403);
            $previous = $candidate->review_decision;
            WhatsAppTrackingPreference::query()->updateOrCreate([
                'company_id' => $candidate->company_id,
                'external_identity_hash' => $candidate->external_identity_hash,
            ], [
                'phone_e164' => $candidate->phone_e164,
                'decision' => $decision,
                'source_batch_id' => $candidate->whatsapp_history_import_batch_id,
                'source_candidate_id' => $candidate->id,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);
            $candidate->forceFill([
                'review_decision' => $decision,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'import_status' => $decision === 'track' && $candidate->staged_content_purged_at
                    ? 'requires_resync'
                    : $candidate->import_status,
            ])->save();

            if ($decision === 'dont_track') {
                if ($removeImportedHistory) {
                    MessageLog::query()->where('company_id', $candidate->company_id)
                        ->where('whatsapp_history_candidate_id', $candidate->id)
                        ->where('is_historical', true)
                        ->delete();
                }
                if (! $candidate->imported_client_id || $removeImportedHistory) {
                    $candidate->messages()->whereNull('purged_at')->get()->each(function ($message): void {
                        $message->forceFill([
                            'body' => null, 'metadata' => null, 'customer_identifier' => null, 'purged_at' => now(),
                        ])->save();
                    });
                    $candidate->forceFill(['staged_content_purged_at' => now()])->save();
                }
            }

            $this->audit->record($candidate->company_id, 'history.review_decision', $candidate->batch, $candidate, $actor->id, [
                'decision' => $decision,
                'source' => $previous,
                'remove_history' => $removeImportedHistory,
            ]);
            $this->counters->refresh($candidate->batch);

            return $candidate->fresh();
        }, 3);
    }
}
