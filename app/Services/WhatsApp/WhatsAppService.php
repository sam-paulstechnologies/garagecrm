<?php

namespace App\Services\WhatsApp;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\WhatsApp\WhatsAppMessage;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppService
{
    protected string $provider;
    protected HttpClient $http;

    public function __construct()
    {
        $this->provider = config('services.whatsapp.provider', 'meta'); // 'meta' | 'twilio' | 'gupshup'
        $this->http     = new HttpClient(['timeout' => 15]);
    }

    /** Public API */
    public function sendTemplate(
        string $toE164,
        string $templateName,
        array $params = [],
        array $links = [],
        array $context = []
    ): array {
        $tenantProvider = $this->getTenantProvider($context['company_id'] ?? null);
        $provider = $tenantProvider ?: $this->provider;

        return match ($provider) {
            'meta'   => $this->sendMetaTemplate($toE164, $templateName, $params, $links, $context),
            'twilio' => $this->sendTwilioTemplate($toE164, $templateName, $params, $links, $context),
            'gupshup'=> $this->sendGupshupTemplate($toE164, $templateName, $params, $links, $context),
            default  => ['error' => 'Unknown provider'],
        };
    }

    /* ======================== META ======================== */
    protected function sendMetaTemplate(string $to, string $template, array $params, array $links, array $context): array
    {
        $companyId = $context['company_id'] ?? null;

        $phoneId = $this->getSetting($companyId, [
            'company_col' => 'meta_phone_id',
            'kv_keys'     => ['whatsapp.meta.phone_id', 'meta.phone_id', 'meta.phone_number_id', 'whatsapp.meta.phone_number_id'],
            'config'      => 'services.whatsapp.meta.phone_id',
        ]);

        $token = $this->getSetting($companyId, [
            'company_col' => 'meta_token',
            'kv_keys'     => ['whatsapp.meta.token', 'meta.token', 'meta.access_token'],
            'config'      => 'services.whatsapp.meta.token',
        ]);

        if (!$phoneId || !$token) {
            $this->logWa(provider: 'meta', to: $to, status: 'failed', companyId: $companyId, payload: [
                'error' => 'Meta phone_id/token missing',
                'template' => $template,
                'params'   => $params,
                'links'    => $links,
            ]);
            return ['error' => 'Meta phone_id/token missing'];
        }

        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn($p) => ['type' => 'text', 'text' => (string)$p],
                    $params
                ),
            ];
        }
        if (!empty($links)) {
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => (string)($links[0] ?? '')]],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'       => $to,
            'type'     => 'template',
            'template' => [
                'name'       => $template,
                'language'   => ['code' => 'en'],
                'components' => $components,
            ],
        ];

        $url = "https://graph.facebook.com/v20.0/{$phoneId}/messages";

        try {
            $res  = $this->http->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $res->getBody(), true) ?? ['ok' => true];

            $externalId = $body['messages'][0]['id'] ?? null;

            $this->logWa(
                provider:   'meta',
                to:         $to,
                status:     'sent',
                companyId:  $companyId,
                templateId: null,
                externalId: $externalId,
                providerMsgId: $externalId,
                payload:    [
                    'request'  => $payload,
                    'response' => $body,
                    'template' => $template,
                ]
            );

            return $body;
        } catch (\Throwable $e) {
            Log::error('[WA][META] send error: '.$e->getMessage(), ['payload' => $payload]);

            $this->logWa(
                provider:  'meta',
                to:        $to,
                status:    'failed',
                companyId: $companyId,
                payload:   ['request' => $payload, 'error' => $e->getMessage(), 'template' => $template]
            );

            return ['error' => $e->getMessage()];
        }
    }

    /* ======================== TWILIO ======================== */
    protected function sendTwilioTemplate(string $toE164, string $template, array $params, array $links, array $context): array
    {
        $companyId = $context['company_id'] ?? null;

        $sid = $this->getSetting($companyId, [
            'company_col' => 'twilio_sid',
            'kv_keys'     => ['twilio.sid', 'twilio.account_sid'],
            'config'      => 'services.whatsapp.twilio.sid',
        ]);

        $token = $this->getSetting($companyId, [
            'company_col' => 'twilio_token',
            'kv_keys'     => ['twilio.token', 'twilio.auth_token'],
            'config'      => 'services.whatsapp.twilio.token',
        ]);

        $from = $this->getSetting($companyId, [
            'company_col' => 'twilio_whatsapp_from',
            'kv_keys'     => ['twilio.whatsapp_from'],
            'config'      => 'services.whatsapp.twilio.from',
        ]);

        if (!$sid || !$token || !$from) {
            $this->logWa('twilio', $toE164, 'failed', $companyId, [
                'error' => 'Twilio SID/token/from missing',
                'template' => $template,
                'params'   => $params,
                'links'    => $links,
            ]);
            return ['error' => 'Twilio SID/token/from missing'];
        }

        $client = new TwilioClient($sid, $token);

        $fromWa = $this->wa($from);
        $toWa   = $this->wa($toE164);

        $body = $this->assembleTemplateAsText($template, $params, $links);
        $mode = str_contains($fromWa, '+14155238886') ? 'sandbox' : 'live';

        try {
            $msg = $client->messages->create($toWa, [
                'from'           => $fromWa,
                'body'           => $body,
                'statusCallback' => route('webhooks.twilio.status'),
            ]);

            $this->logWa(
                provider:   'twilio',
                to:         $toE164,
                status:     'sent',
                companyId:  $companyId,
                externalId: $msg->sid ?? null,
                providerMsgId: $msg->sid ?? null,
                payload:    [
                    'to'   => $toWa,
                    'from' => $fromWa,
                    'body' => $body,
                    'mode' => $mode,
                    'template' => $template,
                ]
            );

            return ['sid' => $msg->sid];
        } catch (\Throwable $e) {
            Log::error('[WA][TWILIO] send error: '.$e->getMessage(), [
                'to' => $toWa, 'from' => $fromWa, 'mode' => $mode,
            ]);

            $this->logWa(
                provider:  'twilio',
                to:        $toE164,
                status:    'failed',
                companyId: $companyId,
                payload:   [
                    'to' => $toWa, 'from' => $fromWa, 'body' => $body, 'mode' => $mode,
                    'error' => $e->getMessage(),
                    'template' => $template,
                ]
            );

            return ['error' => $e->getMessage()];
        }
    }

    /* ======================== GUPSHUP (stub) ======================== */
    protected function sendGupshupTemplate(string $to, string $template, array $params, array $links, array $context): array
    {
        $this->logWa('gupshup', $to, 'failed', $context['company_id'] ?? null, [
            'todo' => 'Implement Gupshup',
            'template' => $template,
            'params'   => $params,
            'links'    => $links,
        ]);
        return ['todo' => 'Implement Gupshup if you switch provider'];
    }

    /* =================== Direct text (Twilio) =================== */
    public function sendText(string $toE164, string $body, array $context = []): array
    {
        $companyId = $context['company_id'] ?? null;

        $sid = $this->getSetting($companyId, [
            'company_col' => 'twilio_sid',
            'kv_keys'     => ['twilio.sid', 'twilio.account_sid'],
            'config'      => 'services.whatsapp.twilio.sid',
        ]);
        $token = $this->getSetting($companyId, [
            'company_col' => 'twilio_token',
            'kv_keys'     => ['twilio.token', 'twilio.auth_token'],
            'config'      => 'services.whatsapp.twilio.token',
        ]);
        $from = $this->getSetting($companyId, [
            'company_col' => 'twilio_whatsapp_from',
            'kv_keys'     => ['twilio.whatsapp_from'],
            'config'      => 'services.whatsapp.twilio.from',
        ]);

        if (!$sid || !$token || !$from) {
            $this->logWa('twilio', $toE164, 'failed', $companyId, [
                'error' => 'Twilio SID/token/from missing for sendText',
                'body'  => $body,
            ]);
            return ['error' => 'Twilio SID/token/from missing'];
        }

        $client = new TwilioClient($sid, $token);
        $fromWa = $this->wa($from);
        $toWa   = $this->wa($toE164);

        try {
            $msg = $client->messages->create($toWa, [
                'from'           => $fromWa,
                'body'           => $body,
                'statusCallback' => route('webhooks.twilio.status'),
            ]);

            $this->logWa(
                provider:   'twilio',
                to:         $toE164,
                status:     'sent',
                companyId:  $companyId,
                externalId: $msg->sid ?? null,
                providerMsgId: $msg->sid ?? null,
                payload:    [
                    'to'   => $toWa,
                    'from' => $fromWa,
                    'body' => $body,
                    'template' => 'text',
                ]
            );

            return ['sid' => $msg->sid];
        } catch (\Throwable $e) {
            Log::error('[WA][TWILIO][TEXT] send error: '.$e->getMessage());

            $this->logWa('twilio', $toE164, 'failed', $companyId, [
                'to' => $toWa, 'from' => $fromWa, 'body' => $body, 'error' => $e->getMessage(),
                'template' => 'text',
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    /* ====================== Utilities ====================== */

    protected function wa(string $n): string
    {
        $n = trim($n);
        return Str::startsWith($n, 'whatsapp:') ? $n : 'whatsapp:' . $n;
    }

    /** Public for jobs to reuse (sandbox + live) */
    public function assembleTemplateAsText(string $template, array $params, array $links): string
    {
        $library = [
            'lead_acknowledgment_v2' => "Hi 👋 thanks for contacting us. Our manager will call you shortly.",
            'visit_handoff_v1'       => "Got it! Our manager will reach out to you shortly to finalize the visit.",
            'visit_confirmation_v1'  => "All set! ✅ Your visit is scheduled for {0}.",
            'visit_feedback_v1'      => "Hi! Hope your experience was great. Could you share quick feedback?",
            'review_request_v1'      => "That's awesome to hear! 🙌 Would you mind leaving us a quick Google review?",
            'apology_response_v1'    => "We're really sorry to hear that. Our manager will contact you to make it right.",
            'ack_lead'               => "Hey! 👋 We’ve received your details. Our manager will call you shortly.",
            'ask_make_model'         => "Could you please share your car’s *Make & Model*?",
            'manager_call_lead'      => "Heads up 👤 Lead needs attention: Name: {0}, Phone: {1}, Source: {2}. Reason: {3}",
            'booking_confirmation'   => "Your booking is confirmed for {0} ({1}). See you soon! 🚗",
            'visit_reminder_v1'      => "Reminder ⏰ Your booking is for {0} ({1}). Reply if you need to reschedule.",
        ];

        $body = $library[$template] ?? $template;

        foreach ($params as $i => $val) {
            $body = str_replace('{'.$i.'}', (string) $val, $body);
        }

        if (!empty($links)) {
            $body .= "\n";
            foreach ($links as $label => $url) {
                $body .= (is_string($label) ? "{$label}: {$url}" : (string)$url) . "\n";
            }
            $body = rtrim($body);
        }

        return $body;
    }

    /** Persist to whatsapp_messages */
    protected function logWa(
        string $provider,
        string $to,
        string $status,
        ?int $companyId = null,
        array $payload = [],
        ?int $templateId = null,
        ?string $externalId = null,
        ?string $providerMsgId = null
    ): void {
        try {
            WhatsAppMessage::create([
                'company_id'          => $companyId,
                'campaign_id'         => null,
                'template_id'         => $templateId,
                'to'                  => $to,
                'direction'           => 'out',
                'status'              => $status,
                'external_id'         => $externalId,
                'provider_message_id' => $providerMsgId ?? $externalId,
                'error_message'       => ($status === 'failed') ? ($payload['error'] ?? null) : null,
                'payload'             => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WA] logWa failed: '.$e->getMessage());
        }
    }

    /** Get a single setting with fallback chain */
    protected function getSetting(?int $companyId, array $map): ?string
    {
        $val = null;

        if ($companyId) {
            try {
                $row = DB::table('company_settings')
                    ->where('company_id', $companyId)
                    ->first();

                if ($row && !empty($map['company_col']) && isset($row->{$map['company_col']})) {
                    $raw = (string) $row->{$map['company_col']};
                    $isEncrypted = property_exists($row, 'is_encrypted') ? ((int) $row->is_encrypted === 1) : false;
                    $val = $this->maybeDecrypt($raw, $isEncrypted);
                    if ($this->hasValue($val)) return $val;
                }
            } catch (\Throwable $e) { /* ignore; fallback */ }

            if (!empty($map['kv_keys']) && is_array($map['kv_keys'])) {
                try {
                    $rows = DB::table('company_settings')
                        ->select(['key', 'value', 'is_encrypted'])
                        ->where('company_id', $companyId)
                        ->whereIn('key', $map['kv_keys'])
                        ->get();

                    foreach ($rows as $r) {
                        $raw = (string) $r->value;
                        $dec = $this->maybeDecrypt($raw, (int) ($r->is_encrypted ?? 0) === 1);
                        if ($this->hasValue($dec)) return $dec;
                    }
                } catch (\Throwable $e) { /* ignore; fallback */ }
            }
        }

        $cfg = $map['config'] ? config($map['config']) : null;
        return $this->hasValue($cfg) ? trim((string) $cfg) : null;
    }

    protected function maybeDecrypt(?string $v, bool $encrypted): ?string
    {
        if (!$this->hasValue($v)) return null;
        $v = trim((string)$v);

        if ($encrypted || Str::startsWith($v, 'eyJpdiI6')) {
            try { return Crypt::decryptString($v); } catch (\Throwable) { /* fall through */ }
        }
        return $v;
    }

    protected function hasValue($v): bool
    {
        return isset($v) && trim((string)$v) !== '';
    }

    /** Optional: per-tenant provider override (company_settings key `whatsapp.provider`) */
    protected function getTenantProvider(?int $companyId): ?string
    {
        if (!$companyId) return null;
        try {
            $pv = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->whereIn('key', ['whatsapp.provider', 'wa.provider'])
                ->value('value');

            $pv = is_string($pv) ? strtolower(trim($pv)) : null;
            return in_array($pv, ['meta', 'twilio', 'gupshup'], true) ? $pv : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
