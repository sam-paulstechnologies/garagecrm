<?php

namespace App\Services\WhatsApp;

use App\Models\CompanySetting;
use App\Models\Shared\WhatsappMessage;
use Illuminate\Support\Facades\Log;

/**
 * Compatibility wrapper around WhatsAppService.
 *
 * Why this exists:
 * - Some legacy code may call SendWhatsAppMessage::{sendPlain, sendTemplateBody, fireEvent}
 * - We forward to WhatsAppService so provider selection + auditing stay centralized.
 *
 * NOTE: We do NOT depend on env() here. Tenant context comes from:
 * - company_settings rows (e.g., whatsapp.manager_number), and
 * - WhatsAppService which reads config/services.php and DB as needed.
 */
class SendWhatsAppMessage
{
    public function __construct(private WhatsAppService $wa) {}

    /**
     * Send a pre-approved template by name with simple {{name}} replacements.
     * $vars is an associative array: ['name' => 'Sam', ...] -> becomes positional params.
     */
    public function sendTemplateBody(string $toE164, /* string|object */ $template, array $vars = []): WhatsappMessage
    {
        $templateName = is_string($template) ? $template : (method_exists($template, 'name') ? $template->name : 'template');

        $params = $this->varsToParams($vars);

        $res = $this->wa->sendTemplate(
            toE164:       $toE164,
            templateName: $templateName,
            params:       $params,
            links:        [],
            context:      []  // caller can pass richer context if needed
        );

        return $this->persistOutbound($toE164, $templateName, $res, ['vars' => $vars]);
    }

    /**
     * Legacy "plain text" send.
     * For Meta/Gupshup providers, freeform text is not allowed outside a session:
     * we emulate by using a tenant-defined 'plain_text' template if available.
     *
     * If your provider is Twilio and you truly need freeform text, add that path
     * inside WhatsAppService::sendTemplate (or a dedicated method) so the logic remains centralized.
     */
    public function sendPlain(string $toE164, string $text, ?string $templateAlias = 'plain_text', array $payload = []): WhatsappMessage
    {
        // Best-effort: call a simple template that renders body as {{1}}
        $res = $this->wa->sendTemplate(
            toE164:       $toE164,
            templateName: $templateAlias ?? 'plain_text',
            params:       [$text],
            links:        [],
            context:      $payload
        );

        return $this->persistOutbound($toE164, $templateAlias ?? 'plain_text', $res, $payload + ['body' => $text]);
    }

    /**
     * Notify the manager for a tenant using company_settings key:
     *   whatsapp.manager_number  → E.164 phone number
     */
    public function sendPlainToManager(int $companyId, string $text): ?WhatsappMessage
    {
        $manager = $this->resolveManagerNumber($companyId);
        if (! $manager) return null;

        // Route as 'plain_text' template to keep provider-agnostic
        $res = $this->wa->sendTemplate(
            toE164:       $manager,
            templateName: 'plain_text',
            params:       [$text],
            links:        [],
            context:      ['company_id' => $companyId, 'reason' => 'manager_notify']
        );

        return $this->persistOutbound($manager, 'manager_notify', $res, ['body' => $text, 'company_id' => $companyId]);
    }

    /**
     * Fire by event mapping if you keep those in DB; this is a no-op stub here.
     * Prefer your TriggerEngine + CampaignDispatcher for events → templates.
     */
    public function fireEvent(int $companyId, string $eventKey, string $toE164, array $vars = []): ?WhatsappMessage
    {
        // Delegate selection logic to your automation layer.
        Log::info('[WA][fireEvent] Delegate this to TriggerEngine/Campaigns', compact('companyId','eventKey','toE164'));
        return null;
    }

    /* -----------------------
     | Helpers
     ----------------------- */

    /** Convert associative vars to the positional list expected by your templates. */
    protected function varsToParams(array $vars): array
    {
        // If the caller passed a numeric-indexed array already, keep it.
        if (array_is_list($vars)) return array_map('strval', $vars);

        // Else, sort by key for deterministic order and return values
        ksort($vars);
        return array_map('strval', array_values($vars));
    }

    /** Persist an outbound message row using your Shared\WhatsappMessage model. */
    protected function persistOutbound(string $toE164, string $template, array $result, array $payload = []): WhatsappMessage
    {
        $status = isset($result['error']) && $result['error'] ? 'failed' : 'sent';

        return WhatsappMessage::create([
            'provider'       => config('services.whatsapp.provider', 'meta'),
            'direction'      => 'outbound',
            'to_number'      => $toE164,
            'from_number'    => null, // your WhatsAppService can store the actual sender if needed
            'template'       => $template,
            'payload'        => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'status'         => $status,
            'error_code'     => $status === 'failed' ? ($result['code']   ?? null) : null,
            'error_message'  => $status === 'failed' ? ($result['error']  ?? ($result['message'] ?? 'send_failed')) : null,
            'lead_id'        => $payload['lead_id']        ?? null,
            'opportunity_id' => $payload['opportunity_id'] ?? null,
            'job_id'         => $payload['job_id']         ?? null,
        ]);
    }

    protected function resolveManagerNumber(int $companyId): ?string
    {
        $val = \DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', 'whatsapp.manager_number')
            ->value('value');

        $val = is_string($val) ? trim($val) : null;
        return $val !== '' ? $val : null;
    }
}
