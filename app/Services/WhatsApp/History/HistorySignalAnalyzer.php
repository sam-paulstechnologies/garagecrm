<?php

namespace App\Services\WhatsApp\History;

use Illuminate\Support\Collection;

final class HistorySignalAnalyzer
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

    private const BOOKING_TERMS = ['booked', 'appointment confirmed', 'see you at', 'booking confirmed'];

    /** @return array{text:string,fingerprint:string,messages:Collection} */
    public function conversation(Collection $messages): array
    {
        $bounded = $this->boundedMessages($messages);

        return [
            'text' => mb_strtolower($bounded->pluck('body')->filter()->implode("\n")),
            'fingerprint' => hash('sha256', $bounded->map(fn ($message) => implode('|', [
                $message->source_fingerprint,
                $message->message_timestamp?->timestamp,
                hash('sha256', (string) $message->body),
            ]))->implode(';')),
            'messages' => $bounded,
        ];
    }

    /**
     * @param  array{classification:string,classification_confidence:float,classification_reason:string,retention_level:string,retention_confidence:float,retention_reason:string}|null  $semantic
     * @return array{classification:string,classification_confidence:float,classification_reason:string,retention_level:string,retention_confidence:float,retention_reason:string,potential_missed:bool,quote_unresolved:bool,service_related:bool}
     */
    public function evaluate(string $text, int $daysSinceLastMessage, ?array $semantic = null): array
    {
        if ($semantic) {
            $classification = $semantic['classification'];
            $classificationConfidence = $semantic['classification_confidence'];
            $classificationReason = $semantic['classification_reason'];
            $retention = $semantic['retention_level'];
            $retentionConfidence = $semantic['retention_confidence'];
            $retentionReason = $semantic['retention_reason'];
        } else {
            [$classification, $classificationConfidence, $classificationReason] = $this->classify($text);
            [$retention, $retentionConfidence, $retentionReason] = $this->retention(
                $text,
                $classification,
                $daysSinceLastMessage,
            );
        }

        $serviceRelated = $this->matches($text, self::CUSTOMER_TERMS) > 0;
        $hasResolution = $this->matches($text, self::RESOLVED_TERMS) > 0
            || $this->matches($text, self::BOOKING_TERMS) > 0;
        $quoteUnresolved = $classification === 'likely_customer'
            && $this->matches($text, self::QUOTE_TERMS) > 0
            && ! $hasResolution
            && $daysSinceLastMessage >= 30;

        return [
            'classification' => $classification,
            'classification_confidence' => $classificationConfidence,
            'classification_reason' => $classificationReason,
            'retention_level' => $retention,
            'retention_confidence' => $retentionConfidence,
            'retention_reason' => $retentionReason,
            'potential_missed' => $classification === 'likely_customer'
                && $serviceRelated
                && ! $hasResolution
                && $daysSinceLastMessage >= 30,
            'quote_unresolved' => $quoteUnresolved,
            'service_related' => $serviceRelated,
        ];
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

    private function retention(string $text, string $classification, int $age): array
    {
        if ($classification !== 'likely_customer') {
            return ['none', .9, 'No supported customer-service relationship is available for retention assessment.'];
        }

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
