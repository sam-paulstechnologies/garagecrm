<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwilioWhatsAppService
{
    protected string $sid;
    protected string $token;
    protected string $from;
    protected array $contentSids;
    protected array $textTemplates;

    public function __construct()
    {
        $svc = Config::get('services.whatsapp.twilio', []);
        $this->sid          = (string)($svc['sid']   ?? env('TWILIO_SID'));
        $this->token        = (string)($svc['token'] ?? env('TWILIO_TOKEN'));
        $this->from         = (string)($svc['from']  ?? env('TWILIO_WHATSAPP_FROM')); // e.g. "whatsapp:+14155238886"
        $this->contentSids  = (array)($svc['content_sids'] ?? []);
        $this->textTemplates = Config::get('services.whatsapp.templates_text', []);
    }

    /**
     * Send a templated WhatsApp message via Twilio.
     *
     * @param string $to E.g. "+9715XXXXXXXX" or "whatsapp:+9715XXXXXXXX" (both okay)
     * @param string $templateKey One of: lead_created, opp_confirmed, opp_cancelled, opp_rescheduled, job_completed, generic
     * @param array<int|string, string> $vars Values to inject into template ({{1}}, {{2}}...) or vsprintf order
     * @param array<int, string> $mediaUrls Optional array of absolute URLs for media
     * @return array{ok: bool, sid?: string, error?: string}
     */
    public function sendTemplate(string $to, string $templateKey, array $vars = [], array $mediaUrls = []): array
    {
        $to = $this->normalizeWhatsAppAddress($to);

        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";

        // Prefer Twilio ContentSid if configured for this template
        $contentSid = $this->contentSids[$templateKey] ?? null;

        $payload = [
            'From' => $this->from,
            'To'   => $to,
        ];

        if ($contentSid) {
            $payload['ContentSid']       = $contentSid;
            $payload['ContentVariables'] = $this->makeContentVariables($vars);
        } else {
            // Fallback to plain text template
            $body = $this->renderTextTemplate($templateKey, $vars);
            if ($body === null) {
                return ['ok' => false, 'error' => "Template '{$templateKey}' not found and no ContentSid set."];
            }
            $payload['Body'] = $body;
        }

        // Optional media
        if (!empty($mediaUrls)) {
            // Twilio accepts multiple MediaUrl params
            $payload['MediaUrl'] = array_values($mediaUrls);
        }

        try {
            $resp = Http::asForm()
                ->withBasicAuth($this->sid, $this->token)
                ->post($endpoint, $payload);

            if ($resp->successful()) {
                $sid = (string)($resp->json('sid') ?? '');
                return ['ok' => true, 'sid' => $sid];
            }

            $err = $resp->json('message') ?? $resp->body();
            Log::error('Twilio WhatsApp send failed', ['status' => $resp->status(), 'error' => $err, 'payload' => $payload]);
            return ['ok' => false, 'error' => is_string($err) ? $err : 'Twilio API error'];
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp exception', ['e' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Render a plain-text template with vsprintf-style %s placeholders.
     */
    protected function renderTextTemplate(string $key, array $vars): ?string
    {
        $tpl = $this->textTemplates[$key] ?? null;
        if ($tpl === null) {
            return null;
        }

        // If $vars is associative, flatten to numeric order
        $ordered = $this->normalizeVarsToArray($vars);
        return vsprintf($tpl, $ordered);
    }

    /**
     * Build ContentVariables JSON string for Twilio (expects {"1":"val1","2":"val2"...}).
     */
    protected function makeContentVariables(array $vars): string
    {
        $ordered = $this->normalizeVarsToArray($vars);
        $mapped  = [];
        foreach (array_values($ordered) as $i => $val) {
            $mapped[(string)($i + 1)] = (string)$val;
        }
        return json_encode($mapped, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Accepts "+9715...", "9715...", "whatsapp:+9715..." and normalizes to "whatsapp:+9715..."
     */
    protected function normalizeWhatsAppAddress(string $to): string
    {
        $t = trim($to);
        if (!Str::startsWith($t, 'whatsapp:')) {
            // Ensure it starts with "+"
            if (!Str::startsWith($t, '+')) {
                $t = '+' . ltrim($t, '+');
            }
            $t = 'whatsapp:' . $t;
        }
        return $t;
    }

    /**
     * If assoc array given, convert to numeric array in key order; otherwise return as-is.
     */
    protected function normalizeVarsToArray(array $vars): array
    {
        // If already a numeric array, keep order
        $isAssoc = array_keys($vars) !== range(0, count($vars) - 1);
        if ($isAssoc) {
            // Preserve insertion order
            return array_values($vars);
        }
        return $vars;
    }
}
