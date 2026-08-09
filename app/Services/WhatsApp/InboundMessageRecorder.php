<?php

namespace App\Services\WhatsApp;

use App\Models\MessageLog;
use RuntimeException;

class InboundMessageRecorder
{
    public function record(array $data): MessageLog
    {
        $companyId = (int) ($data['company_id'] ?? 0);
        if ($companyId < 1) {
            throw new RuntimeException('Inbound message company is required.');
        }

        $providerMessageId = filled($data['provider_message_id'] ?? null)
            ? (string) $data['provider_message_id']
            : null;
        $attributes = $providerMessageId
            ? ['company_id' => $companyId, 'provider_message_id' => $providerMessageId]
            : null;
        $values = [
            'company_id' => $companyId,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'source' => 'customer',
            'to_number' => $data['to'] ?? null,
            'from_number' => $data['from'] ?? null,
            'body' => $data['body'] ?? '',
            'provider_message_id' => $providerMessageId,
            'provider_status' => 'received',
            'meta' => array_merge((array) ($data['meta'] ?? []), [
                'lifecycle_stage' => 'raw_captured',
                'raw_captured_at' => now()->toIso8601String(),
            ]),
        ];

        $message = $attributes
            ? MessageLog::query()->firstOrCreate($attributes, $values)
            : MessageLog::query()->create($values);

        if ($message->direction !== 'in' || (int) $message->company_id !== $companyId) {
            throw new RuntimeException('Provider message identity conflicts with an existing record.');
        }

        return $message;
    }

    public function markLifecycle(MessageLog $message, string $stage, ?string $reason = null, array $extra = []): void
    {
        $meta = is_array($message->meta) ? $message->meta : [];
        $message->forceFill(['meta' => array_merge($meta, $extra, [
            'lifecycle_stage' => $stage,
            'lifecycle_reason' => $reason,
            'lifecycle_updated_at' => now()->toIso8601String(),
        ])])->save();
    }
}
