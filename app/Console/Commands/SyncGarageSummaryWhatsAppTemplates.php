<?php

namespace App\Console\Commands;

use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use App\Services\Reports\GarageSummaryReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncGarageSummaryWhatsAppTemplates extends Command
{
    protected $signature = 'reports:sync-summary-templates
        {--dry-run : Preview changes without writing}
        {--apply : Write local template and mapping records}
        {--status=pending : Local template status: pending, active, approved, or rejected}
        {--company_id= : Limit sync to one company}
        {--show-records : Show record-level actions}';

    protected $description = 'Sync local WhatsApp garage summary report template records and mappings without sending messages.';

    private const ALLOWED_STATUSES = ['pending', 'active', 'approved', 'rejected'];

    public function handle(GarageSummaryReportService $reports): int
    {
        $mode = $this->option('apply') ? 'apply' : 'dry-run';
        $requestedStatus = strtolower(trim((string) $this->option('status')));

        if (! in_array($requestedStatus, self::ALLOWED_STATUSES, true)) {
            $this->error('Invalid --status. Use pending, active, approved, or rejected.');
            return self::FAILURE;
        }

        $companyIds = $this->companyIds();

        if (empty($companyIds)) {
            $this->warn('No companies found to sync.');
            return self::SUCCESS;
        }

        $storedStatus = $this->storedStatus($requestedStatus);
        $summary = $this->emptySummary($mode, $requestedStatus, $storedStatus);
        $records = [];

        foreach ($companyIds as $companyId) {
            $summary['companies_checked']++;

            foreach ($this->templates($reports) as $definition) {
                $templateResult = $this->syncTemplate((int) $companyId, $definition, $requestedStatus, $storedStatus, $mode);
                $summary[$templateResult['summary_key']]++;
                $records[] = $templateResult['record'];

                $mappingResult = $this->syncMapping((int) $companyId, $definition['event_key'], $templateResult['template_id'], $mode);
                $summary[$mappingResult['summary_key']]++;
                $records[] = $mappingResult['record'];
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

    private function templates(GarageSummaryReportService $reports): array
    {
        return collect(['eod', 'eow', 'eom'])
            ->map(function (string $period) use ($reports) {
                $definition = $reports->templateDefinition($period);

                return [
                    'name' => $definition['key'],
                    'event_key' => $definition['event_key'],
                    'body' => $definition['body'],
                    'category' => 'Utility',
                    'variables' => ['1', '2', '3', '4', '5', '6'],
                ];
            })
            ->all();
    }

    private function emptySummary(string $mode, string $requestedStatus, string $storedStatus): array
    {
        return [
            'mode' => $mode,
            'requested_status' => $requestedStatus,
            'stored_status' => $storedStatus,
            'companies_checked' => 0,
            'templates_to_create' => 0,
            'templates_created' => 0,
            'templates_reused' => 0,
            'templates_to_update' => 0,
            'templates_updated' => 0,
            'mappings_to_create' => 0,
            'mappings_created' => 0,
            'mappings_reused' => 0,
            'mappings_to_update' => 0,
            'mappings_updated' => 0,
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
                    'record' => $this->record('template', null, $definition['name'], 'created', $companyId, 'Created local summary template.'),
                ];
            }

            return [
                'summary_key' => 'templates_to_create',
                'template_id' => null,
                'record' => $this->record('template', null, $definition['name'], 'would_create', $companyId, 'Would create local summary template.'),
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
                    'record' => $this->record('template', $template->id, $definition['name'], 'updated', $companyId, 'Updated local summary template metadata.'),
                ];
            }

            return [
                'summary_key' => 'templates_to_update',
                'template_id' => $template->id,
                'record' => $this->record('template', $template->id, $definition['name'], 'would_update', $companyId, 'Would update local summary template metadata.'),
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
            'category' => $definition['category'],
            'body' => $definition['body'],
            'status' => $status,
            'provider' => config('services.whatsapp.provider', 'meta'),
            'last_synced_at' => now(),
            'variables' => $definition['variables'],
        ];

        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn('whatsapp_templates', $key))
            ->all();
    }

    private function templateUpdates(WhatsAppTemplate $template, array $payload, string $requestedStatus): array
    {
        $updates = [];

        foreach ($payload as $key => $value) {
            if ($key === 'company_id' || $key === 'last_synced_at') {
                continue;
            }

            if ($key === 'status' && strtolower((string) $template->status) === 'active' && $requestedStatus === 'pending') {
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
                    'record' => $this->record('mapping', $mapping->id, $eventKey, 'created', $companyId, 'Created local summary mapping.'),
                ];
            }

            return [
                'summary_key' => 'mappings_to_create',
                'record' => $this->record('mapping', null, $eventKey, 'would_create', $companyId, 'Would create local summary mapping.'),
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
                    'record' => $this->record('mapping', $mapping->id, $eventKey, 'updated', $companyId, 'Updated local summary mapping.'),
                ];
            }

            return [
                'summary_key' => 'mappings_to_update',
                'record' => $this->record('mapping', $mapping->id, $eventKey, 'would_update', $companyId, 'Would update local summary mapping.'),
            ];
        }

        return [
            'summary_key' => 'mappings_reused',
            'record' => $this->record('mapping', $mapping->id, $eventKey, 'reused', $companyId, 'Mapping already points to the local summary template.'),
        ];
    }

    private function storedStatus(string $requestedStatus): string
    {
        return match ($requestedStatus) {
            'active', 'approved' => 'active',
            'rejected' => 'archived',
            default => 'draft',
        };
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
