<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;
use App\Services\Leads\LeadConversionService;

class TwilioWhatsAppWebhookController
{
    public function handle(Request $request)
    {
        $from = (string) $request->input('From');
        $to   = (string) $request->input('To');
        $body = trim((string) $request->input('Body', ''));
        $sid  = $request->input('SmsSid') ?? $request->input('MessageSid');
        $numMedia = (int) $request->input('NumMedia', 0);
        $profile  = $request->input('ProfileName');
        $payload  = $request->all();

        Log::info('[Twilio WhatsApp] Inbound', compact('sid','from','to') + ['body' => $body]);

        ProcessInboundWhatsApp::dispatch(
            from: $from,
            to: $to,
            body: $body,
            sid: $sid,
            numMedia: $numMedia,
            profileName: $profile,
            provider: 'twilio',
            payload: $payload
        );

        return response('OK', Response::HTTP_OK);
    }

    public function status(Request $request, LeadConversionService $converter)
    {
        $sid    = $request->input('MessageSid');
        $status = $request->input('MessageStatus'); // queued|sent|delivered|read|failed...
        $error  = $request->input('ErrorCode');

        $statusNorm = is_string($status) ? strtolower($status) : $status;

        Log::info('[Twilio WhatsApp] Status update', [
            'sid'    => $sid,
            'status' => $statusNorm,
            'error'  => $error,
        ]);

        if ($sid) {
            // 1) Update provider status on our log
            MessageLog::where('provider_message_id', $sid)
                ->update(['provider_status' => $statusNorm]);

            // 2) Delivery-gated conversion
            if ($statusNorm === 'delivered') {
                $log = MessageLog::where('provider_message_id', $sid)->latest('id')->first();
                if ($log && $log->lead_id) {
                    try {
                        // Optional guard: restrict to first-touch templates
                        // if (in_array($log->template, ['lead_welcome','ack_lead','lead_acknowledgment_v2'], true)) {
                        $converter->ensureClientAndOpportunity((int) $log->lead_id);
                        Log::info('[Twilio WhatsApp] Lead converted on delivery', [
                            'lead_id' => $log->lead_id,
                            'sid'     => $sid,
                        ]);
                        // }
                    } catch (\Throwable $e) {
                        Log::error('[Twilio WhatsApp] Conversion on delivery failed', [
                            'sid' => $sid,
                            'err' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return response('OK', Response::HTTP_OK);
    }
}
