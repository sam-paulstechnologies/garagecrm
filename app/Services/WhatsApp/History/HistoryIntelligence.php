<?php

namespace App\Services\WhatsApp\History;

use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Services\Ai\NlpService;

class HistoryIntelligence
{
    public function __construct(
        private readonly HistoryQuota $quota,
        private readonly HistoryStaffMatcher $staff,
        private readonly HistoryBatchCounters $counters,
        private readonly HistoryAudit $audit,
        private readonly NlpService $nlp,
        private readonly HistorySignalAnalyzer $signals,
    ) {}

    public function markDeterministicStaff(WhatsAppHistoryCandidate $candidate): bool
    {
        if (! $this->staff->matches($candidate->company_id, (string) $candidate->phone_e164)) {
            return false;
        }

        $candidate->forceFill([
            'intelligence_status' => 'deterministic',
            'classification' => 'possible_colleague',
            'classification_confidence' => 1,
            'classification_reason' => 'Number matches a SayaraForce team member.',
            'retention_level' => 'none',
            'retention_confidence' => 1,
            'retention_reason' => 'A team-member match is not treated as a customer retention opportunity.',
            'analysed_at' => now(),
        ])->save();

        return true;
    }

    public function analyse(WhatsAppHistoryCandidate $candidate): WhatsAppHistoryCandidate
    {
        $candidate = WhatsAppHistoryCandidate::query()->with(['messages', 'batch'])->findOrFail($candidate->id);
        if ($candidate->unsupported || $candidate->staged_content_purged_at) {
            return $candidate;
        }
        if ($this->markDeterministicStaff($candidate)) {
            $this->counters->refresh($candidate->batch);

            return $candidate->fresh();
        }

        $conversation = $this->signals->conversation($candidate->messages);
        $fingerprint = $conversation['fingerprint'];
        if ($candidate->analysis_fingerprint === $fingerprint && $candidate->intelligence_status === 'analysed') {
            return $candidate;
        }

        if (! $this->quota->claim($candidate)) {
            $this->counters->refresh($candidate->batch);

            return $candidate->fresh();
        }

        $candidate->forceFill(['intelligence_status' => 'analysing', 'analysis_requested_at' => now()])->save();
        $text = $conversation['text'];
        $semantic = $this->nlp->analyzeHistoryConversation(
            $text,
            $candidate->last_message_at?->diffInDays(now()) ?? 0,
        );
        $result = $this->signals->evaluate(
            $text,
            $candidate->last_message_at?->diffInDays(now()) ?? 0,
            $semantic,
        );

        $candidate->forceFill([
            'analysis_fingerprint' => $fingerprint,
            'intelligence_status' => 'analysed',
            'classification' => $result['classification'],
            'classification_confidence' => $result['classification_confidence'],
            'classification_reason' => $result['classification_reason'],
            'retention_level' => $result['retention_level'],
            'retention_confidence' => $result['retention_confidence'],
            'retention_reason' => $result['retention_reason'],
            'analysed_at' => now(),
        ])->save();
        $this->audit->record($candidate->company_id, 'history.analysis_completed', $candidate->batch, $candidate, context: [
            'classification' => $result['classification'], 'retention_level' => $result['retention_level'],
        ]);
        $this->counters->refresh($candidate->batch);

        return $candidate->fresh();
    }
}
