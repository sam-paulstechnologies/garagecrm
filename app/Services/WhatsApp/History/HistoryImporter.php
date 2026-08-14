<?php

namespace App\Services\WhatsApp\History;

use App\Models\Client\Client;
use App\Models\Client\RetentionAction;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HistoryImporter
{
    public function __construct(
        private readonly HistoryQuota $quota,
        private readonly HistoryBatchCounters $counters,
        private readonly HistoryAudit $audit,
    ) {}

    public function import(WhatsAppHistoryCandidate $candidate): WhatsAppHistoryCandidate
    {
        return DB::transaction(function () use ($candidate): WhatsAppHistoryCandidate {
            $candidate = WhatsAppHistoryCandidate::query()->with(['messages', 'batch'])->lockForUpdate()->findOrFail($candidate->id);
            if ($candidate->review_decision !== 'track') {
                throw new RuntimeException('Only administrator-approved Track contacts can be imported.');
            }
            if ($candidate->imported_client_id && in_array($candidate->import_status, ['created_client', 'matched_client'], true)) {
                return $candidate;
            }
            if ($candidate->staged_content_purged_at) {
                $candidate->forceFill(['import_status' => 'requires_resync'])->save();

                return $candidate;
            }
            $usage = $this->quota->claim($candidate);
            if (! $usage) {
                $candidate->forceFill(['import_status' => 'locked'])->save();

                return $candidate;
            }

            $phone = (string) $candidate->phone_e164;
            $client = Client::findByPhone((int) $candidate->company_id, $phone);
            $matched = $client !== null;
            $client ??= Client::query()->create([
                'company_id' => $candidate->company_id,
                'name' => $candidate->display_name ?: 'WhatsApp Contact',
                'phone' => $phone,
                'whatsapp' => $phone,
                'source' => 'whatsapp_history',
                'status' => 'active',
            ]);
            if ((int) $client->company_id !== (int) $candidate->company_id) {
                throw new RuntimeException('Cross-tenant client match refused.');
            }

            $conversation = Conversation::query()->firstOrCreate([
                'company_id' => $candidate->company_id,
                'client_id' => $client->id,
                'is_whatsapp_linked' => true,
            ], [
                'customer_name' => $client->name,
                'customer_phone' => $phone,
                'subject' => 'Imported WhatsApp history',
                'latest_message_at' => $candidate->last_message_at,
                'last_message_at' => $candidate->last_message_at,
                'unread_count' => 0,
            ]);

            foreach ($candidate->messages->whereNull('purged_at') as $history) {
                $providerId = $history->provider_message_id ?: 'history-'.$history->source_fingerprint;
                $existing = MessageLog::query()->where('provider_message_id', $providerId)->first();
                if ($existing) {
                    if ((int) $existing->company_id !== (int) $candidate->company_id) {
                        throw new RuntimeException('Cross-tenant historical message identity refused.');
                    }

                    continue;
                }
                $message = MessageLog::query()->create([
                    'company_id' => $candidate->company_id,
                    'conversation_id' => $conversation->id,
                    'direction' => $history->direction,
                    'channel' => 'whatsapp',
                    'source' => 'whatsapp_coexistence_history',
                    'is_historical' => true,
                    'whatsapp_history_candidate_id' => $candidate->id,
                    'historical_message_timestamp' => $history->message_timestamp,
                    'to_number' => $history->direction === 'out' ? $phone : null,
                    'from_number' => $history->direction === 'in' ? $phone : null,
                    'body' => $history->body ?: '['.ucfirst((string) $history->message_type).']',
                    'provider_message_id' => $providerId,
                    'provider_status' => 'historical',
                    'meta' => ['historical' => true, 'source' => $history->source],
                ]);
                if ($history->message_timestamp) {
                    $message->forceFill([
                        'created_at' => $history->message_timestamp,
                        'updated_at' => $history->message_timestamp,
                    ])->saveQuietly();
                }
            }

            if ($candidate->retention_level !== 'none') {
                RetentionAction::query()->firstOrCreate([
                    'company_id' => $candidate->company_id,
                    'source_type' => 'whatsapp_history_candidate',
                    'source_id' => $candidate->id,
                ], [
                    'client_id' => $client->id,
                    'segment_code' => 'whatsapp_history_'.$candidate->retention_level,
                    'segment_label' => ucfirst($candidate->retention_level).' retention potential',
                    'status' => 'insight_only',
                    'meta' => ['automation_allowed' => false, 'candidate_public_id' => $candidate->public_id],
                ]);
            }

            $usage->forceFill(['imported_at' => $usage->imported_at ?? now()])->save();
            $candidate->forceFill([
                'imported_client_id' => $client->id,
                'imported_conversation_id' => $conversation->id,
                'import_status' => $matched ? 'matched_client' : 'created_client',
                'imported_at' => now(),
            ])->save();
            $this->audit->record($candidate->company_id, 'history.contact_imported', $candidate->batch, $candidate, context: [
                'new_clients' => $matched ? 0 : 1,
                'matched_clients' => $matched ? 1 : 0,
                'messages_imported' => $candidate->messages()->whereNull('purged_at')->count(),
            ]);
            $this->counters->refresh($candidate->batch);

            return $candidate->fresh();
        }, 3);
    }
}
