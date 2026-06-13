<?php

namespace App\Console\Commands;

use App\Models\Client\RetentionAction;
use App\Services\Retention\RetentionTemplateResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RetentionDispatchDryRun extends Command
{
    protected $signature = 'retention:dispatch-dry-run
        {--company_id= : Limit dry-run to one company}
        {--due-now : Classify only actions due at or before now as due}
        {--date= : Use YYYY-MM-DD as the due cutoff date}
        {--limit= : Maximum records to inspect}
        {--show-records : Show action-level classifications}
        {--json : Output JSON only}';

    protected $description = 'Dry-run retention action dispatch eligibility without sending or writing records.';

    private const CLASSIFICATIONS = [
        'ready_to_send',
        'blocked_template_pending',
        'blocked_missing_template',
        'blocked_missing_phone',
        'blocked_opted_out',
        'blocked_missing_variables',
        'skipped_not_due',
        'skipped_wrong_status',
        'skipped_locked',
    ];

    public function handle(RetentionTemplateResolver $templateResolver): int
    {
        $cutoff = $this->cutoff();
        $summary = $this->emptySummary($cutoff);
        $records = [];

        $query = RetentionAction::query()
            ->with([
                'client:id,company_id,name,phone,whatsapp,email',
                'vehicle:id,company_id,client_id,make_id,model_id,plate_number',
                'vehicle.make:id,name',
                'vehicle.model:id,name',
                'company:id,name',
            ])
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($companyId = $this->option('company_id')) {
            $query->where('company_id', (int) $companyId);
        }

        if ($limit = $this->limit()) {
            $query->limit($limit);
        }

        $query->get()->each(function (RetentionAction $action) use ($templateResolver, $cutoff, &$summary, &$records) {
            $summary['actions_checked']++;

            if ($action->status === 'scheduled') {
                $summary['scheduled_actions_checked']++;
            }

            if ($action->scheduled_at && $action->scheduled_at->lessThanOrEqualTo($cutoff)) {
                $summary['due_actions']++;
            }

            $preview = $templateResolver->resolve($action);
            $classification = $this->classify($action, $preview, $cutoff);
            $summary[$classification['classification']]++;

            $records[] = $this->record($action, $preview, $classification);
        });

        $payload = [
            'summary' => $summary,
            'records' => $records,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->line(json_encode($summary, JSON_PRETTY_PRINT));

        if ($this->option('show-records')) {
            foreach ($records as $record) {
                $this->line(sprintf(
                    'action #%s client="%s" phone="%s" segment="%s" template="%s" template_status="%s" scheduled_at="%s" classification=%s reason=%s',
                    $record['action_id'],
                    $record['client'],
                    $record['phone'],
                    $record['segment'],
                    $record['template_key'],
                    $record['template_status'],
                    $record['scheduled_at'],
                    $record['classification'],
                    $record['reason']
                ));
            }
        }

        return self::SUCCESS;
    }

    private function cutoff(): Carbon
    {
        if ($date = $this->option('date')) {
            return Carbon::parse($date)->endOfDay();
        }

        return now();
    }

    private function limit(): ?int
    {
        $limit = $this->option('limit');

        if ($limit === null || $limit === '') {
            return null;
        }

        return max(1, (int) $limit);
    }

    private function emptySummary(Carbon $cutoff): array
    {
        return array_merge([
            'mode' => 'dry-run',
            'company_id' => $this->option('company_id') ?: 'all',
            'due_cutoff' => $cutoff->toDateTimeString(),
            'due_now' => (bool) $this->option('due-now'),
            'actions_checked' => 0,
            'scheduled_actions_checked' => 0,
            'due_actions' => 0,
        ], array_fill_keys(self::CLASSIFICATIONS, 0));
    }

    private function classify(RetentionAction $action, array $preview, Carbon $cutoff): array
    {
        if ($action->status === 'sent' || $action->sent_at || $action->message_log_id) {
            return [
                'classification' => 'skipped_locked',
                'reason' => 'Action is already sent or linked to a message log.',
            ];
        }

        if ($action->status !== 'scheduled') {
            return [
                'classification' => 'skipped_wrong_status',
                'reason' => 'Only scheduled retention actions are dispatch candidates.',
            ];
        }

        if (! $action->scheduled_at) {
            return [
                'classification' => 'skipped_not_due',
                'reason' => 'Scheduled action has no scheduled_at timestamp.',
            ];
        }

        if ($action->scheduled_at->greaterThan($cutoff)) {
            return [
                'classification' => 'skipped_not_due',
                'reason' => 'Scheduled action is not due by the selected cutoff.',
            ];
        }

        return match ($preview['readiness'] ?? null) {
            'ready' => [
                'classification' => 'ready_to_send',
                'reason' => 'Action is scheduled, due, and template readiness is Ready.',
            ],
            'template_pending' => [
                'classification' => 'blocked_template_pending',
                'reason' => 'Mapped local template is pending review or draft.',
            ],
            'template_rejected', 'warning_missing_template' => [
                'classification' => 'blocked_missing_template',
                'reason' => $preview['readiness_label'] ?? 'Template is missing or rejected.',
            ],
            'blocked_no_phone' => [
                'classification' => 'blocked_missing_phone',
                'reason' => 'Client has no phone or WhatsApp number.',
            ],
            'blocked_opted_out' => [
                'classification' => 'blocked_opted_out',
                'reason' => 'Client is opted out or marked do not contact.',
            ],
            default => [
                'classification' => 'blocked_missing_variables',
                'reason' => $this->missingVariableReason($preview),
            ],
        };
    }

    private function missingVariableReason(array $preview): string
    {
        $missing = $preview['missing_variables'] ?? [];

        if (! empty($missing)) {
            return 'Missing required variable(s): ' . implode(', ', $missing) . '.';
        }

        return 'Action is not dispatch-ready: ' . ($preview['readiness_label'] ?? 'Needs review') . '.';
    }

    private function record(RetentionAction $action, array $preview, array $classification): array
    {
        return [
            'action_id' => $action->id,
            'client' => $action->client?->name ?: 'Unknown',
            'phone' => $preview['phone'] ?? null,
            'segment' => $action->segment_code,
            'template_key' => $preview['template_key'] ?? null,
            'mapped_template' => $preview['mapped_template_name'] ?? null,
            'template_status' => $preview['template_status'] ?? null,
            'scheduled_at' => $action->scheduled_at?->toDateTimeString(),
            'classification' => $classification['classification'],
            'reason' => $classification['reason'],
        ];
    }
}
