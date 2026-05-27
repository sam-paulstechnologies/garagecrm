<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;
use App\Models\System\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetaWhatsAppWebhookController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | META WEBHOOK VERIFICATION (GET)
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        Log::info('[SF-WA Connect][META] Verification attempt', [
            'mode' => $mode,
            'token_present' => filled($token),
        ]);

        if ($mode !== 'subscribe') {
            Log::warning('[SF-WA Connect][META] Invalid verify mode');
            return response('Forbidden', 403);
        }

        if (! $token) {
            Log::warning('[SF-WA Connect][META] Missing verify token');
            return response('Forbidden', 403);
        }

        $company = Company::query()
            ->where('meta_verify_token', $token)
            ->first();

        if (! $company) {
            Log::warning('[SF-WA Connect][META] Verify token not matched');
            return response('Forbidden', 403);
        }

        Log::info('[SF-WA Connect][META] Verification successful', [
            'company_id' => $company->id,
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
        Log::info('[SF-WA Connect][META] Webhook hit');

        $signatureResponse = $this->validateSignature($request);

        if ($signatureResponse) {
            return $signatureResponse;
        }

        $payload = $request->all();
        $value = $request->input('entry.0.changes.0.value');

        if (! $value) {
            Log::info('[SF-WA Connect][META] Empty payload structure');
            return response()->noContent();
        }

        /*
        |--------------------------------------------------------------------------
        | Status updates
        |--------------------------------------------------------------------------
        | Status payloads can arrive separately or alongside other webhook changes.
        | We process them but do not let them block echo/inbound detection.
        */
        if (! empty($value['statuses'])) {
            $this->handleStatuses($value);

            if (empty($value['smb_message_echoes']) && empty($value['messages'][0])) {
                return response()->noContent();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Business App Coexistence Echoes
        |--------------------------------------------------------------------------
        | Messages sent by the garage from the WhatsApp Business mobile app arrive
        | as smb_message_echoes. These must be logged as outbound/manual messages.
        |
        | IMPORTANT:
        | Do not dispatch ProcessInboundWhatsApp here.
        | Do not create/reuse leads here.
        | Do not trigger bot replies here.
        */
        if (! empty($value['smb_message_echoes'])) {
            return $this->handleSmbMessageEchoes($payload, $value);
        }

        if (empty($value['messages'][0])) {
            Log::info('[SF-WA Connect][META] No inbound message found');
            return response()->noContent();
        }

        return $this->handleInboundMessage($payload, $value);
    }

    protected function validateSignature(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            Log::warning('[SF-WA Connect][META] Missing signature header');
            return response('Missing signature', 403);
        }

        $appSecret = config('services.meta_leads.app_secret')
            ?: config('services.meta.app_secret')
            ?: env('META_APP_SECRET');

        if (! $appSecret) {
            Log::error('[SF-WA Connect][META] META_APP_SECRET not configured');
            return response('Server misconfigured', 500);
        }

        $expected = 'sha256='.hash_hmac(
            'sha256',
            $request->getContent(),
            $appSecret
        );

        if (! hash_equals($expected, $signature)) {
            Log::warning('[SF-WA Connect][META] Signature mismatch');
            return response('Invalid signature', 403);
        }

        Log::info('[SF-WA Connect][META] Signature validated');

        return null;
    }

    protected function handleStatuses(array $value)
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if (! $phoneNumberId) {
            Log::warning('[SF-WA Connect][META] Status update missing phone_number_id');
            return response()->noContent();
        }

        $company = $this->resolveCompanyByPhoneNumberId($phoneNumberId);

        if (! $company) {
            Log::warning('[SF-WA Connect][META] Status company not resolved', [
                'phone_number_id' => $phoneNumberId,
            ]);

            return response()->noContent();
        }

        foreach ($value['statuses'] as $status) {
            $messageId = $status['id'] ?? null;
            $providerStatus = $status['status'] ?? null;

            if (! $messageId) {
                Log::warning('[SF-WA Connect][META] Status update missing message id', [
                    'company_id' => $company->id,
                    'status' => $status,
                ]);

                continue;
            }

            $messageLog = MessageLog::query()
                ->where('company_id', $company->id)
                ->where('provider_message_id', $messageId)
                ->latest('id')
                ->first();

            if (! $messageLog) {
                Log::warning('[SF-WA Connect][META] Status update message log not found', [
                    'company_id' => $company->id,
                    'provider_message_id' => $messageId,
                    'provider_status' => $providerStatus,
                    'status_payload' => $status,
                ]);

                $this->storeUsageLogIfAvailable($company, null, $messageId, $phoneNumberId, $status);

                continue;
            }

            $existingMeta = $messageLog->meta ?? [];

            if (is_string($existingMeta)) {
                $decoded = json_decode($existingMeta, true);
                $existingMeta = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($existingMeta)) {
                $existingMeta = [];
            }

            $errors = $status['errors'] ?? [];

            $messageLog->update([
                'provider_status' => $providerStatus,
                'meta' => array_merge($existingMeta, [
                    'last_webhook_status' => $status,
                    'last_webhook_value' => $value,
                    'last_webhook_received_at' => now()->toIso8601String(),

                    'wa_status' => $providerStatus,
                    'wa_timestamp' => $status['timestamp'] ?? null,
                    'wa_recipient_id' => $status['recipient_id'] ?? null,
                    'wa_conversation' => $status['conversation'] ?? null,
                    'wa_pricing' => $status['pricing'] ?? null,

                    'wa_errors' => $errors,
                    'wa_error_code' => $errors[0]['code'] ?? null,
                    'wa_error_title' => $errors[0]['title'] ?? null,
                    'wa_error_message' => $errors[0]['message'] ?? null,
                    'wa_error_details' => $errors[0]['error_data']['details'] ?? null,
                ]),
            ]);

            $this->storeUsageLogIfAvailable($company, $messageLog, $messageId, $phoneNumberId, $status);

            Log::info('[SF-WA Connect][META] Status update processed', [
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

    protected function handleSmbMessageEchoes(array $payload, array $value)
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
        $displayPhoneNumber = $value['metadata']['display_phone_number'] ?? null;

        if (! $phoneNumberId) {
            Log::warning('[SF-WA Connect][META][COEXISTENCE] Echo missing phone_number_id');
            return response()->noContent();
        }

        $company = $this->resolveCompanyByPhoneNumberId($phoneNumberId);

        if (! $company) {
            Log::warning('[SF-WA Connect][META][COEXISTENCE] Echo company not resolved', [
                'phone_number_id' => $phoneNumberId,
            ]);

            return response()->noContent();
        }

        if (! (bool) ($company->is_whatsapp_active ?? false)) {
            Log::warning('[SF-WA Connect][META][COEXISTENCE] Company WhatsApp inactive; echo ignored', [
                'company_id' => $company->id,
                'phone_number_id' => $phoneNumberId,
            ]);

            return response()->noContent();
        }

        foreach ($value['smb_message_echoes'] as $echo) {
            $providerMessageId = $echo['id'] ?? null;

            if ($providerMessageId) {
                $alreadyLogged = MessageLog::query()
                    ->where('company_id', $company->id)
                    ->where('provider_message_id', $providerMessageId)
                    ->exists();

                if ($alreadyLogged) {
                    Log::info('[SF-WA Connect][META][COEXISTENCE] Echo already logged', [
                        'company_id' => $company->id,
                        'provider_message_id' => $providerMessageId,
                    ]);

                    continue;
                }
            }

            $body = $this->extractMessageBody($echo);

            if ($body === '') {
                $body = '[WhatsApp Business App message]';
            }

            $customerNumber = $this->extractEchoCustomerNumber($echo);

            DB::table('message_logs')->insert([
                'company_id' => $company->id,
                'lead_id' => null,
                'conversation_id' => null,
                'direction' => 'out',
                'source' => 'whatsapp_business_app',
                'is_ai' => 0,
                'channel' => 'whatsapp',
                'to_number' => $customerNumber,
                'from_number' => $displayPhoneNumber,
                'template' => null,
                'template_id' => null,
                'body' => $body,
                'provider_message_id' => $providerMessageId,
                'provider_status' => 'sent_from_business_app',
                'meta' => json_encode([
                    'provider' => 'meta',
                    'provider_message_type' => 'smb_message_echoes',
                    'is_echo' => true,
                    'echo_payload' => $echo,
                    'webhook_value' => $value,
                    'webhook_payload' => $payload,
                    'received_at' => now()->toIso8601String(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->updateCompanyLastEchoAt($company);

            Log::info('[SF-WA Connect][META][COEXISTENCE] Business App echo logged', [
                'company_id' => $company->id,
                'provider_message_id' => $providerMessageId,
                'from_number' => $displayPhoneNumber,
                'to_number' => $customerNumber,
                'body' => $body,
            ]);
        }

        return response()->noContent();
    }

    protected function handleInboundMessage(array $payload, array $value)
    {
        $msg = $value['messages'][0];
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if (! $phoneNumberId) {
            Log::warning('[SF-WA Connect][META] Missing phone_number_id');
            return response()->noContent();
        }

        $company = $this->resolveCompanyByPhoneNumberId($phoneNumberId);

        if (! $company) {
            Log::warning('[SF-WA Connect][META] No company mapped', [
                'phone_number_id' => $phoneNumberId,
            ]);

            return response()->noContent();
        }

        if (! (bool) ($company->is_whatsapp_active ?? false)) {
            Log::warning('[SF-WA Connect][META] Company WhatsApp inactive; inbound ignored', [
                'company_id' => $company->id,
                'phone_number_id' => $phoneNumberId,
            ]);

            return response()->noContent();
        }

        Log::info('[SF-WA Connect][META] Company resolved', [
            'company_id' => $company->id,
            'phone_number_id' => $phoneNumberId,
        ]);

        $body = $this->extractMessageBody($msg);

        if ($body === '') {
            $body = '[Non-text message received]';
        }

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

        Log::info('[SF-WA Connect][META] Inbound message dispatched', [
            'company_id' => $company->id,
            'type' => $msg['type'] ?? null,
            'body' => $body,
        ]);

        return response()->noContent();
    }

    protected function resolveCompanyByPhoneNumberId(?string $phoneNumberId): ?Company
    {
        if (blank($phoneNumberId)) {
            return null;
        }

        return Company::query()
            ->where('meta_phone_number_id', trim((string) $phoneNumberId))
            ->first();
    }

    protected function updateCompanyLastEchoAt(Company $company): void
    {
        if (! Schema::hasColumn('companies', 'whatsapp_last_echo_at')) {
            return;
        }

        try {
            $updates = [
                'whatsapp_last_echo_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('companies', 'whatsapp_coexistence_status')) {
                $updates['whatsapp_coexistence_status'] = 'active';
            }

            DB::table('companies')
                ->where('id', $company->id)
                ->update($updates);
        } catch (\Throwable $e) {
            Log::warning('[SF-WA Connect][META][COEXISTENCE] Failed to update last echo timestamp', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function extractEchoCustomerNumber(array $echo): ?string
    {
        return $echo['to']
            ?? $echo['recipient_id']
            ?? $echo['customer']
            ?? $echo['wa_id']
            ?? $echo['contacts'][0]['wa_id']
            ?? null;
    }

    protected function storeUsageLogIfAvailable(
        Company $company,
        ?MessageLog $messageLog,
        ?string $providerMessageId,
        ?string $phoneNumberId,
        array $status
    ): void {
        if (! Schema::hasTable('whatsapp_usage_logs')) {
            return;
        }

        try {
            $pricing = $status['pricing'] ?? [];
            $conversation = $status['conversation'] ?? [];

            DB::table('whatsapp_usage_logs')->insert([
                'company_id' => $company->id,
                'message_log_id' => $messageLog?->id,
                'whatsapp_message_id' => null,
                'provider_message_id' => $providerMessageId,
                'phone_number_id' => $phoneNumberId,
                'direction' => 'out',
                'conversation_category' => $pricing['category'] ?? $conversation['origin']['type'] ?? null,
                'billable' => isset($pricing['billable']) ? (int) (bool) $pricing['billable'] : 0,
                'currency' => $pricing['currency'] ?? 'AED',
                'meta_cost' => $pricing['cost'] ?? null,
                'sayaraforce_charge' => null,
                'pricing_payload' => json_encode([
                    'status' => $status,
                    'pricing' => $pricing,
                    'conversation' => $conversation,
                ]),
                'occurred_at' => isset($status['timestamp'])
                    ? date('Y-m-d H:i:s', (int) $status['timestamp'])
                    : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[SF-WA Connect][META] Failed to store usage log', [
                'company_id' => $company->id,
                'provider_message_id' => $providerMessageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Extract Meta inbound / echo body
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
                    ?? $msg['interactive']['list_reply']['id']
                    ?? ''
                ));
            }
        }

        if ($type === 'image') {
            return '[Image message sent from WhatsApp Business App]';
        }

        if ($type === 'document') {
            return '[Document message sent from WhatsApp Business App]';
        }

        if ($type === 'audio') {
            return '[Audio message sent from WhatsApp Business App]';
        }

        if ($type === 'video') {
            return '[Video message sent from WhatsApp Business App]';
        }

        return '';
    }
}