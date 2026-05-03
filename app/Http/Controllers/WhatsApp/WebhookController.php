<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Shared\Communication;
use App\Services\WhatsApp\WhatsAppLeadIngestService;
use App\Services\Leads\LeadFactory;
use App\Services\Leads\LeadConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function twilio(Request $request)
    {
        /** --------------------------------
         * 1️⃣ Extract inbound message
         * -------------------------------- */
        $from = (string) $request->input('From'); // whatsapp:+9715...
        $to   = (string) $request->input('To');
        $body = trim((string) $request->input('Body', ''));

        if ($from === '' || $to === '') {
            return response('Invalid payload', 400);
        }

        /** --------------------------------
         * 2️⃣ Resolve company from WhatsApp number
         * -------------------------------- */
        $toRaw = str_replace('whatsapp:', '', $to);

        $companyId = (int) DB::table('company_settings')
            ->whereIn('key', ['twilio.whatsapp_from', 'twilio_whatsapp_from'])
            ->where('value', $toRaw)
            ->value('company_id');

        if (!$companyId) {
            Log::warning('WA inbound: company not resolved', [
                'to' => $toRaw,
            ]);
            return response('Company not found', 404);
        }

        /** --------------------------------
         * 3️⃣ Ingest Lead (auto-create / dedupe)
         * -------------------------------- */
        $payload = [
            'company_id' => $companyId,
            'from'       => preg_replace('/\D+/', '', $from),
            'name'       => 'WhatsApp Lead',
            'message'    => $body,
            'raw'        => $request->all(),
        ];

        $lead = app(WhatsAppLeadIngestService::class)
            ->ingest($payload, app(LeadFactory::class));

        /** --------------------------------
         * 4️⃣ Ensure Client + Opportunity
         * -------------------------------- */
        app(LeadConversionService::class)
            ->ensureClientAndOpportunity($lead->id);

        /** --------------------------------
         * 5️⃣ Log communication (source of truth)
         * -------------------------------- */
        Communication::create([
            'company_id'         => $companyId,
            'client_id'          => $lead->client_id,
            'lead_id'            => $lead->id,
            'opportunity_id'     => $lead->opportunity?->id,
            'type'               => 'whatsapp',
            'content'            => $body,
            'communication_date' => now(),
        ]);

        Log::info('WA inbound processed', [
            'company_id'     => $companyId,
            'lead_id'        => $lead->id,
            'client_id'      => $lead->client_id,
            'opportunity_id' => $lead->opportunity?->id,
        ]);

        return response('OK', 200);
    }
}
