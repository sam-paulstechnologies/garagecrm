<?php

namespace App\Services\Conversation;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Log;

class MessageLogger
{
    /**
     * Log inbound message
     */
    public function logInbound(array $data): void
    {
        try {

            MessageLog::create([
                'company_id'      => $data['company_id'],
                'lead_id'         => $data['lead_id'] ?? null,
                'conversation_id' => $data['conversation_id'] ?? null,
                'direction'       => 'in',
                'channel'         => $data['channel'] ?? 'whatsapp',
                'to_number'       => $data['to'],
                'from_number'     => $data['from'],
                'body'            => $data['body'] ?? '',
                'template'        => null,
                'provider_message_id' => $data['provider_message_id'] ?? null,
                'provider_status' => 'received',
                'meta'            => $data['meta'] ?? [],
                'ai_analysis'     => $data['ai_analysis'] ?? null,
            ]);

        } catch (\Throwable $e) {

            Log::error('[MessageLogger] inbound log failed', [
                'err' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log outbound message
     */
    public function logOutbound(array $data): void
    {
        try {

            MessageLog::create([
                'company_id'      => $data['company_id'],
                'lead_id'         => $data['lead_id'] ?? null,
                'conversation_id' => $data['conversation_id'] ?? null,
                'direction'       => 'out',
                'channel'         => $data['channel'] ?? 'whatsapp',
                'to_number'       => $data['to'],
                'from_number'     => $data['from'],
                'body'            => $data['body'] ?? '',
                'template'        => $data['template'] ?? null,
                'provider_status' => 'sent',
                'meta'            => $data['meta'] ?? [],
            ]);

        } catch (\Throwable $e) {

            Log::error('[MessageLogger] outbound log failed', [
                'err' => $e->getMessage()
            ]);
        }
    }
}