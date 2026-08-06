<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Data\ResolvedMessagingContext;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\Models\MessagingWebhookEvent;
use App\Models\System\Company;

class WebhookRouter
{
    public function resolve(string $wabaId, string $phoneNumberId): ?ResolvedMessagingContext
    {
        $connection = null;
        $phone = null;

        if ($phoneNumberId !== '') {
            $phones = MessagingPhoneNumber::query()
                ->where('provider', 'meta_whatsapp')
                ->where('phone_number_id', $phoneNumberId)
                ->with('connection.company')
                ->limit(2)
                ->get();
            if ($phones->count() > 1) {
                return null;
            }
            if ($phones->count() === 1) {
                $phone = $phones->first();
                $connection = $phone->connection;
            }
        } elseif ($wabaId !== '') {
            $connections = MessagingConnection::query()
                ->where('provider', 'meta_whatsapp')
                ->where('waba_id', $wabaId)
                ->with(['company', 'phoneNumbers'])
                ->limit(2)
                ->get();
            if ($connections->count() > 1) {
                return null;
            }
            if ($connections->count() === 1) {
                $connection = $connections->first();
                $phone = $connection->phoneNumbers->firstWhere('is_primary', true);
            }
        }

        if ($connection) {
            if ($connection->status?->value !== 'connected') {
                return null;
            }
            if ($wabaId !== '' && (string) $connection->waba_id !== $wabaId) {
                return null;
            }
            return new ResolvedMessagingContext(
                company: $connection->company,
                connection: $connection,
                phoneNumber: $phone,
                productKey: $connection->product_key,
            );
        }

        // Compatibility lookup for pre-Phase-1 production connections such as Smart Matrix.
        $query = Company::query()->where('is_whatsapp_active', true);
        if ($phoneNumberId !== '') {
            $query->where('meta_phone_number_id', $phoneNumberId);
        } elseif ($wabaId !== '') {
            $query->where('meta_waba_id', $wabaId);
        } else {
            return null;
        }
        $companies = $query->limit(2)->get();
        if ($companies->count() !== 1) {
            return null;
        }
        $company = $companies->first();
        if ($wabaId !== '' && filled($company->meta_waba_id) && (string) $company->meta_waba_id !== $wabaId) {
            return null;
        }

        return new ResolvedMessagingContext(
            company: $company,
            connection: null,
            phoneNumber: null,
            productKey: (string) config('messaging.default_product', 'sayaraforce'),
            legacy: true,
        );
    }

    public function quarantine(string $wabaId, string $phoneNumberId, string $field, array $value): MessagingWebhookEvent
    {
        $providerEventId = (string) (data_get($value, 'messages.0.id')
            ?? data_get($value, 'statuses.0.id')
            ?? data_get($value, 'smb_message_echoes.0.id')
            ?? '');
        $safeShape = [
            'value_keys' => array_values(array_map('strval', array_keys($value))),
            'has_waba_id' => $wabaId !== '',
            'has_phone_number_id' => $phoneNumberId !== '',
        ];
        $hash = hash('sha256', json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $eventKey = hash('sha256', implode('|', ['meta_whatsapp', $wabaId, $phoneNumberId, $field, $providerEventId ?: $hash]));

        return MessagingWebhookEvent::query()->firstOrCreate(['event_key' => $eventKey], [
            'provider' => 'meta_whatsapp',
            'field' => $field,
            'event_type' => 'unmapped',
            'provider_event_id' => $providerEventId ?: null,
            'payload_hash' => $hash,
            'status' => 'quarantined',
            'error_code' => 'tenant_not_resolved',
            'metadata' => $safeShape,
            'occurred_at' => now(),
        ]);
    }
}
