<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Support\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function twilio(Request $r)
    {
        // Minimal Twilio inbound fields:
        $from = $r->input('From');      // whatsapp:+9715...
        $to   = $r->input('To');        // whatsapp:+1415... (your sender)
        $body = trim((string) $r->input('Body', ''));

        // Try to resolve tenant/company by 'To' number (strip 'whatsapp:')
        $toRaw = is_string($to) ? str_replace('whatsapp:', '', $to) : null;
        $companyId = (int) (DB::table('company_settings')
            ->whereIn('key', ['twilio.whatsapp_from', 'twilio_whatsapp_from'])
            ->where('value', $toRaw)
            ->value('company_id') ?? 1);

        // Resolve lead by phone. Adjust to your schema as needed.
        $digits = preg_replace('/\D+/', '', (string) $from);
        $lead = Lead::where('company_id', $companyId)
            ->where(function ($q) use ($digits) {
                $q->where('phone', 'like', '%'.$digits)
                  ->orWhere('phone_norm', 'like', '%'.$digits);
            })
            ->latest('id')
            ->first();

        // INBOUND LOG
        MessageLog::in([
            'company_id'          => $companyId,
            'lead_id'             => $lead?->id,
            'to_number'           => $to,
            'from_number'         => $from,
            'body'                => $body,
            'provider_message_id' => $r->input('SmsMessageSid') ?? $r->input('MessageSid'),
            'provider_status'     => $r->input('SmsStatus')     ?? $r->input('MessageStatus'),
            'meta'                => json_encode($r->all()),
        ]);

        Log::info('WA inbound', ['company_id'=>$companyId,'lead_id'=>$lead?->id,'from'=>$from,'to'=>$to,'body'=>$body]);

        // Very simple time intent detector (improve later)
        $hasTimeIntent = preg_match('/\b(mon|tue|wed|thu|fri|sat|sun|tomorrow|today|\d{1,2}\s?(am|pm))\b/i', $body);

        if ($hasTimeIntent) {
            // Notify manager using mapping key: lead.reply.suggest_time
            $mapping = WhatsAppTemplateMapping::where([
                'company_id' => $companyId,
                'event_key'  => 'lead.reply.suggest_time',
                'is_active'  => true,
            ])->first();

            $payload = [
                'client_phone' => $from,
                'text'         => $body,
            ];

            $svc = new SendWhatsAppMessage();
            if ($mapping && $mapping->template) {
                $svc->sendUsingTemplateToManager($companyId, $payload, $mapping->template);
            } else {
                $svc->sendPlainToManager($companyId, "Client at {$from} wants to schedule: \"{$body}\"");
            }

            if ($lead) {
                $lead->status = 'appointment_requested';
                $lead->save();
            }
        }

        return response('OK', 200);
    }
}
