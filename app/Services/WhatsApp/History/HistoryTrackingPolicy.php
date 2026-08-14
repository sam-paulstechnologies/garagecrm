<?php

namespace App\Services\WhatsApp\History;

use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppTrackingPreference;
use Illuminate\Support\Carbon;

class HistoryTrackingPolicy
{
    public function __construct(private readonly HistoryIdentity $identity) {}

    public function decision(Company $company, string $phone): string
    {
        $normalized = $this->identity->normalize($phone);
        if ($normalized === '') {
            return 'normal';
        }

        return (string) (WhatsAppTrackingPreference::query()
            ->where('company_id', $company->id)
            ->where('external_identity_hash', $this->identity->hash($normalized))
            ->value('decision') ?: 'normal');
    }

    public function stagePendingLiveMessage(
        Company $company,
        string $providerPhoneId,
        string $customerPhone,
        string $providerMessageId,
        string $direction,
        string $type,
        string $body,
        array $metadata,
        ?int $providerTimestamp,
    ): ?WhatsAppHistoryMessage {
        $hash = $this->identity->hash($customerPhone);
        $candidate = WhatsAppHistoryCandidate::query()
            ->where('company_id', $company->id)
            ->where('external_identity_hash', $hash)
            ->where('review_decision', 'pending')
            ->latest('id')
            ->first();
        if (! $candidate) {
            return null;
        }

        $timestamp = $providerTimestamp ? Carbon::createFromTimestampUTC($providerTimestamp) : now();
        $fingerprint = hash('sha256', implode('|', [
            $company->id, $providerPhoneId, $hash, $providerMessageId, $timestamp->timestamp,
            $direction, $type, hash('sha256', $body),
        ]));
        $message = WhatsAppHistoryMessage::query()->firstOrCreate(['source_fingerprint' => $fingerprint], [
            'company_id' => $company->id,
            'whatsapp_history_import_batch_id' => $candidate->whatsapp_history_import_batch_id,
            'whatsapp_history_candidate_id' => $candidate->id,
            'phone_number_id' => $providerPhoneId,
            'external_identity_hash' => $hash,
            'provider_message_id' => $providerMessageId ?: null,
            'direction' => $direction,
            'message_type' => $type,
            'source' => 'live_pending_review',
            'customer_identifier' => $this->identity->normalize($customerPhone),
            'body' => $body,
            'metadata' => array_intersect_key($metadata, array_flip(['media_id', 'mime_type'])),
            'message_timestamp' => $timestamp,
        ]);
        $candidate->forceFill([
            'message_count' => $candidate->messages()->whereNull('purged_at')->count(),
            'inbound_count' => $candidate->messages()->whereNull('purged_at')->where('direction', 'in')->count(),
            'outbound_count' => $candidate->messages()->whereNull('purged_at')->where('direction', 'out')->count(),
            'last_message_at' => $candidate->messages()->whereNull('purged_at')->max('message_timestamp'),
            'last_live_message_at' => $timestamp,
        ])->save();

        return $message;
    }
}
