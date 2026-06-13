<?php

namespace App\Console\Commands;

use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncRetentionWhatsAppTemplates extends Command
{
    protected $signature = 'retention:sync-templates
        {--dry-run : Preview changes without writing}
        {--apply : Write local template and mapping records}
        {--status=pending : Local template status: pending, active, approved, or rejected}
        {--company_id= : Limit sync to one company}
        {--show-records : Show record-level actions}';

    protected $description = 'Sync local WhatsApp retention template records and mappings without sending messages.';

    private const ALLOWED_STATUSES = ['pending', 'active', 'approved', 'rejected'];

    public function handle(): int
    {
        $mode = $this->option('apply') ? 'apply' : 'dry-run';
        $status = strtolower(trim((string) $this->option('status')));

        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            $this->error('Invalid --status. Use pending, active, approved, or rejected.');
            return self::FAILURE;
        }

        $companyIds = $this->companyIds();

        if (empty($companyIds)) {
            $this->warn('No companies found to sync.');
            return self::SUCCESS;
        }

        $storedStatus = $this->storedStatus($status);
        $summary = $this->emptySummary($mode, $status, $storedStatus);
        $records = [];

        foreach ($companyIds as $companyId) {
            $summary['companies_checked']++;

            foreach ($this->templates() as $definition) {
                $templateResult = $this->syncTemplate((int) $companyId, $definition, $status, $storedStatus, $mode);
                $summary[$templateResult['summary_key']]++;
                $records[] = $templateResult['record'];

                $templateId = $templateResult['template_id'];

                foreach ($definition['event_keys'] as $eventKey) {
                    $mappingResult = $this->syncMapping((int) $companyId, $eventKey, $templateId, $mode);
                    $summary[$mappingResult['summary_key']]++;
                    $records[] = $mappingResult['record'];
                }
            }
        }

        $summary['records'] = $records;
        $this->renderSummary($summary);

        return self::SUCCESS;
    }

    private function companyIds(): array
    {
        $companyId = $this->option('company_id');

        if ($companyId !== null && $companyId !== '') {
            return [(int) $companyId];
        }

        return Company::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function emptySummary(string $mode, string $status, string $storedStatus): array
    {
        return [
            'mode' => $mode,
            'requested_status' => $status,
            'stored_status' => $storedStatus,
            'companies_checked' => 0,
            'templates_to_create' => 0,
            'templates_created' => 0,
            'templates_reused' => 0,
            'templates_to_update' => 0,
            'templates_updated' => 0,
            'templates_preserved' => 0,
            'mappings_to_create' => 0,
            'mappings_created' => 0,
            'mappings_reused' => 0,
            'mappings_to_update' => 0,
            'mappings_updated' => 0,
        ];
    }

    private function templates(): array
    {
        return [
            [
                'name' => 'retention_general_service_due_v1',
                'label' => 'Retention General Service Due',
                'body' => 'Hi {{1}}, your {{2}} may be due for a general service check. Reply YES and our team will help schedule a convenient time.',
                'event_keys' => ['retention_general_service_due', 'retention.general_service'],
            ],
            [
                'name' => 'retention_oil_change_due_v1',
                'label' => 'Retention Oil Change Due',
                'body' => 'Hi {{1}}, your {{2}} may be due for an oil change. Reply YES and our team will help you book a suitable time.',
                'event_keys' => ['retention_oil_change_due', 'retention.oil_service'],
            ],
            [
                'name' => 'retention_battery_follow_up_v1',
                'label' => 'Retention Battery Follow-up',
                'body' => 'Hi {{1}}, it may be a good time to check the battery health of your {{2}}. Reply YES and our team will help arrange a quick check.',
                'event_keys' => ['retention_battery_follow_up', 'retention.battery'],
            ],
            [
                'name' => 'retention_insurance_expiry_v1',
                'label' => 'Retention Insurance Expiry',
                'body' => 'Hi {{1}}, your vehicle insurance may be due for renewal soon. Reply YES if you would like our team to assist you.',
                'event_keys' => ['retention_insurance_expiry'],
            ],
            [
                'name' => 'retention_mulkia_renewal_v1',
                'label' => 'Retention Mulkia Renewal',
                'body' => 'Hi {{1}}, your vehicle registration renewal may be coming up soon. Reply YES if you would like our team to assist you.',
                'event_keys' => ['retention_mulkia_renewal', 'retention.mulkia_renewal'],
            ],
        ];
    }

    private function syncTemplate(int $companyId, array $definition, string $requestedStatus, string $storedStatus, string $mode): array
    {
        $template = WhatsAppTemplate::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($definition) {
                $query->where('name', $definition['name'])
                    ->orWhere('provider_template', $definition['name']);
            })
            ->first();

        if (! $template) {
            if ($mode === 'apply') {
                $template = WhatsAppTemplate::create($this->templatePayload($companyId, $definition, $storedStatus));

                return [
                    'summary_key' => 'templates_created',
                    'template_id' => $template->id,
                    'record' => $this->record('template', null, $definition['name'], 'created', $companyId, 'Created local template.'),
                ];
            }

            return [
                'summary_key' => 'templates_to_create',
                'template_id' => null,
                'record' => $this->record('template', null, $definition['name'], 'would_create', $companyId, 'Would create local template.'),
            ];
        }

        $payload = $this->templatePayload($companyId, $definition, $storedStatus);
        $updates = $this->templateUpdates($template, $payload, $requestedStatus);

        if (! empty($updates)) {
            if ($mode === 'apply') {
                $template->update($updates);

                return [
                    'summary_key' => 'templates_updated',
                    'template_id' => $template->id,
                    'record' => $this->record('template', $template->id, $definition['name'], 'updated', $companyId, 'Updated local template metadata.'),
                ];
            }

            return [
                'summary_key' => 'templates_to_update',
                'template_id' => $template->id,
                'record' => $this->record('template', $template->id, $definition['name'], 'would_update', $companyId, 'Would update local template metadata.'),
            ];
        }

        return [
            'summary_key' => 'templates_reused',
            'template_id' => $template->id,
            'record' => $this->record('template', $template->id, $definition['name'], 'reused', $companyId, 'Template already matches safe sync data.'),
        ];
    }

    private function templatePayload(int $companyId, array $definition, string $status): array
    {
        $payload = [
            'company_id' => $companyId,
            'name' => $definition['name'],
            'provider_template' => $definition['name'],
            'language' => 'en',
            'category' => 'Marketing',
            'body' => $definition['body'],
            'status' => $status,
            'provider' => config('services.whatsapp.provider', 'meta'),
            'last_synced_at' => now(),
            'variables' => ['1', '2'],
        ];

        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn('whatsapp_templates', $key))
            ->all();
    }

    private function storedStatus(string $requestedStatus): string
    {
        return match ($requestedStatus) {
            'active', 'approved' => 'active',
            'rejected' => 'archived',
            default => 'draft',
        };
    }

    private function templateUpdates(WhatsAppTemplate $template, array $payload, string $requestedStatus): array
    {
        $updates = [];

        foreach ($payload as $key => $value) {
            if ($key === 'company_id') {
                continue;
            }

            if ($key === 'status' && in_array(strtolower((string) $template->status), ['active', 'approved'], true) && $requestedStatus === 'pending') {
                continue;
            }

            if ($key === 'last_synced_at') {
                continue;
            }

            if ($key === 'variables') {
                if ((array) $template->variables !== (array) $value) {
                    $updates[$key] = $value;
                }

                continue;
            }

            if ((string) ($template->{$key} ?? '') !== (string) $value) {
                $updates[$key] = $value;
            }
        }

        if (! empty($updates) && Schema::hasColumn('whatsapp_templates', 'last_synced_at')) {
            $updates['last_synced_at'] = now();
        }

        return $updates;
    }

    private function syncMapping(int $companyId, string $eventKey, ?int $templateId, string $mode): array
    {
        $mapping = WhatsAppTemplateMapping::query()
            ->where('company_id', $companyId)
            ->where('event_key', $eventKey)
            ->first();

        if (! $mapping) {
            if ($mode === 'apply') {
                $mapping = WhatsAppTemplateMapping::create([
                    'company_id' => $companyId,
                    'event_key' => $eventKey,
                    'template_id' => $templateId,
                    'is_active' => ! empty($templateId),
                ]);

                return [
                    'summary_key' => 'mappings_created',
                    'record' => $this->record('mapping', $mapping->id, $eventKey, 'created', $companyId, 'Created local mapping.'),
                ];
            }

            return [
                'summary_key' => 'mappings_to_create',
                'record' => $this->record('mapping', null, $eventKey, 'would_create', $companyId, 'Would create local mapping.'),
            ];
        }

        $updates = [];

        if ($templateId && (int) $mapping->template_id !== (int) $templateId) {
            $updates['template_id'] = $templateId;
        }

        if ($templateId && ! (bool) $mapping->is_active) {
            $updates['is_active'] = true;
        }

        if (! empty($updates)) {
            if ($mode === 'apply') {
                $mapping->update($updates);

                return [
                    'summary_key' => 'mappings_updated',
                    'record' => $this->record('mapping', $mapping->id, $eventKey, 'updated', $companyId, 'Updated local mapping.'),
                ];
            }

            return [
                'summary_key' => 'mappings_to_update',
                'record' => $this->record('mapping', $mapping->id, $eventKey, 'would_update', $companyId, 'Would update local mapping.'),
            ];
        }

        return [
            'summary_key' => 'mappings_reused',
            'record' => $this->record('mapping', $mapping->id, $eventKey, 'reused', $companyId, 'Mapping already points to the local template.'),
        ];
    }

    private function record(string $type, ?int $id, string $key, string $action, int $companyId, string $reason): array
    {
        return compact('type', 'id', 'key', 'action', 'companyId', 'reason');
    }

    private function renderSummary(array $summary): void
    {
        $this->line(json_encode(collect($summary)->except('records')->all(), JSON_PRETTY_PRINT));

        if (! $this->option('show-records')) {
            return;
        }

        foreach ($summary['records'] as $record) {
            $this->line(sprintf(
                '%s #%s [%s] company=%s action=%s reason=%s',
                $record['type'],
                $record['id'] ?? '-',
                $record['key'],
                $record['companyId'],
                $record['action'],
                $record['reason']
            ));
        }
    }
}
