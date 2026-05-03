<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;
use App\Services\Leads\LeadConversionService;

use Twilio\TwiML\MessagingResponse;
use Twilio\Security\RequestValidator;

class TwilioWhatsAppWebhookController
{
    public function handle(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 🔐 STRICT SIGNATURE VALIDATION (MANDATORY)
        |--------------------------------------------------------------------------
        */
        if (!$this->validateTwilioRequest($request)) {
            return response('Unauthorized', 403);
        }

        $from      = (string) $request->input('From');
        $to        = (string) $request->input('To');
        $body      = trim((string) $request->input('Body', ''));
        $sid       = $request->input('SmsSid') ?? $request->input('MessageSid');
        $numMedia  = (int) $request->input('NumMedia', 0);
        $profile   = $request->input('ProfileName');
        $payload   = $request->all();

        $fromRaw = preg_replace('/^whatsapp:/', '', $from);
        $toRaw   = preg_replace('/^whatsapp:/', '', $to);

        Log::info('[Twilio WhatsApp] Inbound', [
            'sid'  => $sid,
            'from' => $fromRaw,
            'to'   => $toRaw,
            'body' => $body,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Resolve company
        |--------------------------------------------------------------------------
        */
        $companyId = DB::table('company_settings')
            ->whereIn('key', ['twilio.whatsapp_from', 'twilio_whatsapp_from'])
            ->where('value', $toRaw)
            ->value('company_id');

        if (!$companyId) {
            Log::warning('[Twilio WhatsApp] Company not resolved', [
                'to' => $toRaw
            ]);
            return response('OK', Response::HTTP_OK);
        }

        /*
        |--------------------------------------------------------------------------
        | Dispatch job
        |--------------------------------------------------------------------------
        */
        ProcessInboundWhatsApp::dispatch(
            from: $fromRaw,
            to: $toRaw,
            body: $body,
            sid: $sid,
            numMedia: $numMedia,
            profileName: $profile,
            provider: 'twilio',
            payload: $payload,
            companyId: (int) $companyId
        );

        /*
        |--------------------------------------------------------------------------
        | Twilio response
        |--------------------------------------------------------------------------
        */
        $twiml = new MessagingResponse();

        $twiml->message(
            $body === ''
                ? "👋 Hi! Please send a message so we can assist you."
                : "👋 Got it! Processing your request..."
        );

        return response($twiml, Response::HTTP_OK)
            ->header('Content-Type', 'text/xml');
    }

    public function status(Request $request, LeadConversionService $converter)
    {
        /*
        |--------------------------------------------------------------------------
        | 🔐 VALIDATE STATUS CALLBACK (CRITICAL)
        |--------------------------------------------------------------------------
        */
        if (!$this->validateTwilioRequest($request)) {
            return response('Unauthorized', 403);
        }

        $sid    = $request->input('MessageSid');
        $status = strtolower((string) $request->input('MessageStatus'));
        $error  = $request->input('ErrorCode');

        Log::info('[Twilio WhatsApp] Status update', compact('sid', 'status', 'error'));

        if (!$sid) {
            return response('OK', Response::HTTP_OK);
        }

        $log = MessageLog::where('provider_message_id', $sid)->latest()->first();

        if (!$log) {
            return response('OK', Response::HTTP_OK);
        }

        $meta = is_array($log->meta) ? $log->meta : [];

        if ($log->provider_status !== $status) {
            $log->update(['provider_status' => $status]);
        }

        /*
        |--------------------------------------------------------------------------
        | Convert on delivery (safe)
        |--------------------------------------------------------------------------
        */
        if ($status === 'delivered' && empty($meta['converted']) && $log->lead_id) {
            try {
                $converter->ensureClientAndOpportunity((int) $log->lead_id);

                $meta['converted'] = true;
                $log->update(['meta' => $meta]);

            } catch (\Throwable $e) {
                Log::error('[WA] Conversion failed', [
                    'sid' => $sid,
                    'err' => $e->getMessage(),
                ]);
            }
        }

        return response('OK', Response::HTTP_OK);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔐 CENTRAL VALIDATOR (REUSABLE)
    |--------------------------------------------------------------------------
    */
    private function validateTwilioRequest(Request $request): bool
    {
        try {
            $signature = $request->header('X-Twilio-Signature');

            if (!$signature) {
                Log::warning('[Twilio] Missing signature');
                return false;
            }

            $validator = new RequestValidator(
                config('services.twilio.auth_token')
            );

            $valid = $validator->validate(
                $signature,
                $request->fullUrl(),
                $request->all()
            );

            if (!$valid) {
                Log::warning('[Twilio] Invalid signature');
            }

            return $valid;

        } catch (\Throwable $e) {
            Log::error('[Twilio] Validation error', [
                'err' => $e->getMessage()
            ]);
            return false;
        }
    }
}