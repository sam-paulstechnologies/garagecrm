<?php

namespace App\Services\WhatsApp;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\Shared\WhatsappMessage;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppService
{
    protected string $provider;
    protected HttpClient $http;

    public function __construct()
    {
        // default provider can be overridden per-tenant via company_settings (whatsapp.provider)
        $this->provider = config('services.whatsapp.provider', 'meta'); // 'meta' | 'twilio' | 'gupshup'
        $this->http     = new HttpClient(['timeout' => 15]);
    }

    /**
     * Public API (keep existing signature)
     */
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

    /* =======================================================================
     |  META (Graph API)
     ======================================================================= */
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
            // Minimal link handling – first URL button
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => (string)($links[0] ?? '')]],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'       => $to, // E.164 (no 'whatsapp:' here for Meta)
            'type'     => 'template',
            'template' => [
                'name'       => $template, // must be approved, if you're actually using Meta live
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

            $this->logMessage('meta', 'outbound', $to, $template, $payload, 'sent', null, $context);
            return $body;
        } catch (\Throwable $e) {
            Log::error('[WA][META] send error: '.$e->getMessage(), ['payload' => $payload]);
            $this->logMessage('meta', 'outbound', $to, $template, $payload, 'failed', $e->getMessage(), $context);
            return ['error' => $e->getMessage()];
        }
    }

    /* =======================================================================
     |  TWILIO (Sandbox-safe via text; live-ready)
     ======================================================================= */
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
            return ['error' => 'Twilio SID/token/from missing'];
        }

        $client = new TwilioClient($sid, $token);

        // Twilio requires whatsapp: prefix on BOTH to/from
        $fromWa = $this->wa($from);    // e.g. 'whatsapp:+14155238886'
        $toWa   = $this->wa($toE164);  // e.g. 'whatsapp:+9198...'

        // Sandbox note: templates are NOT supported. We assemble to plain text.
        $body = $this->assembleTemplateAsText($template, $params, $links);
        $mode = str_contains($fromWa, '+14155238886') ? 'sandbox' : 'live';

        try {
            $msg = $client->messages->create($toWa, [
                'from' => $fromWa,
                'body' => $body,
            ]);

            $this->logMessage('twilio', 'outbound', $toE164, $template, [
                'to'   => $toWa,
                'from' => $fromWa,
                'body' => $body,
                'sid'  => $msg->sid,
                'mode' => $mode,
            ], 'sent', null, $context);

            return ['sid' => $msg->sid];
        } catch (\Throwable $e) {
            Log::error('[WA][TWILIO] send error: '.$e->getMessage(), [
                'to' => $toWa, 'from' => $fromWa, 'mode' => $mode,
            ]);
            $this->logMessage('twilio', 'outbound', $toE164, $template, [
                'to'   => $toWa,
                'from' => $fromWa,
                'body' => $body,
                'mode' => $mode,
            ], 'failed', $e->getMessage(), $context);

            return ['error' => $e->getMessage()];
        }
    }

    /* =======================================================================
     |  GUPSHUP (stub)
     ======================================================================= */
    protected function sendGupshupTemplate(string $to, string $template, array $params, array $links, array $context): array
    {
        return ['todo' => 'Implement Gupshup if you switch provider'];
    }

    /* =======================================================================
     |  Extra Public Helper (optional direct text send)
     ======================================================================= */
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
            return ['error' => 'Twilio SID/token/from missing'];
        }

        $client = new TwilioClient($sid, $token);
        $fromWa = $this->wa($from);
        $toWa   = $this->wa($toE164);

        try {
            $msg = $client->messages->create($toWa, [
                'from' => $fromWa,
                'body' => $body,
            ]);

            $this->logMessage('twilio', 'outbound', $toE164, 'text', [
                'to'   => $toWa,
                'from' => $fromWa,
                'body' => $body,
                'sid'  => $msg->sid,
            ], 'sent', null, $context);

            return ['sid' => $msg->sid];
        } catch (\Throwable $e) {
            Log::error('[WA][TWILIO][TEXT] send error: '.$e->getMessage());
            $this->logMessage('twilio', 'outbound', $toE164, 'text', [
                'to'   => $toWa,
                'from' => $fromWa,
                'body' => $body,
            ], 'failed', $e->getMessage(), $context);

            return ['error' => $e->getMessage()];
        }
    }

    /* =======================================================================
     |  Utilities
     ======================================================================= */

    /** Normalize to 'whatsapp:+E164' */
    protected function wa(string $n): string
    {
        $n = trim($n);
        return Str::startsWith($n, 'whatsapp:') ? $n : 'whatsapp:' . $n;
    }

    /** Minimal template→text so jobs can keep using sendTemplate() (sandbox-safe) */
    protected function assembleTemplateAsText(string $template, array $params, array $links): string
    {
        // These are just defaults; your UI/DB can override by passing body via $params
        $library = [
            'lead_acknowledgment_v2' => "Hi 👋 thanks for contacting us. Our manager will call you shortly.",
            'visit_handoff_v1'       => "Got it! Our manager will reach out to you shortly to finalize the visit.",
            'visit_confirmation_v1'  => "All set! ✅ Your visit is scheduled for {0}.",
            'visit_feedback_v1'      => "Hi! Hope your experience was great. Could you share quick feedback?",
            'review_request_v1'      => "That's awesome to hear! 🙌 Would you mind leaving us a quick Google review?",
            'apology_response_v1'    => "We're really sorry to hear that. Our manager will contact you to make it right.",
            // generic names still work:
            'ack_lead'               => "Hey! 👋 We’ve received your details. Our manager will call you shortly.",
            'ask_make_model'         => "Could you please share your car’s *Make & Model*?",
            'manager_call_lead'      => "Heads up 👤 Lead needs attention: Name: {0}, Phone: {1}, Source: {2}. Reason: {3}",
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

    /** Persist outbound message record (safe; errors ignored) */
    protected function logMessage(
        string $provider,
        string $direction,
        string $to,
        string $template,
        array|string $payload,
        string $status,
        ?string $error,
        array $context
    ): void {
        try {
            WhatsappMessage::create([
                'provider'        => $provider,
                'direction'       => $direction,
                'to_number'       => $to,
                'template'        => $template,
                'payload'         => is_string($payload) ? $payload : json_encode($payload),
                'status'          => $status,
                'error_message'   => $error,
                'lead_id'         => $context['lead_id'] ?? null,
                'opportunity_id'  => $context['opportunity_id'] ?? null,
                'job_id'          => $context['job_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WA] logMessage failed: '.$e->getMessage());
        }
    }

    /**
     * Get a single setting with support for:
     *  - column on single row (company_col)
     *  - key/value rows (kv_keys)
     *  - config() fallback
     *  - decrypt if encrypted
     */
    protected function getSetting(?int $companyId, array $map): ?string
    {
        $val = null;

        if ($companyId) {
            // 1) Column on company_settings (single row per company)
            try {
                $row = DB::table('company_settings')
                    ->where('company_id', $companyId)
                    ->first();

                if ($row && !empty($map['company_col']) && isset($row->{$map['company_col']})) {
                    $raw = (string) $row->{$map['company_col']};
                    // try best-effort decryption if you store a global is_encrypted flag per row
                    $isEncrypted = property_exists($row, 'is_encrypted') ? ((int) $row->is_encrypted === 1) : false;
                    $val = $this->maybeDecrypt($raw, $isEncrypted);
                    if ($this->hasValue($val)) return $val;
                }
            } catch (\Throwable $e) {
                // ignore; fallback
            }

            // 2) Key/Value rows
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
                } catch (\Throwable $e) {
                    // ignore; fallback
                }
            }
        }

        // 3) Config fallback
        $cfg = $map['config'] ? config($map['config']) : null;
        return $this->hasValue($cfg) ? trim((string) $cfg) : null;
    }

    protected function maybeDecrypt(?string $v, bool $encrypted): ?string
    {
        if (!$this->hasValue($v)) return null;
        $v = trim((string)$v);

        // if explicitly flagged, or looks like a typical Laravel encrypted blob
        if ($encrypted || Str::startsWith($v, 'eyJpdiI6')) {
            try { return Crypt::decryptString($v); } catch (\Throwable $e) { /* fall through */ }
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
