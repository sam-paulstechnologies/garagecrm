<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessInboundWhatsApp;
use App\Models\System\Company;
use App\Models\MessageLog;

class MetaWhatsAppWebhookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | META WEBHOOK VERIFICATION (GET)
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request)
    {
        // IMPORTANT: Laravel converts dots to underscores in some setups
        $mode      = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token     = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        Log::info('[META] Verification attempt', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        if ($mode !== 'subscribe') {
            Log::warning('[META] Invalid verify mode');
            return response('Forbidden', 403);
        }

        if (!$token) {
            Log::warning('[META] Missing verify token');
            return response('Forbidden', 403);
        }

        $company = Company::where('meta_verify_token', $token)->first();

        if (!$company) {
            Log::warning('[META] Verify token not matched');
            return response('Forbidden', 403);
        }

        Log::info('[META] Verification successful', [
            'company_id' => $company->id
        ]);

        return response($challenge, 200);
    }

    /*
    |--------------------------------------------------------------------------
    | META WEBHOOK RECEIVER (POST)
    |--------------------------------------------------------------------------
    */
    public function handle(Request $request)
    {
        Log::info('[META] Webhook hit');

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ SIGNATURE VALIDATION
        |--------------------------------------------------------------------------
        */
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::warning('[META] Missing signature header');
            return response('Missing signature', 403);
        }

        $appSecret = config('services.meta_leads.app_secret');

        if (!$appSecret) {
            Log::error('[META] META_APP_SECRET not configured');
            return response('Server misconfigured', 500);
        }

        $expected = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $appSecret
        );

        if (!hash_equals($expected, $signature)) {
            Log::warning('[META] Signature mismatch');
            return response('Invalid signature', 403);
        }

        Log::info('[META] Signature validated');

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ EXTRACT PAYLOAD
        |--------------------------------------------------------------------------
        */
        $payload = $request->all();
        $value = $request->input('entry.0.changes.0.value');

        if (!$value) {
            Log::info('[META] Empty payload structure');
            return response()->noContent();
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ STATUS UPDATES
        |--------------------------------------------------------------------------
        | CRITICAL FIX:
        | Earlier we were only saving provider_status.
        | Now we also merge the full Meta status object into message_logs.meta,
        | including failed error reason.
        |--------------------------------------------------------------------------
        */
        if (!empty($value['statuses'])) {

            $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

            if (!$phoneNumberId) {
                Log::warning('[META] Status update missing phone_number_id');
                return response()->noContent();
            }

            $company = Company::where(
                'meta_phone_number_id',
                $phoneNumberId
            )->first();

            if (!$company) {
                Log::warning('[META] Status company not resolved', [
                    'phone_number_id' => $phoneNumberId,
                ]);

                return response()->noContent();
            }

            foreach ($value['statuses'] as $status) {

                $messageId = $status['id'] ?? null;
                $providerStatus = $status['status'] ?? null;

                if (!$messageId) {
                    Log::warning('[META] Status update missing message id', [
                        'company_id' => $company->id,
                        'status' => $status,
                    ]);

                    continue;
                }

                $messageLog = MessageLog::where('company_id', $company->id)
                    ->where('provider_message_id', $messageId)
                    ->latest('id')
                    ->first();

                if (!$messageLog) {
                    Log::warning('[META] Status update message log not found', [
                        'company_id' => $company->id,
                        'provider_message_id' => $messageId,
                        'provider_status' => $providerStatus,
                        'status_payload' => $status,
                    ]);

                    continue;
                }

                $existingMeta = $messageLog->meta ?? [];

                if (is_string($existingMeta)) {
                    $decoded = json_decode($existingMeta, true);
                    $existingMeta = is_array($decoded) ? $decoded : [];
                }

                if (!is_array($existingMeta)) {
                    $existingMeta = [];
                }

                $errors = $status['errors'] ?? [];

                $messageLog->update([
                    'provider_status' => $providerStatus,
                    'meta' => array_merge($existingMeta, [
                        'last_webhook_status' => $status,
                        'last_webhook_value' => $value,
                        'last_webhook_received_at' => now()->toIso8601String(),

                        // Easy-to-query fields
                        'wa_status' => $providerStatus,
                        'wa_timestamp' => $status['timestamp'] ?? null,
                        'wa_recipient_id' => $status['recipient_id'] ?? null,
                        'wa_conversation' => $status['conversation'] ?? null,
                        'wa_pricing' => $status['pricing'] ?? null,

                        // Error fields
                        'wa_errors' => $errors,
                        'wa_error_code' => $errors[0]['code'] ?? null,
                        'wa_error_title' => $errors[0]['title'] ?? null,
                        'wa_error_message' => $errors[0]['message'] ?? null,
                        'wa_error_details' => $errors[0]['error_data']['details'] ?? null,
                    ]),
                ]);

                Log::info('[META] Status update processed', [
                    'company_id' => $company->id,
                    'message_log_id' => $messageLog->id,
                    'provider_message_id' => $messageId,
                    'provider_status' => $providerStatus,
                    'error_code' => $errors[0]['code'] ?? null,
                    'error_title' => $errors[0]['title'] ?? null,
                    'error_details' => $errors[0]['error_data']['details'] ?? null,
                ]);
            }

            return response()->noContent();
        }

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ INBOUND MESSAGE
        |--------------------------------------------------------------------------
        */
        if (empty($value['messages'][0])) {
            Log::info('[META] No inbound message found');
            return response()->noContent();
        }

        $msg = $value['messages'][0];
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if (!$phoneNumberId) {
            Log::warning('[META] Missing phone_number_id');
            return response()->noContent();
        }

        /*
        |--------------------------------------------------------------------------
        | 🔴 MULTI-TENANT COMPANY RESOLUTION
        |--------------------------------------------------------------------------
        */
        $company = Company::where(
            'meta_phone_number_id',
            $phoneNumberId
        )->first();

        if (!$company) {
            Log::warning('[META] No company mapped', [
                'phone_number_id' => $phoneNumberId
            ]);
            return response()->noContent();
        }

        Log::info('[META] Company resolved', [
            'company_id' => $company->id
        ]);

        /*
        |--------------------------------------------------------------------------
        | MESSAGE BODY EXTRACTION
        |--------------------------------------------------------------------------
        | Supports:
        | - Normal text replies
        | - Template quick reply buttons
        | - Interactive button replies
        | - Interactive list replies
        */
        $body = $this->extractMessageBody($msg);

        if ($body === '') {
            $body = '[Non-text message received]';
        }

        /*
        |--------------------------------------------------------------------------
        | DISPATCH JOB
        |--------------------------------------------------------------------------
        */
        $profileName = $value['contacts'][0]['profile']['name'] ?? null;

        ProcessInboundWhatsApp::dispatch(
            from: $msg['from'] ?? null,
            to: $value['metadata']['display_phone_number'] ?? null,
            body: $body,
            sid: $msg['id'] ?? null,
            profileName: $profileName,
            provider: 'meta',
            payload: $payload,
            companyId: $company->id
        );

        Log::info('[META] Inbound message dispatched', [
            'company_id' => $company->id,
            'type' => $msg['type'] ?? null,
            'body' => $body,
        ]);

        return response()->noContent();
    }

    /*
    |--------------------------------------------------------------------------
    | Extract Meta inbound body
    |--------------------------------------------------------------------------
    */
    protected function extractMessageBody(array $msg): string
    {
        $type = $msg['type'] ?? null;

        if ($type === 'text') {
            return trim((string) ($msg['text']['body'] ?? ''));
        }

        if ($type === 'button') {
            return trim((string) (
                $msg['button']['text']
                ?? $msg['button']['payload']
                ?? ''
            ));
        }

        if ($type === 'interactive') {
            $interactiveType = $msg['interactive']['type'] ?? null;

            if ($interactiveType === 'button_reply') {
                return trim((string) (
                    $msg['interactive']['button_reply']['title']
                    ?? $msg['interactive']['button_reply']['id']
                    ?? ''
                ));
            }

            if ($interactiveType === 'list_reply') {
                return trim((string) (
                    $msg['interactive']['list_reply']['title']
                    ?? $msg['interactive']['button_reply']['id']
                    ?? ''
                ));
            }
        }

        return '';
    }
}