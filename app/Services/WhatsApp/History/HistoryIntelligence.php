<?php

namespace App\Services\WhatsApp\History;

use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Services\Ai\NlpService;
use Illuminate\Support\Collection;

class HistoryIntelligence
{
    private const CUSTOMER_TERMS = [
        'service', 'oil', 'brake', 'tyre', 'tire', 'battery', 'repair', 'diagnostic',
        'quotation', 'quote', 'appointment', 'booking', 'vehicle', 'car', 'workshop',
        'toyota', 'nissan', 'mercedes', 'bmw', 'audi', 'ford', 'honda', 'lexus',
    ];

    private const PERSONAL_TERMS = ['dinner', 'coming home', 'family', 'birthday', 'weekend', 'love you'];

    private const COLLEAGUE_TERMS = ['bay 1', 'bay 2', 'bay 3', 'call the customer', 'shift', 'mechanic', 'job card'];

    private const RESOLVED_TERMS = ['job completed', 'service completed', 'collected the car', 'vehicle collected', 'paid invoice'];

    private const QUOTE_TERMS = ['quotation', 'quote', 'price', 'how much', 'cost'];

    public function __construct(
        private readonly HistoryQuota $quota,
        private readonly HistoryStaffMatcher $staff,
        private readonly HistoryBatchCounters $counters,
        private readonly HistoryAudit $audit,
        private readonly NlpService $nlp,
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

        $messages = $this->boundedMessages($candidate->messages);
        $fingerprint = hash('sha256', $messages->map(fn ($message) => implode('|', [
            $message->source_fingerprint, $message->message_timestamp?->timestamp, hash('sha256', (string) $message->body),
        ]))->implode(';'));
        if ($candidate->analysis_fingerprint === $fingerprint && $candidate->intelligence_status === 'analysed') {
            return $candidate;
        }

        if (! $this->quota->claim($candidate)) {
            $this->counters->refresh($candidate->batch);

            return $candidate->fresh();
        }

        $candidate->forceFill(['intelligence_status' => 'analysing', 'analysis_requested_at' => now()])->save();
        $text = mb_strtolower($messages->pluck('body')->filter()->implode("\n"));
        $semantic = $this->nlp->analyzeHistoryConversation(
            $text,
            $candidate->last_message_at?->diffInDays(now()) ?? 0,
        );
        if ($semantic) {
            $classification = $semantic['classification'];
            $classificationConfidence = $semantic['classification_confidence'];
            $classificationReason = $semantic['classification_reason'];
            $retention = $semantic['retention_level'];
            $retentionConfidence = $semantic['retention_confidence'];
            $retentionReason = $semantic['retention_reason'];
        } else {
            [$classification, $classificationConfidence, $classificationReason] = $this->classify($text);
            [$retention, $retentionConfidence, $retentionReason] = $this->retention($candidate, $text, $classification);
        }

        $candidate->forceFill([
            'analysis_fingerprint' => $fingerprint,
            'intelligence_status' => 'analysed',
            'classification' => $classification,
            'classification_confidence' => $classificationConfidence,
            'classification_reason' => $classificationReason,
            'retention_level' => $retention,
            'retention_confidence' => $retentionConfidence,
            'retention_reason' => $retentionReason,
            'analysed_at' => now(),
        ])->save();
        $this->audit->record($candidate->company_id, 'history.analysis_completed', $candidate->batch, $candidate, context: [
            'classification' => $classification, 'retention_level' => $retention,
        ]);
        $this->counters->refresh($candidate->batch);

        return $candidate->fresh();
    }

    private function boundedMessages(Collection $messages): Collection
    {
        $limit = max(1, min(100, (int) config('messaging.history.analysis_message_limit', 40)));
        $characters = max(1000, min(30000, (int) config('messaging.history.analysis_character_limit', 12000)));
        $selected = $messages->filter(fn ($message) => ! $message->purged_at && filled($message->body))
            ->sortByDesc('message_timestamp')->take($limit)->sortBy('message_timestamp')->values();
        $used = 0;

        return $selected->takeUntil(function ($message) use (&$used, $characters): bool {
            $used += mb_strlen((string) $message->body);

            return $used > $characters;
        });
    }

    private function classify(string $text): array
    {
        $customer = $this->matches($text, self::CUSTOMER_TERMS);
        $personal = $this->matches($text, self::PERSONAL_TERMS);
        $colleague = $this->matches($text, self::COLLEAGUE_TERMS);
        if ($customer > 0) {
            return ['likely_customer', min(.98, .7 + ($customer * .05)), 'Conversation contains automotive service, quotation or appointment evidence.'];
        }
        if ($colleague > 0) {
            return ['possible_colleague', min(.9, .65 + ($colleague * .05)), 'Conversation contains workshop coordination language but no customer-service evidence.'];
        }
        if ($personal > 0) {
            return ['possible_personal', min(.9, .65 + ($personal * .05)), 'Conversation appears predominantly social and contains no visible vehicle or service discussion.'];
        }

        return ['unknown', .25, 'Not enough evidence is available to determine the relationship.'];
    }

    private function retention(WhatsAppHistoryCandidate $candidate, string $text, string $classification): array
    {
        if ($classification !== 'likely_customer') {
            return ['none', .9, 'No supported customer-service relationship is available for retention assessment.'];
        }

        $age = $candidate->last_message_at?->diffInDays(now()) ?? 0;
        $resolved = $this->matches($text, self::RESOLVED_TERMS) > 0;
        $quote = $this->matches($text, self::QUOTE_TERMS) > 0;
        if (! $resolved && $quote && $age >= 60) {
            return ['high', .9, 'An older quotation or pricing discussion has no visible booking or service-completion evidence.'];
        }
        if (! $resolved && $age >= 60) {
            return ['medium', .75, 'A previous workshop enquiry is visible, but its outcome and future follow-through are uncertain.'];
        }
        if ($resolved || $age < 60) {
            return ['low', .65, $resolved
                ? 'The conversation contains completion evidence and little current retention evidence.'
                : 'The customer interaction is recent and provides limited retention evidence.'];
        }

        return ['none', .4, 'There is insufficient evidence for a retention recommendation.'];
    }

    private function matches(string $text, array $terms): int
    {
        return collect($terms)->filter(fn (string $term): bool => str_contains($text, $term))->count();
    }
}
