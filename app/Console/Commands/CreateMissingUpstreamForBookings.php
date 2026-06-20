<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CreateMissingUpstreamForBookings extends Command
{
    protected $signature = 'journey:create-missing-upstream-for-bookings
        {--company_id= : Required company scope}
        {--booking_ids= : Comma-separated booking IDs to process}
        {--dry-run : Preview without writing}
        {--apply : Apply safe upstream creation and linking}
        {--show-records : Show record-level proposed/applied changes}';

    protected $description = 'Create missing leads/opportunities only for explicitly selected unresolved bookings, then copy safe downstream journey links.';

    protected array $summary = [];

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        if ($this->option('company_id') === null || (int) $this->option('company_id') <= 0) {
            $this->error('--company_id is required for Phase 2B safety.');

            return self::FAILURE;
        }

        $bookingIds = $this->bookingIds();

        if ($bookingIds === []) {
            $this->error('--booking_ids is required and must contain at least one booking ID.');

            return self::FAILURE;
        }

        if (! $this->requiredColumnsReady()) {
            $this->error('Required journey integrity columns are missing. Run the Phase 1 migration before this command.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $mode = $apply ? 'apply' : 'dry-run';
        $companyId = (int) $this->option('company_id');
        $this->summary = $this->initialSummary($mode, $companyId, count($bookingIds));

        $records = [];

        foreach ($bookingIds as $bookingId) {
            $record = $this->planBooking($companyId, $bookingId);

            if ($apply && ($record['action'] ?? null) === 'create_and_link') {
                $record = $this->applyBookingPlan($record);
            }

            $this->countRecord($record);
            $records[] = $record;
        }

        $this->renderSummary($companyId, $bookingIds);

        if ($this->option('show-records')) {
            $this->newLine();
            $this->renderRecords($records);
        }

        return $this->summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function bookingIds(): array
    {
        return collect(explode(',', (string) $this->option('booking_ids')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => $id !== '' && ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function planBooking(int $companyId, int $bookingId): array
    {
        $booking = DB::table('bookings')
            ->where('company_id', $companyId)
            ->where('id', $bookingId)
            ->first();

        if (! $booking) {
            return $this->skipRecord($bookingId, 'not_found', 'Booking was not found inside the requested company.');
        }

        if ($booking->lead_id && $booking->opportunity_id) {
            return $this->skipRecord($bookingId, 'already_linked', 'Booking already has lead_id and opportunity_id.', $booking);
        }

        if (! $booking->client_id) {
            return $this->skipRecord($bookingId, 'insufficient_data', 'Booking has no client_id.', $booking);
        }

        $client = DB::table('clients')
            ->where('company_id', $companyId)
            ->where('id', $booking->client_id)
            ->first();

        if (! $client) {
            return $this->skipRecord($bookingId, 'insufficient_data', 'Booking client_id does not resolve inside the same company.', $booking);
        }

        $leadResolution = $this->resolveLead($booking, $client);

        if ($leadResolution['status'] === 'ambiguous') {
            return $this->skipRecord($bookingId, 'ambiguous', $leadResolution['reason'], $booking);
        }

        $opportunityResolution = $this->resolveOpportunity($booking, $leadResolution['lead'] ?? null);

        if ($opportunityResolution['status'] === 'ambiguous') {
            return $this->skipRecord($bookingId, 'ambiguous', $opportunityResolution['reason'], $booking);
        }

        $leadFields = $leadResolution['status'] === 'create'
            ? $this->leadFields($booking, $client)
            : [];

        $opportunityFields = $opportunityResolution['status'] === 'create'
            ? $this->opportunityFields($booking, $client, $leadResolution['lead']->id ?? null)
            : [];

        $existingLeadId = $leadResolution['lead']->id ?? null;
        $existingOpportunityId = $opportunityResolution['opportunity']->id ?? null;
        $bookingFields = [];

        if (! $booking->lead_id) {
            $bookingFields['lead_id'] = $existingLeadId ?: 'new_lead_id';
        }

        if (! $booking->opportunity_id) {
            $bookingFields['opportunity_id'] = $existingOpportunityId ?: 'new_opportunity_id';
        }

        $dependentJobs = $this->dependentJobPlans($booking, $bookingFields);
        $dependentInvoices = $this->dependentInvoicePlans($dependentJobs);

        return [
            'action' => 'create_and_link',
            'record_type' => 'booking',
            'record_id' => (int) $booking->id,
            'company_id' => (int) $booking->company_id,
            'client_id' => (int) $booking->client_id,
            'mode_result' => $this->option('apply') ? 'pending_apply' : 'would_update',
            'lead_action' => $leadResolution['status'],
            'opportunity_action' => $opportunityResolution['status'],
            'lead_id' => $existingLeadId,
            'opportunity_id' => $existingOpportunityId,
            'lead_fields' => $leadFields,
            'opportunity_fields' => $opportunityFields,
            'booking_fields' => $bookingFields,
            'dependent_jobs' => $dependentJobs,
            'dependent_invoices' => $dependentInvoices,
            'reason' => 'Explicit Phase 2B booking target has same-company client data and no ambiguous upstream match.',
        ];
    }

    protected function resolveLead(object $booking, object $client): array
    {
        $matches = $this->findLeadCandidates($booking, $client);

        if ($matches->count() > 1) {
            return [
                'status' => 'ambiguous',
                'reason' => 'Multiple same-company lead candidates found: ' . $matches->pluck('id')->implode(', '),
            ];
        }

        if ($matches->count() === 1) {
            return [
                'status' => 'reuse',
                'lead' => $matches->first(),
            ];
        }

        return ['status' => 'create'];
    }

    protected function resolveOpportunity(object $booking, ?object $lead): array
    {
        if ($booking->opportunity_id) {
            $opportunity = DB::table('opportunities')
                ->where('company_id', $booking->company_id)
                ->where('id', $booking->opportunity_id)
                ->first();

            if (! $opportunity) {
                return [
                    'status' => 'ambiguous',
                    'reason' => 'Booking opportunity_id does not resolve inside the same company.',
                ];
            }

            return ['status' => 'reuse', 'opportunity' => $opportunity];
        }

        $matches = $this->findOpportunityCandidates($booking, $lead);

        if ($matches->count() > 1) {
            return [
                'status' => 'ambiguous',
                'reason' => 'Multiple same-company opportunity candidates found: ' . $matches->pluck('id')->implode(', '),
            ];
        }

        if ($matches->count() === 1) {
            return [
                'status' => 'reuse',
                'opportunity' => $matches->first(),
            ];
        }

        return ['status' => 'create'];
    }

    protected function findLeadCandidates(object $booking, object $client)
    {
        $date = $this->dateValue($booking->booking_date ?: $booking->created_at);
        $windowStart = $date?->copy()->subDays(30);
        $windowEnd = $date?->copy()->addDays(30);
        $service = $this->normalizeText($booking->service_type ?: $booking->name);

        return DB::table('leads')
            ->where('company_id', $booking->company_id)
            ->where('client_id', $client->id)
            ->when($windowStart && $windowEnd, function ($query) use ($windowStart, $windowEnd) {
                $query->whereBetween('created_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);
            })
            ->get()
            ->filter(function ($lead) use ($service) {
                if ($service === '') {
                    return true;
                }

                return $this->servicesSimilar($service, $lead->service_type ?? null)
                    || $this->servicesSimilar($service, $lead->service_category ?? null)
                    || $this->servicesSimilar($service, $lead->notes ?? null);
            })
            ->values();
    }

    protected function findOpportunityCandidates(object $booking, ?object $lead)
    {
        if ($lead) {
            $existingForLead = DB::table('opportunities')
                ->where('company_id', $booking->company_id)
                ->where('lead_id', $lead->id)
                ->get();

            if ($existingForLead->isNotEmpty()) {
                return $existingForLead;
            }
        }

        $date = $this->dateValue($booking->booking_date ?: $booking->created_at);
        $windowStart = $date?->copy()->subDays(30);
        $windowEnd = $date?->copy()->addDays(30);
        $service = $this->normalizeText($booking->service_type ?: $booking->name);

        return DB::table('opportunities')
            ->where('company_id', $booking->company_id)
            ->where('client_id', $booking->client_id)
            ->whereNull('lead_id')
            ->when($booking->vehicle_id, fn ($query) => $query->where('vehicle_id', $booking->vehicle_id))
            ->when($windowStart && $windowEnd, function ($query) use ($windowStart, $windowEnd) {
                $query->where(function ($dates) use ($windowStart, $windowEnd) {
                    $dates->whereBetween('expected_close_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
                        ->orWhereBetween('created_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);
                });
            })
            ->get()
            ->filter(function ($opportunity) use ($service) {
                if ($service === '') {
                    return true;
                }

                return $this->servicesSimilar($service, $opportunity->service_type ?? null)
                    || $this->servicesSimilar($service, $opportunity->title ?? null);
            })
            ->values();
    }

    protected function dependentJobPlans(object $booking, array $bookingFields): array
    {
        return DB::table('jobs')
            ->where('company_id', $booking->company_id)
            ->where('booking_id', $booking->id)
            ->get()
            ->map(function ($job) use ($bookingFields) {
                $fields = [];

                if (! $job->lead_id) {
                    $fields['lead_id'] = $bookingFields['lead_id'] ?? null;
                }

                if (! $job->opportunity_id) {
                    $fields['opportunity_id'] = $bookingFields['opportunity_id'] ?? null;
                }

                return [
                    'job_id' => (int) $job->id,
                    'fields' => array_filter($fields, fn ($value) => $value !== null),
                ];
            })
            ->filter(fn ($plan) => $plan['fields'] !== [])
            ->values()
            ->all();
    }

    protected function dependentInvoicePlans(array $dependentJobs): array
    {
        $plans = [];

        foreach ($dependentJobs as $jobPlan) {
            $invoices = DB::table('invoices')
                ->where('job_id', $jobPlan['job_id'])
                ->get();

            foreach ($invoices as $invoice) {
                $fields = [];

                if (! $invoice->booking_id) {
                    $job = DB::table('jobs')->where('id', $jobPlan['job_id'])->first();
                    $fields['booking_id'] = $job?->booking_id;
                }

                if (! $invoice->lead_id && isset($jobPlan['fields']['lead_id'])) {
                    $fields['lead_id'] = $jobPlan['fields']['lead_id'];
                }

                if (! $invoice->opportunity_id && isset($jobPlan['fields']['opportunity_id'])) {
                    $fields['opportunity_id'] = $jobPlan['fields']['opportunity_id'];
                }

                $fields = array_filter($fields, fn ($value) => $value !== null);

                if ($fields !== []) {
                    $plans[] = [
                        'invoice_id' => (int) $invoice->id,
                        'fields' => $fields,
                    ];
                }
            }
        }

        return $plans;
    }

    protected function applyBookingPlan(array $record): array
    {
        try {
            DB::transaction(function () use (&$record) {
                $booking = DB::table('bookings')
                    ->where('company_id', $record['company_id'])
                    ->where('id', $record['record_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $booking || ($booking->lead_id && $booking->opportunity_id)) {
                    $record['mode_result'] = 'skipped';
                    $record['skip_reason'] = 'Booking was already linked before apply.';

                    return;
                }

                $leadId = $record['lead_id'];

                if (! $leadId) {
                    $leadId = DB::table('leads')->insertGetId($this->prepareInsertFields('leads', $record['lead_fields']));
                    $record['lead_id'] = $leadId;
                    $record['lead_created'] = true;
                }

                $opportunityId = $record['opportunity_id'];

                if (! $opportunityId) {
                    $opportunityFields = $record['opportunity_fields'];
                    $opportunityFields['lead_id'] = $leadId;
                    $opportunityId = DB::table('opportunities')->insertGetId($this->prepareInsertFields('opportunities', $opportunityFields));
                    $record['opportunity_id'] = $opportunityId;
                    $record['opportunity_created'] = true;
                }

                $bookingFields = [];

                if (! $booking->lead_id) {
                    $bookingFields['lead_id'] = $leadId;
                } elseif ((int) $booking->lead_id !== (int) $leadId) {
                    $record['mode_result'] = 'skipped';
                    $record['skip_reason'] = 'Booking already has a different lead_id.';

                    return;
                }

                if (! $booking->opportunity_id) {
                    $bookingFields['opportunity_id'] = $opportunityId;
                } elseif ((int) $booking->opportunity_id !== (int) $opportunityId) {
                    $record['mode_result'] = 'skipped';
                    $record['skip_reason'] = 'Booking already has a different opportunity_id.';

                    return;
                }

                if ($bookingFields !== []) {
                    DB::table('bookings')
                        ->where('company_id', $record['company_id'])
                        ->where('id', $record['record_id'])
                        ->update($bookingFields);
                    $record['booking_fields'] = $bookingFields;
                    $record['booking_updated'] = true;
                }

                $this->applyDependentJobs($record, $leadId, $opportunityId);
                $this->applyDependentInvoices($record, $leadId, $opportunityId);

                $record['mode_result'] = 'updated';
            });
        } catch (Throwable $exception) {
            $record['mode_result'] = 'error';
            $record['error'] = $exception->getMessage();
        }

        return $record;
    }

    protected function applyDependentJobs(array &$record, int $leadId, int $opportunityId): void
    {
        $updatedJobs = [];

        $jobs = DB::table('jobs')
            ->where('company_id', $record['company_id'])
            ->where('booking_id', $record['record_id'])
            ->lockForUpdate()
            ->get();

        foreach ($jobs as $job) {
            $fields = [];

            if (! $job->lead_id) {
                $fields['lead_id'] = $leadId;
            }

            if (! $job->opportunity_id) {
                $fields['opportunity_id'] = $opportunityId;
            }

            if ($fields === []) {
                continue;
            }

            DB::table('jobs')
                ->where('company_id', $record['company_id'])
                ->where('id', $job->id)
                ->update($fields);

            $updatedJobs[] = ['job_id' => (int) $job->id, 'fields' => $fields];
        }

        $record['dependent_jobs'] = $updatedJobs;
    }

    protected function applyDependentInvoices(array &$record, int $leadId, int $opportunityId): void
    {
        $updatedInvoices = [];
        $jobIds = collect($record['dependent_jobs'])->pluck('job_id')->all();

        if ($jobIds === []) {
            return;
        }

        $invoices = DB::table('invoices')
            ->where('company_id', $record['company_id'])
            ->whereIn('job_id', $jobIds)
            ->lockForUpdate()
            ->get();

        foreach ($invoices as $invoice) {
            $job = DB::table('jobs')
                ->where('company_id', $record['company_id'])
                ->where('id', $invoice->job_id)
                ->first();

            if (! $job || (int) $job->booking_id !== (int) $record['record_id']) {
                continue;
            }

            $fields = [];

            if (! $invoice->booking_id) {
                $fields['booking_id'] = (int) $record['record_id'];
            }

            if (! $invoice->lead_id) {
                $fields['lead_id'] = $leadId;
            }

            if (! $invoice->opportunity_id) {
                $fields['opportunity_id'] = $opportunityId;
            }

            if ($fields === []) {
                continue;
            }

            DB::table('invoices')
                ->where('company_id', $record['company_id'])
                ->where('id', $invoice->id)
                ->update($fields);

            $updatedInvoices[] = ['invoice_id' => (int) $invoice->id, 'fields' => $fields];
        }

        $record['dependent_invoices'] = $updatedInvoices;
    }

    protected function leadFields(object $booking, object $client): array
    {
        $createdAt = $this->timestampFromBooking($booking);
        $phone = $client->whatsapp ?: $client->phone;

        return [
            'company_id' => (int) $booking->company_id,
            'client_id' => (int) $client->id,
            'name' => $client->name ?: $booking->name ?: 'Booking #' . $booking->id,
            'email' => $client->email,
            'phone' => $phone,
            'phone_norm' => $this->normalizePhone($phone),
            'status' => $this->leadStatusForBooking($booking),
            'source' => 'Manual',
            'service_type' => $booking->service_type ?: $booking->name,
            'assigned_to' => $booking->assigned_to,
            'preferred_channel' => $client->whatsapp ? 'whatsapp' : 'phone',
            'notes' => trim('Auto-created to preserve service journey integrity for booking #' . $booking->id . '.'),
            'follow_up_required' => 0,
            'is_active' => $booking->status === 'lost' ? 0 : 1,
            'created_at' => $createdAt,
            'updated_at' => now(),
        ];
    }

    protected function opportunityFields(object $booking, object $client, ?int $leadId): array
    {
        $createdAt = $this->timestampFromBooking($booking);
        $stage = $this->opportunityStageForBooking($booking);

        return [
            'company_id' => (int) $booking->company_id,
            'client_id' => (int) $client->id,
            'vehicle_id' => $booking->vehicle_id,
            'lead_id' => $leadId,
            'title' => $booking->name ?: ($booking->service_type ?: 'Booking #' . $booking->id),
            'service_type' => $booking->service_type ?: $booking->name,
            'stage' => $stage,
            'source' => 'Manual Booking',
            'assigned_to' => $booking->assigned_to,
            'priority' => $this->opportunityPriority($booking->priority),
            'value' => 0,
            'is_converted' => $stage === 'booking_confirmed' ? 1 : 0,
            'expected_close_date' => $booking->booking_date,
            'notes' => trim(($booking->notes ? $booking->notes . "\n\n" : '') . 'Auto-created to preserve service journey integrity for booking #' . $booking->id . '.'),
            'created_at' => $createdAt,
            'updated_at' => now(),
        ];
    }

    protected function prepareInsertFields(string $table, array $fields): array
    {
        return collect($fields)
            ->filter(fn ($value, $field) => Schema::hasColumn($table, $field))
            ->all();
    }

    protected function timestampFromBooking(object $booking): Carbon
    {
        return $this->dateValue($booking->created_at)
            ?: $this->dateValue($booking->booking_date)
            ?: now();
    }

    protected function leadStatusForBooking(object $booking): string
    {
        return match ((string) $booking->status) {
            'converted_to_job' => 'converted',
            'lost' => 'lost',
            default => 'qualified',
        };
    }

    protected function opportunityStageForBooking(object $booking): string
    {
        return match ((string) $booking->status) {
            'converted_to_job' => 'booking_confirmed',
            'lost' => 'closed_lost',
            default => 'appointment',
        };
    }

    protected function opportunityPriority(mixed $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high'], true) ? $priority : 'medium';
    }

    protected function skipRecord(int $bookingId, string $reasonKey, string $reason, ?object $booking = null): array
    {
        return [
            'action' => 'skip',
            'record_type' => 'booking',
            'record_id' => $bookingId,
            'company_id' => $booking?->company_id ? (int) $booking->company_id : null,
            'client_id' => $booking?->client_id ? (int) $booking->client_id : null,
            'mode_result' => 'skipped',
            'skip_reason_key' => $reasonKey,
            'reason' => $reason,
        ];
    }

    protected function countRecord(array $record): void
    {
        if (($record['mode_result'] ?? null) === 'error') {
            $this->summary['errors']++;

            return;
        }

        if (($record['action'] ?? null) === 'skip') {
            $this->summary['bookings_skipped']++;
            $key = $record['skip_reason_key'] ?? 'other';

            if ($key === 'ambiguous') {
                $this->summary['ambiguous_records_untouched']++;
            } elseif ($key === 'insufficient_data') {
                $this->summary['insufficient_data_records_untouched']++;
            } elseif ($key === 'already_linked') {
                $this->summary['already_linked_skipped']++;
            }

            return;
        }

        $this->summary['bookings_planned']++;
        $this->summary['leads_to_create'] += ($record['lead_action'] ?? null) === 'create' ? 1 : 0;
        $this->summary['leads_reused'] += ($record['lead_action'] ?? null) === 'reuse' ? 1 : 0;
        $this->summary['opportunities_to_create'] += ($record['opportunity_action'] ?? null) === 'create' ? 1 : 0;
        $this->summary['opportunities_reused'] += ($record['opportunity_action'] ?? null) === 'reuse' ? 1 : 0;

        if (($record['mode_result'] ?? null) === 'updated') {
            $this->summary['bookings_updated'] += ! empty($record['booking_updated']) ? 1 : 0;
            $this->summary['leads_created'] += ! empty($record['lead_created']) ? 1 : 0;
            $this->summary['opportunities_created'] += ! empty($record['opportunity_created']) ? 1 : 0;
        }

        $this->summary['jobs_planned'] += count($record['dependent_jobs'] ?? []);
        $this->summary['invoices_planned'] += count($record['dependent_invoices'] ?? []);

        if (($record['mode_result'] ?? null) === 'updated') {
            $this->summary['jobs_updated'] += count($record['dependent_jobs'] ?? []);
            $this->summary['invoices_updated'] += count($record['dependent_invoices'] ?? []);
        }
    }

    protected function initialSummary(string $mode, int $companyId, int $requested): array
    {
        return [
            'mode' => $mode,
            'company_id' => $companyId,
            'booking_ids_requested' => $requested,
            'bookings_checked' => $requested,
            'bookings_planned' => 0,
            'bookings_updated' => 0,
            'bookings_skipped' => 0,
            'leads_to_create' => 0,
            'leads_created' => 0,
            'leads_reused' => 0,
            'opportunities_to_create' => 0,
            'opportunities_created' => 0,
            'opportunities_reused' => 0,
            'jobs_planned' => 0,
            'jobs_updated' => 0,
            'invoices_planned' => 0,
            'invoices_updated' => 0,
            'already_linked_skipped' => 0,
            'ambiguous_records_untouched' => 0,
            'insufficient_data_records_untouched' => 0,
            'high_risk_invoices_untouched' => $this->highRiskInvoiceCount($companyId),
            'errors' => 0,
        ];
    }

    protected function renderSummary(int $companyId, array $bookingIds): void
    {
        $this->info('Service journey Phase 2B missing-upstream booking backfill');
        $this->line('Company scope: ' . $companyId);
        $this->line('Explicit booking IDs: ' . implode(', ', $bookingIds));
        $this->newLine();

        $this->table(['Metric', 'Count'], collect($this->summary)->map(fn ($value, $key) => [
            str_replace('_', ' ', $key),
            $value,
        ])->values()->all());
    }

    protected function renderRecords(array $records): void
    {
        if ($records === []) {
            $this->line('No records found.');

            return;
        }

        $this->table(
            ['Result', 'Booking', 'Client', 'Lead', 'Opportunity', 'Booking Fields', 'Jobs', 'Invoices', 'Reason'],
            collect($records)->map(fn ($record) => [
                $record['mode_result'] ?? 'n/a',
                $record['record_id'],
                $record['client_id'] ?? 'n/a',
                $this->recordLeadLabel($record),
                $this->recordOpportunityLabel($record),
                json_encode($record['booking_fields'] ?? [], JSON_UNESCAPED_SLASHES),
                json_encode($record['dependent_jobs'] ?? [], JSON_UNESCAPED_SLASHES),
                json_encode($record['dependent_invoices'] ?? [], JSON_UNESCAPED_SLASHES),
                $record['reason'] ?? ($record['error'] ?? ''),
            ])->all()
        );
    }

    protected function recordLeadLabel(array $record): string
    {
        if (($record['lead_action'] ?? null) === 'create') {
            return isset($record['lead_id']) ? 'created:' . $record['lead_id'] : 'would_create';
        }

        if (($record['lead_action'] ?? null) === 'reuse') {
            return 'reuse:' . $record['lead_id'];
        }

        return 'n/a';
    }

    protected function recordOpportunityLabel(array $record): string
    {
        if (($record['opportunity_action'] ?? null) === 'create') {
            return isset($record['opportunity_id']) ? 'created:' . $record['opportunity_id'] : 'would_create';
        }

        if (($record['opportunity_action'] ?? null) === 'reuse') {
            return 'reuse:' . $record['opportunity_id'];
        }

        return 'n/a';
    }

    protected function requiredColumnsReady(): bool
    {
        return $this->columnsReady('bookings', ['company_id', 'client_id', 'lead_id', 'opportunity_id'])
            && $this->columnsReady('leads', ['company_id', 'client_id'])
            && $this->columnsReady('opportunities', ['company_id', 'client_id', 'lead_id'])
            && $this->columnsReady('jobs', ['company_id', 'booking_id', 'lead_id', 'opportunity_id'])
            && $this->columnsReady('invoices', ['company_id', 'job_id', 'booking_id', 'lead_id', 'opportunity_id']);
    }

    protected function columnsReady(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    protected function highRiskInvoiceCount(int $companyId): int
    {
        if (! Schema::hasTable('invoices')) {
            return 0;
        }

        return DB::table('invoices')
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->whereNull('job_id')
                    ->orWhereNull('client_id');
            })
            ->count();
    }

    protected function normalizeText(mixed $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    protected function servicesSimilar(string $left, mixed $right): bool
    {
        $right = $this->normalizeText($right);

        if ($left === '' || $right === '') {
            return false;
        }

        return str_contains($right, $left)
            || str_contains($left, $right)
            || count(array_intersect(explode(' ', $left), explode(' ', $right))) > 0;
    }

    protected function normalizePhone(mixed $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits !== '' ? $digits : null;
    }

    protected function dateValue(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
