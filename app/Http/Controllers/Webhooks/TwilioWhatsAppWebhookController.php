<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;
use App\Services\WhatsApp\InboundMessageRecorder;

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
            'from' => $this->maskPhone($fromRaw),
            'to'   => $this->maskPhone($toRaw),
            'body_length' => mb_strlen($body),
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
                'to' => $this->maskPhone($toRaw),
            ]);
            return response('OK', Response::HTTP_OK);
        }

        /*
        |--------------------------------------------------------------------------
        | Dispatch job
        |--------------------------------------------------------------------------
        */
        $raw = app(InboundMessageRecorder::class)->record([
            'company_id' => (int) $companyId,
            'provider_message_id' => filled($sid) ? (string) $sid : null,
            'from' => $fromRaw,
            'to' => $toRaw,
            'body' => $body !== '' ? $body : ($numMedia > 0 ? '[Media]' : ''),
            'meta' => [
                'provider' => 'twilio',
                'has_media' => $numMedia > 0,
                'num_media' => $numMedia,
            ],
        ]);

        ProcessInboundWhatsApp::dispatch(
            from: $fromRaw,
            to: $toRaw,
            body: $body,
            sid: $sid,
            numMedia: $numMedia,
            profileName: $profile,
            provider: 'twilio',
            payload: $payload,
            companyId: (int) $companyId,
            messageLogId: $raw->id,
        );

        /*
        |--------------------------------------------------------------------------
        | Twilio response
        |--------------------------------------------------------------------------
        */
        $twiml = new MessagingResponse();

        // Customer-facing replies are emitted only by the queued lifecycle after
        // commercial and environment safety checks. Twilio receives an empty
        // acknowledgement here.

        return response($twiml, Response::HTTP_OK)
            ->header('Content-Type', 'text/xml');
    }

    public function status(Request $request)
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

        $fromRaw = preg_replace('/^whatsapp:/', '', (string) $request->input('From'));
        $toRaw   = preg_replace('/^whatsapp:/', '', (string) $request->input('To'));

        Log::info('[Twilio WhatsApp] Status update', compact('sid', 'status', 'error'));

        if (!$sid) {
            return response('OK', Response::HTTP_OK);
        }

        $companyId = DB::table('company_settings')
            ->whereIn('key', ['twilio.whatsapp_from', 'twilio_whatsapp_from'])
            ->whereIn('value', array_filter([$fromRaw, $toRaw]))
            ->value('company_id');

        if (!$companyId) {
            Log::warning('[Twilio WhatsApp] Status company not resolved', [
                'sid'  => $sid,
                'from' => $this->maskPhone($fromRaw),
                'to'   => $this->maskPhone($toRaw),
            ]);

            return response('OK', Response::HTTP_OK);
        }

        $log = MessageLog::where('company_id', (int) $companyId)
            ->where('provider_message_id', $sid)
            ->latest()
            ->first();

        if (!$log) {
            return response('OK', Response::HTTP_OK);
        }

        if ($log->provider_status !== $status) {
            $log->update(['provider_status' => $status]);
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

    private function maskPhone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
