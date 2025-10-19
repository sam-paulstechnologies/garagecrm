<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;

class TwilioWhatsAppWebhookController
{
    public function handle(Request $request)
    {
        $from = $request->input('From');
        $to   = $request->input('To');
        $body = trim($request->input('Body', ''));
        $sid  = $request->input('SmsSid') ?? $request->input('MessageSid');
        $numMedia = (int) $request->input('NumMedia', 0);
        $profile  = $request->input('ProfileName');
        $payload  = $request->all();

        Log::info('[Twilio WhatsApp] Inbound', [
            'sid' => $sid,
            'from' => $from,
            'to' => $to,
            'body' => $body,
        ]);

        // Queue the message for DB storage
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

    public function status(Request $request)
    {
        $sid    = $request->input('MessageSid');
        $status = $request->input('MessageStatus');
        $error  = $request->input('ErrorCode');

        Log::info('[Twilio WhatsApp] Status update', [
            'sid' => $sid,
            'status' => $status,
            'error' => $error,
        ]);

        if ($sid) {
            MessageLog::where('provider_message_id', $sid)
                ->update(['provider_status' => $status]);
        }

        return response('OK', Response::HTTP_OK);
    }
}
