<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanJourneyIntegrity extends Command
{
    protected $signature = 'journey:plan-integrity
        {--company_id= : Limit planning to one company}
        {--limit=50 : Maximum record-level recommendations per type}
        {--show-records : Show record-level recommendations}
        {--json : Output the plan as JSON}';

    protected $description = 'Build a read-only Phase 2 service journey integrity plan without linking or creating records.';

    protected array $openLeadStatuses = [
        'new',
        'open',
        'qualified',
        'contacted',
        'attempting_contact',
        'manager_confirmation_pending',
    ];

    protected array $relevantOpportunityStages = [
        'new',
        'attempting_contact',
        'collecting_details',
        'manager_confirmation_pending',
        'appointment',
        'offer',
        'closed_won',
    ];

    public function handle(): int
    {
        $companyId = $this->option('company_id') !== null
            ? (int) $this->option('company_id')
            : null;

        $limit = max(1, (int) $this->option('limit'));

        $bookingPlans = $this->planBookings($companyId, $limit);
        $jobPlans = $this->planJobs($companyId, $limit, $bookingPlans);
        $invoicePlans = $this->planInvoices($companyId, $limit, $jobPlans);

        $summary = $this->buildSummary($bookingPlans, $jobPlans, $invoicePlans, $companyId);

        $payload = [
            'summary' => $summary,
            'records' => [
                'bookings' => $bookingPlans,
                'jobs' => $jobPlans,
                'invoices' => $invoicePlans,
            ],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Service journey integrity Phase 2 plan');
        $this->line($companyId ? "Company scope: {$companyId}" : 'Company scope: all companies');
        $this->line("Record limit per type: {$limit}");
        $this->newLine();

        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($value, $key) => [
            str_replace('_', ' ', $key),
            $value,
        ])->values()->all());

        if ($this->option('show-records')) {
            $this->newLine();
            $this->renderRecords('Bookings', $bookingPlans);
            $this->renderRecords('Jobs', $jobPlans);
            $this->renderRecords('Invoices', $invoicePlans);
        }

        return self::SUCCESS;
    }

    protected function planBookings(?int $companyId, int $limit): array
    {
        if (! $this->tableReady('bookings')) {
            return [];
        }

        $query = $this->companyScoped(DB::table('bookings'), 'bookings', $companyId)
            ->where(function ($where) {
                $where->whereNull('lead_id')
                    ->orWhereNull('opportunity_id');
            })
            ->orderBy('id')
            ->limit($limit);

        return $query->get()->map(function ($booking) {
            $missing = $this->missingFields($booking, ['lead_id', 'opportunity_id']);
            $candidateIds = [];
            $reason = '';
            $recommendation = 'insufficient_data';
            $risk = 'medium';

            if (! $booking->client_id) {
                $reason = 'Booking has no client_id, so no safe same-client opportunity match can be planned.';
            } elseif ($booking->opportunity_id) {
                $opportunity = DB::table('opportunities')
                    ->where('company_id', $booking->company_id)
                    ->where('id', $booking->opportunity_id)
                    ->first();

                if ($opportunity && $opportunity->lead_id) {
                    $recommendation = 'safe_link_existing_opportunity';
                    $candidateIds = ['opportunity_id' => $opportunity->id, 'lead_id' => $opportunity->lead_id];
                    $reason = 'Booking already points to one same-company opportunity with a lead_id; Phase 2 can safely copy lead_id.';
                    $risk = 'low';
                } elseif ($opportunity) {
                    $recommendation = 'needs_new_lead_and_opportunity';
                    $candidateIds = ['opportunity_id' => $opportunity->id];
                    $reason = 'Booking has an opportunity, but that opportunity has no lead_id to copy.';
                } else {
                    $recommendation = 'insufficient_data';
                    $reason = 'Booking opportunity_id is set but does not resolve inside the same company.';
                    $risk = 'high';
                }
            } else {
                $matches = $this->findOpportunityCandidatesForBooking($booking);

                if ($matches->count() === 1 && (int) $matches->first()->score >= 4) {
                    $match = $matches->first();
                    $recommendation = 'safe_link_existing_opportunity';
                    $candidateIds = ['opportunity_id' => $match->id, 'lead_id' => $match->lead_id];
                    $reason = $match->reason;
                    $risk = 'low';
                } elseif ($matches->count() > 1) {
                    $recommendation = 'ambiguous_multiple_possible_matches';
                    $candidateIds = ['opportunity_ids' => $matches->pluck('id')->values()->all()];
                    $reason = 'Multiple same-company opportunities are plausible; human review is required before linking.';
                    $risk = 'high';
                } else {
                    $recommendation = 'needs_new_lead_and_opportunity';
                    $reason = 'No strong same-company opportunity candidate was found for this booking.';
                }
            }

            $duplicateRisks = $this->duplicateRisksForJourneySeed(
                (int) $booking->company_id,
                $booking->client_id ? (int) $booking->client_id : null,
                $booking->vehicle_id ? (int) $booking->vehicle_id : null,
                $booking->service_type,
                $booking->booking_date ?: $booking->created_at,
                'booking',
                (int) $booking->id
            );

            return $this->recordPlan('booking', $booking, $missing, $recommendation, $candidateIds, $reason, $risk, $duplicateRisks);
        })->all();
    }

    protected function planJobs(?int $companyId, int $limit, array $bookingPlans): array
    {
        if (! $this->tableReady('jobs')) {
            return [];
        }

        $bookingPlanById = collect($bookingPlans)->keyBy('record_id');

        return $this->companyScoped(DB::table('jobs'), 'jobs', $companyId)
            ->where(function ($where) {
                $where->whereNull('lead_id')
                    ->orWhereNull('opportunity_id');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function ($job) use ($bookingPlanById) {
                $missing = $this->missingFields($job, ['lead_id', 'opportunity_id']);
                $candidateIds = [];
                $recommendation = 'insufficient_data';
                $reason = '';
                $risk = 'medium';

                if (! $job->booking_id) {
                    $recommendation = 'invalid_missing_booking';
                    $reason = 'Job has no booking_id, so upstream journey links cannot be safely copied.';
                    $risk = 'high';
                } else {
                    $booking = DB::table('bookings')
                        ->where('company_id', $job->company_id)
                        ->where('id', $job->booking_id)
                        ->first();

                    if (! $booking) {
                        $recommendation = 'invalid_missing_booking';
                        $candidateIds = ['booking_id' => $job->booking_id];
                        $reason = 'Job booking_id does not resolve inside the same company.';
                        $risk = 'high';
                    } elseif ($booking->lead_id && $booking->opportunity_id) {
                        $recommendation = 'safe_copy_from_booking';
                        $candidateIds = [
                            'booking_id' => $booking->id,
                            'opportunity_id' => $booking->opportunity_id,
                            'lead_id' => $booking->lead_id,
                        ];
                        $reason = 'Linked booking already has lead_id and opportunity_id; Phase 2 can safely copy them.';
                        $risk = 'low';
                    } else {
                        $recommendation = 'depends_on_booking_fix';
                        $candidateIds = [
                            'booking_id' => $booking->id,
                            'booking_recommendation' => $bookingPlanById->get($booking->id)['recommendation'] ?? 'not_in_limited_booking_plan',
                        ];
                        $reason = 'Linked booking exists but is missing upstream journey links.';
                    }
                }

                return $this->recordPlan('job', $job, $missing, $recommendation, $candidateIds, $reason, $risk);
            })
            ->all();
    }

    protected function planInvoices(?int $companyId, int $limit, array $jobPlans): array
    {
        if (! $this->tableReady('invoices')) {
            return [];
        }

        $jobPlanById = collect($jobPlans)->keyBy('record_id');

        return $this->companyScoped(DB::table('invoices'), 'invoices', $companyId)
            ->where(function ($where) {
                $where->whereNull('job_id')
                    ->orWhereNull('booking_id')
                    ->orWhereNull('opportunity_id')
                    ->orWhereNull('lead_id');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) use ($jobPlanById) {
                $missing = $this->missingFields($invoice, ['job_id', 'booking_id', 'opportunity_id', 'lead_id']);
                $candidateIds = [];
                $recommendation = 'insufficient_data';
                $reason = '';
                $risk = 'medium';

                if ($invoice->job_id) {
                    $job = DB::table('jobs')
                        ->where('company_id', $invoice->company_id)
                        ->where('id', $invoice->job_id)
                        ->first();

                    if ($job && $job->booking_id && $job->opportunity_id && $job->lead_id) {
                        $recommendation = 'safe_copy_from_job';
                        $candidateIds = [
                            'job_id' => $job->id,
                            'booking_id' => $job->booking_id,
                            'opportunity_id' => $job->opportunity_id,
                            'lead_id' => $job->lead_id,
                        ];
                        $reason = 'Linked job already has booking_id, opportunity_id, and lead_id; Phase 2 can safely copy them.';
                        $risk = 'low';
                    } elseif ($job) {
                        $recommendation = 'depends_on_job_or_booking_fix';
                        $candidateIds = [
                            'job_id' => $job->id,
                            'job_recommendation' => $jobPlanById->get($job->id)['recommendation'] ?? 'not_in_limited_job_plan',
                        ];
                        $reason = 'Linked job exists but is missing one or more upstream journey links.';
                    } else {
                        $recommendation = 'needs_new_full_journey';
                        $candidateIds = ['job_id' => $invoice->job_id];
                        $reason = 'Invoice job_id does not resolve inside the same company.';
                        $risk = 'high';
                    }
                } elseif (! $invoice->client_id) {
                    $recommendation = 'insufficient_data';
                    $reason = 'Invoice has no job_id or client_id, so no safe same-client job match can be planned.';
                    $risk = 'high';
                } else {
                    $matches = $this->findJobCandidatesForInvoice($invoice);

                    if ($matches->count() === 1 && (int) $matches->first()->score >= 3) {
                        $match = $matches->first();
                        $recommendation = 'safe_link_existing_job';
                        $candidateIds = [
                            'job_id' => $match->id,
                            'booking_id' => $match->booking_id,
                            'opportunity_id' => $match->opportunity_id,
                            'lead_id' => $match->lead_id,
                        ];
                        $reason = $match->reason;
                        $risk = 'low';
                    } elseif ($matches->count() > 1) {
                        $recommendation = 'ambiguous_multiple_possible_matches';
                        $candidateIds = ['job_ids' => $matches->pluck('id')->values()->all()];
                        $reason = 'Multiple same-company jobs are plausible; human review is required before linking.';
                        $risk = 'high';
                    } else {
                        $recommendation = 'needs_new_full_journey';
                        $reason = 'No strong same-company job candidate was found for this invoice.';
                    }
                }

                $duplicateRisks = in_array($recommendation, ['needs_new_full_journey', 'insufficient_data'], true)
                    ? $this->duplicateRisksForJourneySeed(
                        (int) $invoice->company_id,
                        $invoice->client_id ? (int) $invoice->client_id : null,
                        null,
                        null,
                        $invoice->invoice_date ?: $invoice->created_at,
                        'invoice',
                        (int) $invoice->id
                    )
                    : [];

                return $this->recordPlan('invoice', $invoice, $missing, $recommendation, $candidateIds, $reason, $risk, $duplicateRisks);
            })
            ->all();
    }

    protected function findOpportunityCandidatesForBooking(object $booking): Collection
    {
        $windowStart = $this->dateValue($booking->booking_date ?: $booking->created_at)?->copy()->subDays(30);
        $windowEnd = $this->dateValue($booking->booking_date ?: $booking->created_at)?->copy()->addDays(30);
        $bookingService = $this->normalizeText($booking->service_type ?: $booking->name);

        return DB::table('opportunities')
            ->where('company_id', $booking->company_id)
            ->where('client_id', $booking->client_id)
            ->where('stage', '!=', 'closed_lost')
            ->when($windowStart && $windowEnd, function ($query) use ($windowStart, $windowEnd) {
                $query->where(function ($dates) use ($windowStart, $windowEnd) {
                    $dates->whereBetween('expected_close_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
                        ->orWhereBetween('created_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);
                });
            })
            ->get()
            ->map(function ($opportunity) use ($booking, $bookingService) {
                $score = 2;
                $reasons = ['same client'];

                if ($booking->vehicle_id && (int) $booking->vehicle_id === (int) $opportunity->vehicle_id) {
                    $score += 2;
                    $reasons[] = 'same vehicle';
                }

                if ($bookingService && $this->servicesSimilar($bookingService, $opportunity->service_type ?: $opportunity->title)) {
                    $score += 2;
                    $reasons[] = 'similar service';
                }

                if (in_array($opportunity->stage, $this->relevantOpportunityStages, true)) {
                    $score++;
                    $reasons[] = 'relevant stage';
                }

                $opportunity->score = $score;
                $opportunity->reason = implode(', ', $reasons);

                return $opportunity;
            })
            ->filter(fn ($opportunity) => (int) $opportunity->score >= 3)
            ->sortByDesc('score')
            ->values();
    }

    protected function findJobCandidatesForInvoice(object $invoice): Collection
    {
        $invoiceDate = $this->dateValue($invoice->invoice_date ?: $invoice->created_at);
        $windowStart = $invoiceDate?->copy()->subDays(14);
        $windowEnd = $invoiceDate?->copy()->addDays(14);

        return DB::table('jobs')
            ->where('company_id', $invoice->company_id)
            ->where('client_id', $invoice->client_id)
            ->when($windowStart && $windowEnd, function ($query) use ($windowStart, $windowEnd) {
                $query->where(function ($dates) use ($windowStart, $windowEnd) {
                    $dates->whereBetween('end_time', [$windowStart->startOfDay(), $windowEnd->endOfDay()])
                        ->orWhereBetween('updated_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);
                });
            })
            ->get()
            ->map(function ($job) {
                $score = 1;
                $reasons = ['same client'];

                if ($job->status === 'completed') {
                    $score += 2;
                    $reasons[] = 'completed job';
                }

                if ($job->booking_id && $job->opportunity_id && $job->lead_id) {
                    $score += 2;
                    $reasons[] = 'complete journey links';
                }

                $job->score = $score;
                $job->reason = implode(', ', $reasons);

                return $job;
            })
            ->filter(fn ($job) => (int) $job->score >= 3)
            ->sortByDesc('score')
            ->values();
    }

    protected function duplicateRisksForJourneySeed(
        int $companyId,
        ?int $clientId,
        ?int $vehicleId,
        mixed $service,
        mixed $date,
        string $sourceType,
        int $sourceId
    ): array {
        if (! $clientId) {
            return ['missing_client_blocks_duplicate_review'];
        }

        $risks = [];
        $normalizedService = $this->normalizeText($service);
        $dateValue = $this->dateValue($date);
        $windowStart = $dateValue?->copy()->subDays(30);
        $windowEnd = $dateValue?->copy()->addDays(30);

        $leadQuery = DB::table('leads')
            ->where('company_id', $companyId)
            ->where('client_id', $clientId);

        if (Schema::hasColumn('leads', 'status')) {
            $leadQuery->whereIn('status', $this->openLeadStatuses);
        }

        if ($normalizedService) {
            $leadQuery->where(function ($query) use ($normalizedService) {
                $query->where('service_type', 'like', '%' . $normalizedService . '%')
                    ->orWhere('service_category', 'like', '%' . $normalizedService . '%');
            });
        }

        if ($leadQuery->exists()) {
            $risks[] = 'existing_open_lead_same_client_service';
        }

        $opportunityQuery = DB::table('opportunities')
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->where('stage', '!=', 'closed_lost');

        if ($vehicleId) {
            $opportunityQuery->where(function ($query) use ($vehicleId) {
                $query->whereNull('vehicle_id')
                    ->orWhere('vehicle_id', $vehicleId);
            });
        }

        if ($normalizedService) {
            $opportunityQuery->where(function ($query) use ($normalizedService) {
                $query->where('service_type', 'like', '%' . $normalizedService . '%')
                    ->orWhere('title', 'like', '%' . $normalizedService . '%');
            });
        }

        if ($opportunityQuery->exists()) {
            $risks[] = 'existing_opportunity_same_client_service';
        }

        if ($windowStart && $windowEnd) {
            $bookingQuery = DB::table('bookings')
                ->where('company_id', $companyId)
                ->where('client_id', $clientId)
                ->whereBetween('booking_date', [$windowStart->toDateString(), $windowEnd->toDateString()]);

            if ($sourceType === 'booking') {
                $bookingQuery->where('id', '!=', $sourceId);
            }

            if ($bookingQuery->exists()) {
                $risks[] = 'existing_booking_same_client_date_window';
            }

            $jobQuery = DB::table('jobs')
                ->where('company_id', $companyId)
                ->where('client_id', $clientId)
                ->whereBetween('updated_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);

            if ($sourceType === 'job') {
                $jobQuery->where('id', '!=', $sourceId);
            }

            if ($jobQuery->exists()) {
                $risks[] = 'existing_job_same_client_date_window';
            }
        }

        return array_values(array_unique($risks));
    }

    protected function buildSummary(array $bookingPlans, array $jobPlans, array $invoicePlans, ?int $companyId): array
    {
        $bookingsChecked = $this->countRows('bookings', $companyId);
        $jobsChecked = $this->countRows('jobs', $companyId);
        $invoicesChecked = $this->countRows('invoices', $companyId);

        return [
            'bookings_checked' => $bookingsChecked,
            'orphan_bookings_found' => $this->countOrphans('bookings', ['lead_id', 'opportunity_id'], $companyId),
            'safe_booking_link_candidates' => $this->countRecommendation($bookingPlans, 'safe_link_existing_opportunity'),
            'ambiguous_booking_cases' => $this->countRecommendation($bookingPlans, 'ambiguous_multiple_possible_matches'),
            'bookings_needing_new_lead_opportunity' => $this->countRecommendation($bookingPlans, 'needs_new_lead_and_opportunity'),
            'jobs_checked' => $jobsChecked,
            'jobs_safe_copy_candidates' => $this->countRecommendation($jobPlans, 'safe_copy_from_booking'),
            'jobs_depending_on_booking_fixes' => $this->countRecommendation($jobPlans, 'depends_on_booking_fix'),
            'invoices_checked' => $invoicesChecked,
            'invoices_safe_copy_candidates' => $this->countRecommendation($invoicePlans, 'safe_copy_from_job'),
            'invoices_safe_link_job_candidates' => $this->countRecommendation($invoicePlans, 'safe_link_existing_job'),
            'invoices_depending_on_job_or_booking_fixes' => $this->countRecommendation($invoicePlans, 'depends_on_job_or_booking_fix'),
            'invoices_needing_job_full_journey' => $this->countRecommendation($invoicePlans, 'needs_new_full_journey'),
            'ambiguous_invoice_cases' => $this->countRecommendation($invoicePlans, 'ambiguous_multiple_possible_matches'),
            'insufficient_data_cases' => $this->countRecommendation($bookingPlans, 'insufficient_data')
                + $this->countRecommendation($jobPlans, 'insufficient_data')
                + $this->countRecommendation($invoicePlans, 'insufficient_data'),
            'high_risk_cases' => collect([...$bookingPlans, ...$jobPlans, ...$invoicePlans])
                ->where('risk', 'high')
                ->count(),
        ];
    }

    protected function recordPlan(
        string $type,
        object $record,
        array $missing,
        string $recommendation,
        array $candidateIds,
        string $reason,
        string $risk,
        array $duplicateRisks = []
    ): array {
        return [
            'record_type' => $type,
            'record_id' => (int) $record->id,
            'company_id' => (int) $record->company_id,
            'client_id' => isset($record->client_id) ? (int) $record->client_id : null,
            'current_missing_fields' => $missing,
            'recommendation' => $recommendation,
            'candidate_parent_ids' => $candidateIds,
            'reason' => $reason,
            'duplicate_risks' => $duplicateRisks,
            'risk' => $risk,
        ];
    }

    protected function renderRecords(string $title, array $plans): void
    {
        $this->line($title);

        if ($plans === []) {
            $this->line('No records found for this section.');
            $this->newLine();

            return;
        }

        $this->table(
            ['Type', 'ID', 'Client', 'Missing', 'Recommendation', 'Candidates', 'Risk', 'Reason'],
            collect($plans)->map(fn ($plan) => [
                $plan['record_type'],
                $plan['record_id'],
                $plan['client_id'] ?? 'n/a',
                implode(', ', $plan['current_missing_fields']),
                $plan['recommendation'],
                json_encode($plan['candidate_parent_ids'], JSON_UNESCAPED_SLASHES),
                $plan['risk'],
                trim($plan['reason'] . ($plan['duplicate_risks'] ? ' Duplicate risks: ' . implode(', ', $plan['duplicate_risks']) : '')),
            ])->all()
        );

        $this->newLine();
    }

    protected function missingFields(object $record, array $fields): array
    {
        return collect($fields)
            ->filter(fn ($field) => empty($record->{$field}))
            ->values()
            ->all();
    }

    protected function countRecommendation(array $plans, string $recommendation): int
    {
        return collect($plans)->where('recommendation', $recommendation)->count();
    }

    protected function countOrphans(string $table, array $columns, ?int $companyId): int|string
    {
        if (! $this->tableReady($table)) {
            return 'n/a';
        }

        return $this->companyScoped(DB::table($table), $table, $companyId)
            ->where(function ($query) use ($columns) {
                foreach ($columns as $column) {
                    $query->orWhereNull($column);
                }
            })
            ->count();
    }

    protected function countRows(string $table, ?int $companyId): int|string
    {
        if (! $this->tableReady($table)) {
            return 'n/a';
        }

        return $this->companyScoped(DB::table($table), $table, $companyId)->count();
    }

    protected function companyScoped(Builder $query, string $table, ?int $companyId): Builder
    {
        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    protected function tableReady(string $table): bool
    {
        return Schema::hasTable($table);
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

    protected function dateValue(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
