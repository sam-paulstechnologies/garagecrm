<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\CompanySetting;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function twilio(Request $r)
    {
        $from = $r->input('From'); // whatsapp:+9715...
        $to   = $r->input('To');
        $body = trim((string)$r->input('Body',''));

        Log::info('WA inbound', compact('from','to','body'));

        // naive “time intent” detector; improve later
        $hasTimeIntent = preg_match('/\b(mon|tue|wed|thu|fri|sat|sun|tomorrow|today|\d{1,2}\s?(am|pm))\b/i', $body);

        if ($hasTimeIntent) {
            $companyId = 1; // TODO: infer from $to (tenant routing)
            $svc = new SendWhatsAppMessage();

            $mapping = WhatsAppTemplateMapping::where([
                'company_id' => $companyId,
                'event_key'  => 'lead.reply.suggest_time',
                'is_active'  => true,
            ])->first();

            $payload = ['client_phone'=>$from, 'text'=>$body];

            if ($mapping && $mapping->template) {
                $svc->sendUsingTemplateToManager($companyId, $payload, $mapping->template);
            } else {
                $svc->sendPlainToManager($companyId, "Client $from wants to schedule: \"$body\"");
            }
        }
        return response('OK', 200);
    }
}
