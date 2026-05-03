<?php

namespace App\Services\Ai;

use App\Models\MessageLog;
use Illuminate\Support\Facades\DB;

class AiOutboundSender
{
    public static function sendFromInbound(MessageLog $inbound, string $replyText): MessageLog
    {
        $out = new MessageLog();

        // Copy safe linkage fields
        foreach ([
            'company_id',
            'conversation_id',
            'client_id',
            'lead_id',
            'opportunity_id',
            'booking_id',
            'to_number',
            'from_number',
            'channel',
            'provider',
        ] as $field) {
            if (isset($inbound->{$field})) {
                $out->{$field} = $inbound->{$field};
            }
        }

        // Required flags
        $out->direction = 'out';
        $out->source    = 'ai';

        // Content mapping (try known columns)
        if (property_exists($out, 'body')) {
            $out->body = $replyText;
        } elseif (property_exists($out, 'content')) {
            $out->content = $replyText;
        } elseif (property_exists($out, 'message')) {
            $out->message = $replyText;
        } else {
            // Absolute fallback
            DB::table('message_logs')->insert([
                'company_id'      => $inbound->company_id,
                'conversation_id' => $inbound->conversation_id ?? null,
                'direction'       => 'out',
                'source'          => 'ai',
                'body'            => $replyText,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return MessageLog::query()
                ->where('company_id', $inbound->company_id)
                ->where('conversation_id', $inbound->conversation_id ?? 0)
                ->orderByDesc('id')
                ->firstOrFail();
        }

        // Provider lifecycle
        if (property_exists($out, 'provider_status')) {
            $out->provider_status = 'queued';
        }

        $out->save();

        // Fire actual send if job exists
        if (class_exists(\App\Jobs\SendWhatsAppMessage::class)) {
            \App\Jobs\SendWhatsAppMessage::dispatch($out->id);
        }

        return $out;
    }
}
