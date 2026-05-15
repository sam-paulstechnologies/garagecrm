<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendWhatsAppMessage
{
    public function __construct(private WhatsAppService $wa) {}

    /*
    |--------------------------------------------------------------------------
    | Direct Template Send - legacy/manual/campaign compatible
    |--------------------------------------------------------------------------
    */

    public function sendTemplateBody(string $toE164, mixed $template, array $vars = []): WhatsAppMessage
    {
        $templateName = is_string($template)
            ? $template
            : ($template->provider_template ?? $template->name ?? 'template');

        $companyId = (int) ($vars['company_id'] ?? 0);
        $toE164 = $this->normalizePhone($toE164);

        try {
            $res = $this->wa->sendTemplate(
                toE164: $toE164,
                templateName: $templateName,
                params: $this->varsToParams($vars),
                links: [],
                context: [
                    'company_id' => $companyId,
                    'source' => 'direct_template',
                    'vars' => $vars,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[WA][sendTemplateBody] Send failed', [
                'company_id' => $companyId,
                'to' => $toE164,
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);

            $res = [
                'error' => $e->getMessage(),
                'code' => 'send_exception',
            ];
        }

        return $this->persistOutbound($toE164, $templateName, $res, [
            'company_id' => $companyId,
            'source' => 'direct_template',
            'vars' => $vars,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Plain Text Send
    |--------------------------------------------------------------------------
    */

    public function sendPlain(string $toE164, string $text, ?string $templateAlias = 'plain_text', array $payload = []): WhatsAppMessage
    {
        $companyId = (int) ($payload['company_id'] ?? 0);
        $toE164 = $this->normalizePhone($toE164);

        try {
            $res = $this->wa->sendText(
                toE164: $toE164,
                body: $text,
                context: [
                    'company_id' => $companyId,
                    'source' => 'plain_text',
                ] + $payload
            );
        } catch (\Throwable $e) {
            Log::error('[WA][sendPlain] Send failed', [
                'company_id' => $companyId,
                'to' => $toE164,
                'error' => $e->getMessage(),
            ]);

            $res = [
                'error' => $e->getMessage(),
                'code' => 'send_exception',
            ];
        }

        return $this->persistOutbound($toE164, $templateAlias ?? 'plain_text', $res, $payload + [
            'company_id' => $companyId,
            'body' => $text,
            'source' => 'plain_text',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Manager Notification
    |--------------------------------------------------------------------------
    */

    public function sendPlainToManager(int $companyId, string $text): ?WhatsAppMessage
    {
        $manager = $this->resolveManagerNumber($companyId);

        if (! $manager) {
            Log::warning('[WA][manager_notify] Manager WhatsApp number missing', [
                'company_id' => $companyId,
                'message' => $text,
            ]);

            return null;
        }

        $manager = $this->normalizePhone($manager);

        if (! $this->isLikelyValidPhone($manager)) {
            Log::warning('[WA][manager_notify] Manager WhatsApp number invalid', [
                'company_id' => $companyId,
                'manager' => $manager,
            ]);

            return null;
        }

        try {
            $res = $this->wa->sendText(
                toE164: $manager,
                body: $text,
                context: [
                    'company_id' => $companyId,
                    'reason' => 'manager_notify',
                    'source' => 'manager_notify',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[WA][manager_notify] Send failed', [
                'company_id' => $companyId,
                'manager' => $manager,
                'error' => $e->getMessage(),
            ]);

            $res = [
                'error' => $e->getMessage(),
                'code' => 'manager_notify_exception',
            ];
        }

        return $this->persistOutbound($manager, 'manager_notify', $res, [
            'company_id' => $companyId,
            'body' => $text,
            'reason' => 'manager_notify',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Canonical Event Sender
    |--------------------------------------------------------------------------
    */

    public function fireEvent(int $companyId, string $eventKey, string $toE164, array $vars = []): ?WhatsAppMessage
    {
        $companyId = (int) $companyId;
        $eventKey = trim($eventKey);

        if (! $companyId || $eventKey === '') {
            Log::warning('[WA][fireEvent] Missing company or event key', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
            ]);

            return null;
        }

        if (! $this->isWhatsAppActive($companyId)) {
            Log::info('[WA][fireEvent] WhatsApp automation inactive', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
            ]);

            return null;
        }

        $toE164 = $this->normalizePhone($toE164);

        if ($this->shouldBlockForOptOut($companyId, $eventKey, $toE164, $vars)) {
            $reason = 'Customer has opted out from WhatsApp automation';

            Log::info('[WA][fireEvent] Customer opted out; event blocked', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'to' => $toE164,
                'vars' => $vars,
            ]);

            return $this->persistFailedEvent($companyId, $toE164, 'opted_out', $eventKey, $reason, $vars);
        }

        if (! $this->acquireActionLock($companyId, $eventKey, $toE164, $vars)) {
            Log::info('[WA][fireEvent] Duplicate event blocked by last-action lock', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'to' => $toE164,
                'vars' => $vars,
            ]);

            return null;
        }

        if (! $this->isLikelyValidPhone($toE164)) {
            $reason = 'Customer WhatsApp number missing or invalid';

            Log::warning('[WA][fireEvent] Invalid customer phone', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'to' => $toE164,
                'vars' => $vars,
            ]);

            $this->notifyManagerForCustomerSendFailure(
                companyId: $companyId,
                eventKey: $eventKey,
                toE164: $toE164,
                reason: $reason,
                vars: $vars
            );

            return null;
        }

        $mapping = WhatsAppTemplateMapping::query()
            ->where('company_id', $companyId)
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->with('template')
            ->first();

        if (! $mapping || ! $mapping->template) {
            $reason = 'Missing or inactive WhatsApp template mapping';

            Log::warning('[WA][fireEvent] Missing active template mapping', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'to' => $toE164,
            ]);

            $this->notifyManagerForMissingMapping($companyId, $eventKey, $toE164, $vars, $reason);

            return $this->persistFailedEvent($companyId, $toE164, 'missing_mapping', $eventKey, $reason, $vars);
        }

        $template = $mapping->template;

        if ((int) $template->company_id !== $companyId) {
            $reason = 'Template tenant mismatch';

            Log::error('[WA][fireEvent] Template tenant mismatch', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'template_id' => $template->id,
                'template_company_id' => $template->company_id,
            ]);

            return $this->persistFailedEvent(
                companyId: $companyId,
                toE164: $toE164,
                templateName: $template->provider_template ?: $template->name,
                eventKey: $eventKey,
                reason: $reason,
                vars: $vars,
                templateId: $template->id
            );
        }

        if (! $this->isTemplateSendable($template)) {
            $reason = 'Mapped template is not active/approved';

            Log::warning('[WA][fireEvent] Template is not sendable', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'template_id' => $template->id,
                'template_status' => $template->status,
            ]);

            $this->notifyManagerForMissingMapping($companyId, $eventKey, $toE164, $vars, $reason);

            return $this->persistFailedEvent(
                companyId: $companyId,
                toE164: $toE164,
                templateName: $template->provider_template ?: $template->name,
                eventKey: $eventKey,
                reason: $reason,
                vars: $vars,
                templateId: $template->id
            );
        }

        $activeProvider = $this->resolveProvider($companyId);
        $templateProvider = strtolower(trim((string) ($template->provider ?? '')));

        if ($templateProvider && $activeProvider && $templateProvider !== $activeProvider) {
            $reason = "Template provider mismatch. Active provider is {$activeProvider}, but mapped template provider is {$templateProvider}.";

            Log::warning('[WA][fireEvent] Provider mismatch', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'active_provider' => $activeProvider,
                'template_provider' => $templateProvider,
                'template_id' => $template->id,
                'template_name' => $template->name,
            ]);

            $this->notifyManagerForCustomerSendFailure(
                companyId: $companyId,
                eventKey: $eventKey,
                toE164: $toE164,
                reason: $reason,
                vars: $vars
            );

            return $this->persistFailedEvent(
                companyId: $companyId,
                toE164: $toE164,
                templateName: $template->provider_template ?: $template->name,
                eventKey: $eventKey,
                reason: $reason,
                vars: $vars,
                templateId: $template->id
            );
        }

        $templateName = $template->provider_template ?: $template->name;
        $params = $this->varsToTemplateParams($template, $vars);

        Log::info('[WA][fireEvent] Sending mapped WhatsApp event', [
            'company_id' => $companyId,
            'event_key' => $eventKey,
            'to' => $toE164,
            'template_id' => $template->id,
            'template_name' => $templateName,
            'provider' => $activeProvider,
            'params' => $params,
        ]);

        try {
            $res = $this->wa->sendTemplate(
                toE164: $toE164,
                templateName: $templateName,
                params: $params,
                links: [],
                context: [
                    'company_id' => $companyId,
                    'event_key' => $eventKey,
                    'template_id' => $template->id,
                    'template_name' => $templateName,
                    'provider' => $activeProvider,
                    'vars' => $vars,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[WA][fireEvent] Provider send exception', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'to' => $toE164,
                'template_id' => $template->id,
                'template_name' => $templateName,
                'error' => $e->getMessage(),
            ]);

            $res = [
                'error' => $e->getMessage(),
                'code' => 'provider_exception',
            ];
        }

        $message = $this->persistOutbound($toE164, $templateName, $res, [
            'company_id' => $companyId,
            'event_key' => $eventKey,
            'template_id' => $template->id,
            'template_name' => $templateName,
            'provider' => $activeProvider,
            'vars' => $vars,
            'lead_id' => $vars['lead_id'] ?? null,
            'opportunity_id' => $vars['opportunity_id'] ?? null,
            'booking_id' => $vars['booking_id'] ?? null,
            'job_id' => $vars['job_id'] ?? null,
            'client_id' => $vars['client_id'] ?? null,
        ]);

        if ($message->status === 'failed') {
            $reason = $message->error_message ?: 'WhatsApp provider send failed';

            $this->notifyManagerForCustomerSendFailure(
                companyId: $companyId,
                eventKey: $eventKey,
                toE164: $toE164,
                reason: $reason,
                vars: $vars
            );
        }

        return $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Template Params
    |--------------------------------------------------------------------------
    */

    protected function varsToTemplateParams(WhatsAppTemplate $template, array $vars): array
    {
        $variableNames = [];

        if (is_array($template->variables) && count($template->variables)) {
            $variableNames = $template->variables;
        } elseif (method_exists($template, 'extractVariables')) {
            $variableNames = $template->extractVariables();
        }

        $variableNames = array_values(array_filter(array_unique(array_map(
            fn ($v) => is_string($v) ? trim($v) : null,
            $variableNames
        ))));

        if (! empty($variableNames)) {
            return array_map(function ($key) use ($vars) {
                return (string) data_get($vars, $key, '');
            }, $variableNames);
        }

        return $this->varsToParams($vars);
    }

    protected function varsToParams(array $vars): array
    {
        if (array_is_list($vars)) {
            return array_map('strval', $vars);
        }

        ksort($vars);

        return array_map('strval', array_values($vars));
    }

    /*
    |--------------------------------------------------------------------------
    | Persistence
    |--------------------------------------------------------------------------
    */

    protected function persistFailedEvent(
        int $companyId,
        string $toE164,
        string $templateName,
        string $eventKey,
        string $reason,
        array $vars = [],
        ?int $templateId = null
    ): WhatsAppMessage {
        return $this->persistOutbound($toE164, $templateName, [
            'error' => $reason,
            'code' => 'preflight_failed',
        ], [
            'company_id' => $companyId,
            'event_key' => $eventKey,
            'template_id' => $templateId,
            'template_name' => $templateName,
            'vars' => $vars,
            'lead_id' => $vars['lead_id'] ?? null,
            'opportunity_id' => $vars['opportunity_id'] ?? null,
            'booking_id' => $vars['booking_id'] ?? null,
            'job_id' => $vars['job_id'] ?? null,
            'client_id' => $vars['client_id'] ?? null,
            'reason' => $reason,
        ]);
    }

    protected function persistOutbound(string $toE164, string $template, array|bool $result, array $payload = []): WhatsAppMessage
    {
        $resultArray = is_array($result) ? $result : [];

        $status = isset($resultArray['error']) && $resultArray['error']
            ? 'failed'
            : 'sent';

        $companyId = (int) ($payload['company_id'] ?? 0);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $baseData = [
            'company_id' => $companyId ?: null,
            'provider' => $payload['provider'] ?? $this->resolveProvider($companyId),

            /*
            |--------------------------------------------------------------------------
            | Support both legacy and newer column names
            |--------------------------------------------------------------------------
            */
            'direction' => 'out',
            'to' => $toE164,
            'to_number' => $toE164,
            'from' => null,
            'from_number' => null,

            'template' => $template,
            'template_name' => $template,
            'payload' => $payloadJson,
            'status' => $status,

            'error_code' => $status === 'failed' ? ($resultArray['code'] ?? null) : null,
            'error_message' => $status === 'failed'
                ? ($resultArray['error'] ?? ($resultArray['message'] ?? 'send_failed'))
                : null,

            'lead_id' => $payload['lead_id'] ?? null,
            'opportunity_id' => $payload['opportunity_id'] ?? null,
            'booking_id' => $payload['booking_id'] ?? null,
            'job_id' => $payload['job_id'] ?? null,
            'client_id' => $payload['client_id'] ?? null,
        ];

        if (Schema::hasTable('whatsapp_messages')) {
            $columns = Schema::getColumnListing('whatsapp_messages');
            $data = array_intersect_key($baseData, array_flip($columns));

            /*
            |--------------------------------------------------------------------------
            | Required legacy column fallbacks
            |--------------------------------------------------------------------------
            */
            if (in_array('to', $columns, true) && empty($data['to'])) {
                $data['to'] = $toE164;
            }

            if (in_array('direction', $columns, true) && empty($data['direction'])) {
                $data['direction'] = 'out';
            }

            if (in_array('status', $columns, true) && empty($data['status'])) {
                $data['status'] = $status;
            }

            if (in_array('payload', $columns, true) && empty($data['payload'])) {
                $data['payload'] = $payloadJson;
            }

            if (in_array('company_id', $columns, true) && empty($data['company_id'])) {
                $data['company_id'] = $companyId ?: null;
            }
        } else {
            $data = $baseData;
        }

        return WhatsAppMessage::create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    protected function isWhatsAppActive(int $companyId): bool
    {
        $value = $this->setting($companyId, 'whatsapp.active')
            ?? $this->setting($companyId, 'whatsapp_active');

        if ($value === null || $value === '') {
            return true;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'active'], true);
    }

    protected function resolveProvider(int $companyId): string
    {
        $provider = $this->setting($companyId, 'whatsapp.provider')
            ?? $this->setting($companyId, 'whatsapp_provider')
            ?? $this->setting($companyId, 'wa.provider')
            ?? $this->setting($companyId, 'meta.provider')
            ?? $this->setting($companyId, 'provider.whatsapp')
            ?? 'meta';

        $provider = strtolower(trim((string) $provider));

        return $provider ?: 'meta';
    }

    protected function resolveManagerNumber(int $companyId): ?string
    {
        $value = $this->setting($companyId, 'whatsapp.manager_number')
            ?? $this->setting($companyId, 'whatsapp_manager_number');

        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    protected function setting(int $companyId, string $key): ?string
    {
        if (! $companyId) {
            return null;
        }

        $value = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('group', 'whatsapp')
            ->where('key', $key)
            ->value('value');

        if ($value === null) {
            $value = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');
        }

        return is_string($value) ? trim($value) : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Manager Alerts
    |--------------------------------------------------------------------------
    */

    protected function notifyManagerForMissingMapping(
        int $companyId,
        string $eventKey,
        string $toE164,
        array $vars = [],
        string $reason = 'Missing or inactive WhatsApp template mapping'
    ): void {
        if (str_contains($eventKey, 'manager_alert')) {
            return;
        }

        $text = "WhatsApp mapping issue\n"
            . "Event: {$eventKey}\n"
            . "Customer: {$toE164}\n"
            . "Reason: {$reason}\n"
            . "Name: " . ($vars['name'] ?? $vars['customer_name'] ?? 'N/A') . "\n"
            . "Source: " . ($vars['source'] ?? 'N/A');

        try {
            $this->sendPlainToManager($companyId, $text);
        } catch (\Throwable $e) {
            Log::error('[WA][manager_notify] Failed to notify manager about mapping issue', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifyManagerForCustomerSendFailure(
        int $companyId,
        string $eventKey,
        string $toE164,
        string $reason,
        array $vars = []
    ): void {
        if (str_contains($eventKey, 'manager_alert')) {
            return;
        }

        $text = "Customer WhatsApp failed\n"
            . "Event: {$eventKey}\n"
            . "Customer: {$toE164}\n"
            . "Reason: {$reason}\n"
            . "Name: " . ($vars['name'] ?? $vars['customer_name'] ?? 'N/A') . "\n"
            . "Phone: " . ($vars['phone'] ?? $toE164) . "\n"
            . "Source: " . ($vars['source'] ?? 'N/A') . "\n"
            . "Job: " . ($vars['job_no'] ?? $vars['job_code'] ?? 'N/A') . "\n"
            . "Booking: " . ($vars['booking_id'] ?? 'N/A');

        try {
            $this->sendPlainToManager($companyId, $text);
        } catch (\Throwable $e) {
            Log::error('[WA][manager_notify] Failed to notify manager about customer WhatsApp failure', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'customer' => $toE164,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Opt-out + Last Action Lock
    |--------------------------------------------------------------------------
    |
    | These helpers are intentionally schema-safe. If the durable table/columns
    | are not present yet, the code falls back to cache and does not break the
    | existing WhatsApp flow.
    */

    protected function shouldBlockForOptOut(int $companyId, string $eventKey, string $toE164, array $vars = []): bool
    {
        if (! $companyId) {
            return false;
        }

        if ($this->isTransactionalOrSystemEvent($eventKey)) {
            return false;
        }

        try {
            return $this->customerOptedOut($companyId, $toE164, $vars);
        } catch (\Throwable $e) {
            Log::warning('[WA][opt_out] Opt-out check failed open', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'to' => $toE164,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function customerOptedOut(int $companyId, string $toE164, array $vars = []): bool
    {
        $phoneDigits = preg_replace('/\D+/', '', $toE164) ?: '';
        $leadId = $vars['lead_id'] ?? null;
        $clientId = $vars['client_id'] ?? null;

        if (Schema::hasTable('whatsapp_opt_outs')) {
            $query = DB::table('whatsapp_opt_outs')->where('company_id', $companyId);

            if ($phoneDigits !== '') {
                $query->where(function ($q) use ($toE164, $phoneDigits) {
                    $q->where('phone', $toE164)
                        ->orWhere('phone_e164', $toE164)
                        ->orWhere('phone_norm', $phoneDigits)
                        ->orWhere('mobile', $toE164)
                        ->orWhere('mobile_norm', $phoneDigits);
                });
            }

            if ($query->exists()) {
                return true;
            }
        }

        foreach ([
            ['table' => 'leads', 'id' => $leadId],
            ['table' => 'clients', 'id' => $clientId],
        ] as $target) {
            if (! $target['id'] || ! Schema::hasTable($target['table'])) {
                continue;
            }

            $columns = Schema::getColumnListing($target['table']);
            $optColumns = array_values(array_intersect($columns, [
                'whatsapp_opt_out',
                'is_whatsapp_opted_out',
                'opted_out_whatsapp',
                'wa_opt_out',
                'marketing_opt_out',
            ]));

            if (empty($optColumns)) {
                continue;
            }

            $row = DB::table($target['table'])
                ->where('company_id', $companyId)
                ->where('id', $target['id'])
                ->first($optColumns);

            if (! $row) {
                continue;
            }

            foreach ($optColumns as $column) {
                if ($this->truthy($row->{$column} ?? null)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isTransactionalOrSystemEvent(string $eventKey): bool
    {
        $eventKey = strtolower(trim($eventKey));

        if ($eventKey === '') {
            return false;
        }

        if (str_starts_with($eventKey, 'system.')) {
            return true;
        }

        if (str_contains($eventKey, 'manager')) {
            return true;
        }

        return str_starts_with($eventKey, 'booking.')
            || str_starts_with($eventKey, 'payment.')
            || str_starts_with($eventKey, 'invoice.')
            || in_array($eventKey, [
                'job.done.feedback',
                'feedback.negative.manager_alert',
            ], true);
    }

    protected function acquireActionLock(int $companyId, string $eventKey, string $toE164, array $vars = []): bool
    {
        if (! $companyId || trim($eventKey) === '') {
            return true;
        }

        $ttlSeconds = $this->lockTtlSeconds($eventKey);

        if ($ttlSeconds <= 0) {
            return true;
        }

        $entityType = $this->lockEntityType($vars);
        $entityId = $this->lockEntityId($vars);
        $actionKey = $this->lockActionKey($companyId, $eventKey, $toE164, $vars);
        $lockedUntil = now()->addSeconds($ttlSeconds);

        try {
            if (Schema::hasTable('automation_action_locks')) {
                $existing = DB::table('automation_action_locks')
                    ->where('company_id', $companyId)
                    ->where('entity_type', $entityType)
                    ->where('entity_id', $entityId)
                    ->where('action', $eventKey)
                    ->where('action_key', $actionKey)
                    ->where(function ($query) {
                        $query->whereNull('locked_until')
                            ->orWhere('locked_until', '>', now());
                    })
                    ->exists();

                if ($existing) {
                    return false;
                }

                DB::table('automation_action_locks')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'action' => $eventKey,
                        'action_key' => $actionKey,
                    ],
                    [
                        'locked_until' => $lockedUntil,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('[WA][lock] Durable action lock failed; using cache fallback', [
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'error' => $e->getMessage(),
            ]);
        }

        return Cache::add('wa:last_action:' . $actionKey, true, $ttlSeconds);
    }

    protected function lockTtlSeconds(string $eventKey): int
    {
        $eventKey = strtolower(trim($eventKey));

        if (str_contains($eventKey, 'reminder_24h') || str_contains($eventKey, 'reminder_day_of')) {
            return 7 * 24 * 60 * 60;
        }

        if (str_contains($eventKey, 'feedback')) {
            return 30 * 24 * 60 * 60;
        }

        if (str_starts_with($eventKey, 'lead.') || str_contains($eventKey, 'lead.created')) {
            return 24 * 60 * 60;
        }

        if (str_contains($eventKey, 'manager')) {
            return 10 * 60;
        }

        if (str_starts_with($eventKey, 'booking.')) {
            return 6 * 60 * 60;
        }

        return 5 * 60;
    }

    protected function lockEntityType(array $vars): string
    {
        foreach (['booking_id' => 'booking', 'job_id' => 'job', 'lead_id' => 'lead', 'client_id' => 'client', 'opportunity_id' => 'opportunity'] as $key => $type) {
            if (! empty($vars[$key])) {
                return $type;
            }
        }

        return 'phone';
    }

    protected function lockEntityId(array $vars): string
    {
        foreach (['booking_id', 'job_id', 'lead_id', 'client_id', 'opportunity_id'] as $key) {
            if (! empty($vars[$key])) {
                return (string) $vars[$key];
            }
        }

        return '0';
    }

    protected function lockActionKey(int $companyId, string $eventKey, string $toE164, array $vars): string
    {
        $parts = [
            $companyId,
            $eventKey,
            $this->normalizePhone($toE164),
            $vars['booking_id'] ?? null,
            $vars['job_id'] ?? null,
            $vars['lead_id'] ?? null,
            $vars['client_id'] ?? null,
            $vars['opportunity_id'] ?? null,
        ];

        return sha1(json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'on', 'opted_out'], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    protected function isTemplateSendable(WhatsAppTemplate $template): bool
    {
        $status = strtolower((string) $template->status);

        return in_array($status, ['active', 'approved'], true);
    }

    protected function normalizePhone(?string $phone): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '';
        }

        $phone = preg_replace('/[^\d+]/', '', $phone) ?: '';

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (str_starts_with($phone, '05') && strlen($phone) === 10) {
            return '+971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '5') && strlen($phone) === 9) {
            return '+971' . $phone;
        }

        if (! str_starts_with($phone, '+') && preg_match('/^\d{8,15}$/', $phone)) {
            return '+' . $phone;
        }

        return $phone;
    }

    protected function isLikelyValidPhone(?string $phone): bool
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return false;
        }

        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $phone);
    }
}