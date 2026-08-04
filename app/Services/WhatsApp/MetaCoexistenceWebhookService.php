<?php

namespace App\Services\WhatsApp;

use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppSyncedContact;
use App\Models\WhatsApp\WhatsAppWebhookEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MetaCoexistenceWebhookService
{
    public function process(int $eventId): void
    {
        $event = WhatsAppWebhookEvent::query()->find($eventId);
        if (! $event || $event->status === 'processed') {
            return;
        }

        $event->forceFill(['status' => 'processing', 'error_code' => null])->save();

        try {
            $company = Company::query()->findOrFail($event->company_id);
            $payload = is_array($event->payload) ? $event->payload : [];

            match ($event->field) {
                'smb_app_state_sync' => $this->processContacts($company, $payload),
                'history' => $this->processHistory($company, $payload),
                default => null,
            };

            $event->forceFill([
                'status' => 'processed',
                'processed_at' => now(),
                'error_code' => null,
            ])->save();
        } catch (\Throwable $exception) {
            $event->forceFill([
                'status' => 'retrying',
                'error_code' => class_basename($exception),
            ])->save();

            Log::warning('[SF-WA Connect] Coexistence synchronization will retry', [
                'event_id' => $eventId,
                'company_id' => $event->company_id,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }
    }

    private function processContacts(Company $company, array $payload): void
    {
        $sync = (array) ($payload['state_sync'] ?? $payload['smb_app_state_sync'] ?? []);
        $items = (array) ($sync['contacts'] ?? $payload['contacts'] ?? $sync);
        $phoneNumberId = (string) ($payload['metadata']['phone_number_id'] ?? $company->meta_phone_number_id ?? '');

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $contact = (array) ($item['contact'] ?? $item);
            $phone = $this->digits($contact['wa_id'] ?? $contact['phone_number'] ?? $contact['phone'] ?? null);
            if ($phone === '') {
                continue;
            }

            $action = strtolower((string) ($item['action'] ?? $item['type'] ?? 'add'));
            $removed = in_array($action, ['delete', 'remove', 'removed'], true);
            $hash = hash_hmac('sha256', $phone, (string) config('app.key'));

            WhatsAppSyncedContact::query()->updateOrCreate([
                'company_id' => $company->id,
                'phone_number_id' => $phoneNumberId,
                'contact_hash' => $hash,
            ], [
                'contact_phone' => $phone,
                'full_name' => $contact['full_name'] ?? $contact['name'] ?? data_get($contact, 'profile.name'),
                'first_name' => $contact['first_name'] ?? null,
                'sync_action' => $removed ? 'remove' : 'add',
                'status' => $removed ? 'removed' : 'active',
                'provider_timestamp' => $this->timestamp($item['timestamp'] ?? null),
                'last_synced_at' => now(),
            ]);
        }

        $company->forceFill([
            'whatsapp_contact_sync_status' => 'completed',
            'whatsapp_contact_sync_completed_at' => now(),
        ])->save();
    }

    private function processHistory(Company $company, array $payload): void
    {
        $phoneNumberId = (string) ($payload['metadata']['phone_number_id'] ?? $company->meta_phone_number_id ?? '');
        $history = (array) ($payload['history'] ?? []);
        $historyBatches = $history === []
            ? [$payload]
            : (array_is_list($history) ? $history : [$history]);

        foreach ($historyBatches as $batch) {
            if (! is_array($batch)) {
                continue;
            }

            foreach ((array) ($batch['threads'] ?? $payload['threads'] ?? []) as $thread) {
                if (! is_array($thread)) {
                    continue;
                }

                $customer = $this->digits($thread['wa_id'] ?? $thread['id'] ?? $thread['phone_number'] ?? null);
                foreach ((array) ($thread['messages'] ?? []) as $message) {
                    if (! is_array($message)) {
                        continue;
                    }

                    $providerId = (string) ($message['id'] ?? '');
                    $type = (string) ($message['type'] ?? 'unknown');
                    $body = $this->messageBody($message);
                    $timestamp = $this->timestamp($message['timestamp'] ?? null);
                    $direction = ($message['from_me'] ?? false) || ($message['direction'] ?? null) === 'outbound'
                        ? 'out'
                        : 'in';
                    $fingerprint = hash('sha256', implode('|', [
                        $company->id,
                        $phoneNumberId,
                        $customer,
                        $providerId,
                        $timestamp?->timestamp,
                        $direction,
                        $type,
                        hash('sha256', $body),
                    ]));

                    WhatsAppHistoryMessage::query()->firstOrCreate([
                        'source_fingerprint' => $fingerprint,
                    ], [
                        'company_id' => $company->id,
                        'phone_number_id' => $phoneNumberId,
                        'provider_message_id' => $providerId !== '' ? $providerId : null,
                        'direction' => $direction,
                        'message_type' => $type,
                        'customer_identifier' => $customer !== '' ? $customer : null,
                        'body' => $body !== '' ? $body : null,
                        'metadata' => [
                            'source' => 'history_sync',
                            'media_id' => data_get($message, $type.'.id'),
                            'mime_type' => data_get($message, $type.'.mime_type'),
                        ],
                        'message_timestamp' => $timestamp,
                    ]);
                }
            }
        }

        // History is deliberately isolated from leads, conversations and automation.
        $completed = $this->syncIsComplete($payload);
        $company->forceFill([
            'whatsapp_history_sync_status' => $completed ? 'completed' : 'received',
            'whatsapp_history_sync_completed_at' => $completed ? now() : null,
        ])->save();
    }

    private function messageBody(array $message): string
    {
        return trim((string) ($message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? $message['caption']
            ?? ''));
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    private function timestamp(mixed $value): ?Carbon
    {
        return is_numeric($value) ? Carbon::createFromTimestampUTC((int) $value) : null;
    }

    private function syncIsComplete(array $payload): bool
    {
        $status = strtolower((string) (
            $payload['status']
            ?? data_get($payload, 'metadata.status')
            ?? data_get($payload, 'history.0.status')
            ?? data_get($payload, 'history.0.metadata.status')
            ?? ''
        ));
        $progress = $payload['progress']
            ?? data_get($payload, 'metadata.progress')
            ?? data_get($payload, 'history.0.progress')
            ?? data_get($payload, 'history.0.metadata.progress');

        return in_array($status, ['complete', 'completed', 'finished'], true)
            || filter_var($payload['is_complete'] ?? false, FILTER_VALIDATE_BOOL)
            || (is_numeric($progress) && (float) $progress >= 100);
    }
}
