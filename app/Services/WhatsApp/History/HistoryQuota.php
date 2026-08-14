<?php

namespace App\Services\WhatsApp\History;

use App\Commercial\EntitlementService;
use App\Models\Commercial\Subscription;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryContactUsage;
use Illuminate\Support\Facades\DB;

class HistoryQuota
{
    public const ALLOWANCE = 'limit.whatsapp_history_contacts';

    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly HistoryAudit $audit,
    ) {}

    /** @return array{limit: ?int, used: int, remaining: ?int, mode: ?string} */
    public function summary(int $companyId): array
    {
        $decision = $this->entitlements->decide($companyId, self::ALLOWANCE);
        $used = WhatsAppHistoryContactUsage::query()->where('company_id', $companyId)->count();

        return [
            'limit' => $decision->limit,
            'used' => $used,
            'remaining' => $decision->limit === null ? null : max(0, $decision->limit - $used),
            'mode' => $decision->mode,
        ];
    }

    public function claim(WhatsAppHistoryCandidate $candidate): ?WhatsAppHistoryContactUsage
    {
        return DB::transaction(function () use ($candidate): ?WhatsAppHistoryContactUsage {
            $candidate = WhatsAppHistoryCandidate::query()->lockForUpdate()->findOrFail($candidate->id);
            $existing = WhatsAppHistoryContactUsage::query()
                ->where('company_id', $candidate->company_id)
                ->where('external_identity_hash', $candidate->external_identity_hash)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            // Serialise claims per subscription so concurrent bulk-analysis jobs
            // cannot exceed a finite allowance.
            Subscription::query()->where('company_id', $candidate->company_id)->lockForUpdate()->first();
            $decision = $this->entitlements->decide($candidate->company_id, self::ALLOWANCE);
            $used = WhatsAppHistoryContactUsage::query()->where('company_id', $candidate->company_id)->count();
            if (! $decision->allowed || ($decision->limit !== null && $used >= $decision->limit)) {
                $candidate->forceFill(['intelligence_status' => 'locked'])->save();
                $this->audit->record(
                    $candidate->company_id,
                    'history.quota_reached',
                    $candidate->batch,
                    $candidate,
                    context: ['limit' => $decision->limit, 'used' => $used, 'remaining' => 0],
                );

                return null;
            }

            return WhatsAppHistoryContactUsage::query()->create([
                'company_id' => $candidate->company_id,
                'external_identity_hash' => $candidate->external_identity_hash,
                'first_batch_id' => $candidate->whatsapp_history_import_batch_id,
                'first_candidate_id' => $candidate->id,
                'allowance_snapshot' => $decision->limit,
                'entitlement_source' => $decision->source,
                'analysed_at' => now(),
            ]);
        }, 3);
    }
}
