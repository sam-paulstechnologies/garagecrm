<?php

namespace App\Services\WhatsApp\History;

use App\Messaging\Models\MessagingPhoneNumber;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppSyncedContact;
use App\Models\WhatsApp\WhatsAppTrackingPreference;
use Illuminate\Support\Carbon;

class HistoryIngestion
{
    public function __construct(
        private readonly HistoryIdentity $identity,
        private readonly HistoryIntelligence $intelligence,
        private readonly HistoryBatchCounters $counters,
        private readonly HistoryAudit $audit,
    ) {}

    public function ingest(Company $company, array $payload, bool $completed): WhatsAppHistoryImportBatch
    {
        $providerPhoneId = (string) ($payload['metadata']['phone_number_id'] ?? $company->meta_phone_number_id ?? '');
        $phone = MessagingPhoneNumber::query()
            ->where('provider', 'meta_whatsapp')
            ->where('phone_number_id', $providerPhoneId)
            ->whereHas('connection', fn ($query) => $query->where('company_id', $company->id))
            ->with('connection')
            ->first();
        $connectionId = $phone?->messaging_connection_id;
        $scope = $this->identity->connectionScope((int) $company->id, $connectionId, $providerPhoneId);
        $batch = WhatsAppHistoryImportBatch::query()
            ->where('company_id', $company->id)
            ->where('connection_scope_hash', $scope)
            ->whereIn('status', ['pending_sync', 'syncing', 'discovered', 'awaiting_review'])
            ->latest('id')
            ->first();
        $batch ??= WhatsAppHistoryImportBatch::query()->create([
            'company_id' => $company->id,
            'messaging_connection_id' => $connectionId,
            'connection_scope_hash' => $scope,
            'status' => 'syncing',
            'sync_started_at' => now(),
            'review_expires_at' => now()->addDays(max(1, (int) config('messaging.history.review_retention_days', 30))),
        ]);
        $batch->forceFill(['status' => 'syncing', 'last_error_code' => null])->save();

        $history = (array) ($payload['history'] ?? []);
        $historyBatches = $history === [] ? [$payload] : (array_is_list($history) ? $history : [$history]);
        foreach ($historyBatches as $providerBatch) {
            if (! is_array($providerBatch)) {
                continue;
            }
            foreach ((array) ($providerBatch['threads'] ?? $payload['threads'] ?? []) as $thread) {
                if (! is_array($thread)) {
                    continue;
                }
                $this->ingestThread($company, $batch, $providerPhoneId, $thread);
            }
        }

        $batch->forceFill([
            'status' => $completed ? 'awaiting_review' : 'syncing',
            'sync_completed_at' => $completed ? now() : null,
        ])->save();
        $batch = $this->counters->refresh($batch);
        $this->audit->record($company->id, $completed ? 'history.sync_completed' : 'history.sync_progress', $batch, context: [
            'count' => $batch->contacts_discovered,
            'status' => $batch->status,
        ]);

        return $batch;
    }

    private function ingestThread(
        Company $company,
        WhatsAppHistoryImportBatch $batch,
        string $providerPhoneId,
        array $thread,
    ): void {
        $customer = $this->identity->normalize((string) ($thread['wa_id'] ?? $thread['id'] ?? $thread['phone_number'] ?? ''));
        if ($customer === '') {
            $batch->increment('excluded_contacts');

            return;
        }

        $hash = $this->identity->hash($customer);
        $contact = WhatsAppSyncedContact::query()
            ->where('company_id', $company->id)
            ->where('phone_number_id', $providerPhoneId)
            ->where('contact_hash', hash_hmac('sha256', $customer, (string) config('app.key')))
            ->first();
        $candidate = WhatsAppHistoryCandidate::query()->firstOrCreate([
            'whatsapp_history_import_batch_id' => $batch->id,
            'external_identity_hash' => $hash,
        ], [
            'company_id' => $company->id,
            'messaging_connection_id' => $batch->messaging_connection_id,
            'phone_e164' => '+'.$customer,
            'display_name' => $contact?->full_name ?: data_get($thread, 'profile.name'),
            'intelligence_status' => 'unselected',
        ]);

        WhatsAppTrackingPreference::query()->firstOrCreate([
            'company_id' => $company->id,
            'external_identity_hash' => $hash,
        ], [
            'phone_e164' => '+'.$customer,
            'decision' => 'pending',
            'source_batch_id' => $batch->id,
            'source_candidate_id' => $candidate->id,
        ]);

        foreach ((array) ($thread['messages'] ?? []) as $message) {
            if (! is_array($message)) {
                continue;
            }
            $this->ingestMessage($company, $batch, $candidate, $providerPhoneId, $customer, $message);
        }

        $stats = WhatsAppHistoryMessage::query()
            ->where('whatsapp_history_candidate_id', $candidate->id)
            ->whereNull('purged_at');
        $candidate->forceFill([
            'message_count' => (clone $stats)->count(),
            'inbound_count' => (clone $stats)->where('direction', 'in')->count(),
            'outbound_count' => (clone $stats)->where('direction', 'out')->count(),
            'first_message_at' => (clone $stats)->min('message_timestamp'),
            'last_message_at' => (clone $stats)->max('message_timestamp'),
        ])->save();
        $this->intelligence->markDeterministicStaff($candidate);
    }

    private function ingestMessage(
        Company $company,
        WhatsAppHistoryImportBatch $batch,
        WhatsAppHistoryCandidate $candidate,
        string $providerPhoneId,
        string $customer,
        array $message,
    ): void {
        $providerId = (string) ($message['id'] ?? '');
        $type = (string) ($message['type'] ?? 'unknown');
        $body = trim((string) ($message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? $message['caption']
            ?? ''));
        $timestamp = is_numeric($message['timestamp'] ?? null)
            ? Carbon::createFromTimestampUTC((int) $message['timestamp'])
            : null;
        $direction = ($message['from_me'] ?? false) || ($message['direction'] ?? null) === 'outbound' ? 'out' : 'in';
        $fingerprint = hash('sha256', implode('|', [
            $company->id, $providerPhoneId, $customer, $providerId, $timestamp?->timestamp,
            $direction, $type, hash('sha256', $body),
        ]));

        WhatsAppHistoryMessage::query()->firstOrCreate(['source_fingerprint' => $fingerprint], [
            'company_id' => $company->id,
            'whatsapp_history_import_batch_id' => $batch->id,
            'whatsapp_history_candidate_id' => $candidate->id,
            'phone_number_id' => $providerPhoneId,
            'external_identity_hash' => $candidate->external_identity_hash,
            'provider_message_id' => $providerId !== '' ? $providerId : null,
            'direction' => $direction,
            'message_type' => $type,
            'source' => 'whatsapp_coexistence_history',
            'customer_identifier' => $customer,
            'body' => $body !== '' ? $body : null,
            'metadata' => [
                'media_id' => data_get($message, $type.'.id'),
                'mime_type' => data_get($message, $type.'.mime_type'),
            ],
            'message_timestamp' => $timestamp,
        ]);
    }
}
