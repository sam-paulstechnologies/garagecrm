<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppCoexistenceWebhook;
use App\Messaging\Data\NormalizedIncomingMessage;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Services\ProductAdapterRegistry;
use App\Messaging\WhatsApp\WebhookRouter;
use App\Models\Client\Lead;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MetaWhatsAppWebhookController extends Controller
{
    private const STATUS_RANK = [
        'accepted' => 1,
        'sent' => 2,
        'delivered' => 3,
        'read' => 4,
        'failed' => 5,
    ];

    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode !== 'subscribe' || blank($token)) {
            return response('Forbidden', 403);
        }

        $globalToken = trim((string) (
            config('services.meta.whatsapp_verify_token')
            ?: config('services.whatsapp.meta.verify_token')
        ));
        $matchesGlobalToken = $globalToken !== ''
            && hash_equals($globalToken, (string) $token);

        $tenantMatchCount = 0;
        if (! $matchesGlobalToken
            && Schema::hasTable('companies')
            && Schema::hasColumn('companies', 'meta_verify_token')) {
            $tenantMatchCount = Company::query()
                ->where('meta_verify_token', $token)
                ->limit(2)
                ->count();
        }

        if (! $matchesGlobalToken && $tenantMatchCount !== 1) {
            Log::warning('[SF-WA Connect] Webhook verification rejected', [
                'token_present' => true,
                'global_token_configured' => $globalToken !== '',
                'tenant_match_count' => $tenantMatchCount,
            ]);

            return response('Forbidden', 403);
        }

        return response((string) $challenge, 200);
    }

    public function handle(Request $request)
    {
        if ($response = $this->validateSignature($request)) {
            return $response;
        }

        $payload = $request->json()->all();
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response()->noContent();
        }

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ((array) ($entry['changes'] ?? []) as $change) {
                if (! is_array($change) || ! is_array($change['value'] ?? null)) {
                    continue;
                }

                $this->handleChange(
                    (string) ($entry['id'] ?? ''),
                    (string) ($change['field'] ?? 'unknown'),
                    $change['value']
                );
            }
        }

        return response()->noContent();
    }

    private function validateSignature(Request $request)
    {
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $appSecret = (string) (config('services.meta_leads.app_secret') ?: config('services.meta.app_secret'));

        if ($signature === '' || $appSecret === '') {
            Log::warning('[SF-WA Connect] Webhook signature prerequisites missing', [
                'signature_present' => $signature !== '',
                'secret_configured' => $appSecret !== '',
            ]);

            return response('Forbidden', 403);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);
        if (! hash_equals($expected, $signature)) {
            Log::warning('[SF-WA Connect] Webhook signature rejected');

            return response('Invalid signature', 403);
        }

        return null;
    }

    private function handleChange(string $entryWabaId, string $field, array $value): void
    {
        $phoneNumberId = (string) data_get($value, 'metadata.phone_number_id', '');
        $context = app(WebhookRouter::class)->resolve($entryWabaId, $phoneNumberId);
        if (! $context) {
            app(WebhookRouter::class)->quarantine($entryWabaId, $phoneNumberId, $field, $value);
            Log::warning('[SF-WA Connect] Webhook tenant could not be resolved uniquely', [
                'field' => $field,
                'has_waba_id' => $entryWabaId !== '',
                'has_phone_number_id' => filled(data_get($value, 'metadata.phone_number_id')),
            ]);

            return;
        }

        $company = $context->company;

        $company->forceFill(['whatsapp_last_webhook_at' => now()])->save();

        if (in_array($field, ['smb_app_state_sync', 'history'], true)) {
            $event = $this->recordEvent($company, $field, null, $value, $field);
            if ($event?->wasRecentlyCreated) {
                try {
                    ProcessWhatsAppCoexistenceWebhook::dispatch($event->id);
                } catch (\Throwable $exception) {
                    $event->delete();
                    throw $exception;
                }
            }

            return;
        }

        foreach ((array) ($value['statuses'] ?? []) as $status) {
            if (is_array($status)) {
                $this->handleStatus($company, $status);
            }
        }

        $echoes = (array) ($value['smb_message_echoes'] ?? []);
        if ($field === 'smb_message_echoes' && $echoes === []) {
            $echoes = (array) ($value['messages'] ?? []);
        }
        foreach ($echoes as $echo) {
            if (is_array($echo)) {
                $this->handleEcho($company, $value, $echo);
            }
        }

        if ($field !== 'messages') {
            if ($field !== 'smb_message_echoes') {
                $this->recordEvent($company, $field, null, ['keys' => array_keys($value)], 'unsupported');
            }

            return;
        }

        foreach ((array) ($value['messages'] ?? []) as $message) {
            if (is_array($message)) {
                $this->handleInbound($company, $value, $message);
            }
        }
    }

    private function handleInbound(Company $company, array $value, array $message): void
    {
        $messageId = (string) ($message['id'] ?? '');
        $event = $this->recordEvent($company, 'messages', $messageId, [
            'message_type' => $message['type'] ?? 'unknown',
            'timestamp' => $message['timestamp'] ?? null,
        ], 'customer_inbound');

        if (! $event?->wasRecentlyCreated) {
            return;
        }

        $from = $this->digits($message['from'] ?? null);
        if ($from === '') {
            $event->forceFill(['status' => 'ignored', 'error_code' => 'missing_sender', 'processed_at' => now()])->save();
            return;
        }

        $type = (string) ($message['type'] ?? 'unknown');
        $body = $this->messageBody($message);
        $hasMedia = in_array($type, ['image', 'document', 'audio', 'video', 'sticker'], true);
        if ($body === '' && $hasMedia) {
            $body = '['.ucfirst($type).']';
        }
        if ($body === '') {
            $body = '[Unsupported WhatsApp message]';
        }

        $profile = collect((array) ($value['contacts'] ?? []))
            ->first(fn ($contact) => is_array($contact) && $this->digits($contact['wa_id'] ?? null) === $from);

        try {
            $connection = MessagingConnection::query()
                ->where('company_id', $company->id)
                ->where('provider', 'meta_whatsapp')
                ->first();
            $productKey = $connection?->product_key ?: (string) config('messaging.default_product', 'sayaraforce');

            app(ProductAdapterRegistry::class)->for($productKey)->handleIncoming(new NormalizedIncomingMessage(
                companyId: (int) $company->id,
                connectionId: (int) ($connection?->id ?? 0),
                productKey: $productKey,
                provider: 'meta_whatsapp',
                providerMessageId: $messageId,
                from: $from,
                to: $this->digits(data_get($value, 'metadata.display_phone_number')),
                type: $type,
                body: $body,
                profileName: is_array($profile) ? data_get($profile, 'profile.name') : null,
                mediaId: data_get($message, $type.'.id'),
                mediaMimeType: data_get($message, $type.'.mime_type'),
                providerTimestamp: filled($message['timestamp'] ?? null) ? (int) $message['timestamp'] : null,
            ));
        } catch (\Throwable $exception) {
            // Release the receipt so Meta's retry can durably enqueue the message.
            $event->delete();
            throw $exception;
        }

        $company->forceFill(['whatsapp_last_inbound_at' => now()])->save();
        $event->forceFill(['status' => 'dispatched', 'processed_at' => now()])->save();
    }

    private function handleEcho(Company $company, array $value, array $echo): void
    {
        $messageId = (string) ($echo['id'] ?? '');
        $event = $this->recordEvent($company, 'smb_message_echoes', $messageId, [
            'message_type' => $echo['type'] ?? 'unknown',
            'timestamp' => $echo['timestamp'] ?? null,
        ], 'business_app_outbound');

        if (! $event?->wasRecentlyCreated) {
            return;
        }

        $customer = $this->digits($echo['to'] ?? $echo['recipient_id'] ?? $echo['from'] ?? null);
        $lead = $customer === '' || ! Schema::hasTable('leads') ? null : Lead::query()
            ->where('company_id', $company->id)
            ->where('phone_norm', $customer)
            ->latest('id')
            ->first();
        $conversation = $lead && Schema::hasTable('conversations') ? Conversation::query()
            ->where('company_id', $company->id)
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->first() : null;
        $type = (string) ($echo['type'] ?? 'unknown');
        $body = $this->messageBody($echo) ?: '['.ucfirst($type).']';

        MessageLog::query()->firstOrCreate([
            'company_id' => $company->id,
            'provider_message_id' => $messageId !== '' ? $messageId : 'echo-'.$event->event_key,
        ], [
            'lead_id' => $lead?->id,
            'conversation_id' => $conversation?->id,
            'direction' => 'out',
            'channel' => 'whatsapp',
            'source' => 'whatsapp_business_app',
            'from_number' => $this->digits(data_get($value, 'metadata.display_phone_number')),
            'to_number' => $customer !== '' ? $customer : null,
            'body' => $body,
            'provider_status' => 'sent',
            'meta' => [
                'source_kind' => 'business_app_outbound',
                'message_type' => $type,
                'provider_timestamp' => $echo['timestamp'] ?? null,
                'media_id' => data_get($echo, $type.'.id'),
            ],
        ]);

        $company->forceFill(['whatsapp_last_echo_at' => now()])->save();
        $event->forceFill(['status' => 'processed', 'processed_at' => now()])->save();
    }

    private function handleStatus(Company $company, array $status): void
    {
        $messageId = (string) ($status['id'] ?? '');
        $providerStatus = strtolower((string) ($status['status'] ?? ''));
        if ($messageId === '' || $providerStatus === '') {
            return;
        }

        $event = $this->recordEvent($company, 'messages', $messageId.'|'.$providerStatus.'|'.($status['timestamp'] ?? ''), [
            'provider_status' => $providerStatus,
            'timestamp' => $status['timestamp'] ?? null,
        ], 'message_status');
        if (! $event?->wasRecentlyCreated) {
            return;
        }

        $message = MessageLog::query()
            ->where('company_id', $company->id)
            ->where('provider_message_id', $messageId)
            ->latest('id')
            ->first();
        if (! $message) {
            $event->forceFill(['status' => 'ignored', 'error_code' => 'message_not_found', 'processed_at' => now()])->save();
            return;
        }

        $current = strtolower((string) $message->provider_status);
        $incomingRank = self::STATUS_RANK[$providerStatus] ?? 0;
        $currentRank = self::STATUS_RANK[$current] ?? 0;
        if ($providerStatus !== 'failed' && $incomingRank < $currentRank) {
            $event->forceFill(['status' => 'ignored', 'error_code' => 'stale_status', 'processed_at' => now()])->save();
            return;
        }

        $errors = (array) ($status['errors'] ?? []);
        $meta = is_array($message->meta) ? $message->meta : [];
        $message->forceFill([
            'provider_status' => $providerStatus,
            'meta' => array_merge($meta, [
                'wa_status' => $providerStatus,
                'wa_timestamp' => $status['timestamp'] ?? null,
                'wa_error_code' => data_get($errors, '0.code'),
                'wa_error_title' => data_get($errors, '0.title'),
                'wa_error_message' => data_get($errors, '0.message'),
                'last_webhook_received_at' => now()->toIso8601String(),
            ]),
        ])->save();

        $this->storeUsageLogIfAvailable($company, $message, $messageId, $status);
        $event->forceFill(['status' => 'processed', 'processed_at' => now()])->save();
    }

    private function recordEvent(
        Company $company,
        string $field,
        ?string $providerEventId,
        array $payload,
        string $eventType
    ): ?WhatsAppWebhookEvent {
        $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payloadHash = hash('sha256', (string) $canonical);
        $eventKey = hash('sha256', implode('|', [$company->id, $field, $providerEventId ?: $payloadHash]));

        return WhatsAppWebhookEvent::query()->firstOrCreate([
            'event_key' => $eventKey,
        ], [
            'company_id' => $company->id,
            'field' => $field,
            'event_type' => $eventType,
            'provider_event_id' => $providerEventId,
            'payload_hash' => $payloadHash,
            'payload' => $payload,
            'status' => 'pending',
            'occurred_at' => now(),
        ]);
    }

    private function storeUsageLogIfAvailable(Company $company, MessageLog $message, string $messageId, array $status): void
    {
        if (! DB::getSchemaBuilder()->hasTable('whatsapp_usage_logs')) {
            return;
        }

        try {
            DB::table('whatsapp_usage_logs')->insert([
                'company_id' => $company->id,
                'message_log_id' => $message->id,
                'whatsapp_message_id' => null,
                'provider_message_id' => $messageId,
                'phone_number_id' => $company->meta_phone_number_id,
                'direction' => 'out',
                'conversation_category' => data_get($status, 'pricing.category')
                    ?? data_get($status, 'conversation.origin.type'),
                'billable' => (int) (bool) data_get($status, 'pricing.billable', false),
                'currency' => data_get($status, 'pricing.currency', 'AED'),
                'meta_cost' => data_get($status, 'pricing.cost'),
                'sayaraforce_charge' => null,
                'pricing_payload' => json_encode([
                    'provider_status' => $status['status'] ?? null,
                    'error_code' => data_get($status, 'errors.0.code'),
                    'conversation_id_present' => filled(data_get($status, 'conversation.id')),
                ]),
                'occurred_at' => is_numeric($status['timestamp'] ?? null)
                    ? date('Y-m-d H:i:s', (int) $status['timestamp'])
                    : now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('[SF-WA Connect] Usage audit write failed safely', [
                'company_id' => $company->id,
                'exception' => $exception::class,
            ]);
        }
    }

    private function messageBody(array $message): string
    {
        return trim((string) ($message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? data_get($message, ($message['type'] ?? '').'.caption')
            ?? ''));
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }
}
