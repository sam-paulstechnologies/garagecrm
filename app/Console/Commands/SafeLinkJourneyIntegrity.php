<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SafeLinkJourneyIntegrity extends Command
{
    protected $signature = 'journey:safe-link-integrity
        {--company_id= : Limit safe-linking to one company}
        {--dry-run : Preview changes without writing}
        {--apply : Apply safe-link updates}
        {--limit=50 : Maximum safe booking candidates to process}
        {--show-records : Show record-level proposed/applied changes}';

    protected $description = 'Safely fill existing service journey IDs where strong existing parent links already exist.';

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $mode = $apply ? 'apply' : 'dry-run';
        $companyId = $this->option('company_id') !== null ? (int) $this->option('company_id') : null;
        $limit = max(1, (int) $this->option('limit'));

        $bookingUpdates = $this->safeBookingUpdates($companyId, $limit);
        $bookingLinks = $this->bookingLinksAfter($bookingUpdates);

        $jobUpdates = $this->safeJobUpdates($companyId, $limit, $bookingLinks);
        $jobLinks = $this->jobLinksAfter($jobUpdates);

        $invoiceUpdates = $this->safeInvoiceUpdates($companyId, $limit, $jobLinks);

        $records = [
            ...$bookingUpdates,
            ...$jobUpdates,
            ...$invoiceUpdates,
        ];

        $summary = $this->summary($mode, $companyId, $bookingUpdates, $jobUpdates, $invoiceUpdates);

        if ($apply) {
            $this->applyUpdates($bookingUpdates, $jobUpdates, $invoiceUpdates, $summary);
        }

        $this->info('Service journey safe-link integrity backfill');
        $this->line('Mode: ' . $mode);
        $this->line($companyId ? "Company scope: {$companyId}" : 'Company scope: all companies');
        $this->line("Record limit per stage: {$limit}");
        $this->newLine();

        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($value, $key) => [
            str_replace('_', ' ', $key),
            $value,
        ])->values()->all());

        if ($this->option('show-records')) {
            $this->newLine();
            $this->renderRecords($records, $apply);
        }

        return self::SUCCESS;
    }

    protected function safeBookingUpdates(?int $companyId, int $limit): array
    {
        if (! $this->columnsReady('bookings', ['lead_id', 'opportunity_id'])) {
            return [];
        }

        return $this->companyScoped(DB::table('bookings'), 'bookings', $companyId)
            ->where(function ($query) {
                $query->whereNull('lead_id')
                    ->orWhereNull('opportunity_id');
            })
            ->orderBy('id')
            ->get()
            ->map(fn ($booking) => $this->safeBookingUpdate($booking))
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    protected function safeBookingUpdate(object $booking): ?array
    {
        $opportunity = null;

        if ($booking->opportunity_id) {
            $opportunity = DB::table('opportunities')
                ->where('company_id', $booking->company_id)
                ->where('id', $booking->opportunity_id)
                ->first();
        } else {
            $matches = $this->findOpportunityCandidatesForBooking($booking);

            if ($matches->count() !== 1 || (int) $matches->first()->score < 4) {
                return null;
            }

            $opportunity = $matches->first();
        }

        if (! $opportunity || ! $opportunity->lead_id) {
            return null;
        }

        if ((int) $opportunity->company_id !== (int) $booking->company_id) {
            return null;
        }

        $fields = [];

        if (empty($booking->opportunity_id)) {
            $fields['opportunity_id'] = (int) $opportunity->id;
        }

        if (empty($booking->lead_id)) {
            $fields['lead_id'] = (int) $opportunity->lead_id;
        }

        if ($fields === []) {
            return null;
        }

        return [
            'record_type' => 'booking',
            'record_id' => (int) $booking->id,
            'company_id' => (int) $booking->company_id,
            'client_id' => $booking->client_id ? (int) $booking->client_id : null,
            'source_parent_type' => 'opportunity',
            'source_parent_id' => (int) $opportunity->id,
            'fields' => $fields,
            'reason' => 'Matched existing same-company opportunity with lead_id.',
        ];
    }

    protected function safeJobUpdates(?int $companyId, int $limit, array $bookingLinks): array
    {
        if (! $this->columnsReady('jobs', ['booking_id', 'lead_id', 'opportunity_id'])) {
            return [];
        }

        return $this->companyScoped(DB::table('jobs'), 'jobs', $companyId)
            ->whereNotNull('booking_id')
            ->where(function ($query) {
                $query->whereNull('lead_id')
                    ->orWhereNull('opportunity_id');
            })
            ->orderBy('id')
            ->get()
            ->map(function ($job) use ($bookingLinks) {
                $booking = $bookingLinks[(int) $job->booking_id] ?? null;

                if (! $booking) {
                    $booking = DB::table('bookings')
                        ->where('company_id', $job->company_id)
                        ->where('id', $job->booking_id)
                        ->first();
                }

                if (! $booking || empty($booking->lead_id) || empty($booking->opportunity_id)) {
                    return null;
                }

                $fields = [];

                if (empty($job->lead_id)) {
                    $fields['lead_id'] = (int) $booking->lead_id;
                }

                if (empty($job->opportunity_id)) {
                    $fields['opportunity_id'] = (int) $booking->opportunity_id;
                }

                if ($fields === []) {
                    return null;
                }

                return [
                    'record_type' => 'job',
                    'record_id' => (int) $job->id,
                    'company_id' => (int) $job->company_id,
                    'client_id' => $job->client_id ? (int) $job->client_id : null,
                    'source_parent_type' => 'booking',
                    'source_parent_id' => (int) $booking->id,
                    'fields' => $fields,
                    'reason' => 'Linked booking has lead_id and opportunity_id.',
                ];
            })
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    protected function safeInvoiceUpdates(?int $companyId, int $limit, array $jobLinks): array
    {
        if (! $this->columnsReady('invoices', ['job_id', 'booking_id', 'lead_id', 'opportunity_id'])) {
            return [];
        }

        return $this->companyScoped(DB::table('invoices'), 'invoices', $companyId)
            ->whereNotNull('job_id')
            ->where(function ($query) {
                $query->whereNull('booking_id')
                    ->orWhereNull('lead_id')
                    ->orWhereNull('opportunity_id');
            })
            ->orderBy('id')
            ->get()
            ->map(function ($invoice) use ($jobLinks) {
                $job = $jobLinks[(int) $invoice->job_id] ?? null;

                if (! $job) {
                    $job = DB::table('jobs')
                        ->where('company_id', $invoice->company_id)
                        ->where('id', $invoice->job_id)
                        ->first();
                }

                if (! $job || empty($job->booking_id) || empty($job->lead_id) || empty($job->opportunity_id)) {
                    return null;
                }

                $fields = [];

                if (empty($invoice->booking_id)) {
                    $fields['booking_id'] = (int) $job->booking_id;
                }

                if (empty($invoice->lead_id)) {
                    $fields['lead_id'] = (int) $job->lead_id;
                }

                if (empty($invoice->opportunity_id)) {
                    $fields['opportunity_id'] = (int) $job->opportunity_id;
                }

                if ($fields === []) {
                    return null;
                }

                return [
                    'record_type' => 'invoice',
                    'record_id' => (int) $invoice->id,
                    'company_id' => (int) $invoice->company_id,
                    'client_id' => $invoice->client_id ? (int) $invoice->client_id : null,
                    'source_parent_type' => 'job',
                    'source_parent_id' => (int) $job->id,
                    'fields' => $fields,
                    'reason' => 'Linked job has booking_id, lead_id, and opportunity_id.',
                ];
            })
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    protected function applyUpdates(array $bookingUpdates, array $jobUpdates, array $invoiceUpdates, array &$summary): void
    {
        foreach ($bookingUpdates as $update) {
            DB::transaction(function () use ($update, &$summary) {
                $affected = DB::table('bookings')
                    ->where('company_id', $update['company_id'])
                    ->where('id', $update['record_id'])
                    ->where(function ($query) use ($update) {
                        foreach (array_keys($update['fields']) as $field) {
                            $query->orWhereNull($field);
                        }
                    })
                    ->update($update['fields']);

                if ($affected > 0) {
                    $summary['bookings_updated']++;
                } else {
                    $summary['bookings_skipped']++;
                }
            });
        }

        foreach ($jobUpdates as $update) {
            DB::transaction(function () use ($update, &$summary) {
                $affected = DB::table('jobs')
                    ->where('company_id', $update['company_id'])
                    ->where('id', $update['record_id'])
                    ->where(function ($query) use ($update) {
                        foreach (array_keys($update['fields']) as $field) {
                            $query->orWhereNull($field);
                        }
                    })
                    ->update($update['fields']);

                if ($affected > 0) {
                    $summary['jobs_updated']++;
                } else {
                    $summary['jobs_skipped']++;
                }
            });
        }

        foreach ($invoiceUpdates as $update) {
            DB::transaction(function () use ($update, &$summary) {
                $affected = DB::table('invoices')
                    ->where('company_id', $update['company_id'])
                    ->where('id', $update['record_id'])
                    ->where(function ($query) use ($update) {
                        foreach (array_keys($update['fields']) as $field) {
                            $query->orWhereNull($field);
                        }
                    })
                    ->update($update['fields']);

                if ($affected > 0) {
                    $summary['invoices_updated']++;
                } else {
                    $summary['invoices_skipped']++;
                }
            });
        }
    }

    protected function summary(string $mode, ?int $companyId, array $bookingUpdates, array $jobUpdates, array $invoiceUpdates): array
    {
        return [
            'mode' => $mode,
            'bookings_checked' => $this->countRows('bookings', $companyId),
            'safe_booking_links_found' => count($bookingUpdates),
            'bookings_updated' => 0,
            'bookings_skipped' => 0,
            'jobs_checked' => $this->countRows('jobs', $companyId),
            'jobs_updates_found' => count($jobUpdates),
            'jobs_updated' => 0,
            'jobs_skipped' => 0,
            'invoices_checked' => $this->countRows('invoices', $companyId),
            'invoices_updates_found' => count($invoiceUpdates),
            'invoices_updated' => 0,
            'invoices_skipped' => 0,
            'high_risk_records_untouched' => $this->highRiskInvoiceCount($companyId),
            'ambiguous_records_untouched' => 0,
            'errors' => 0,
        ];
    }

    protected function renderRecords(array $records, bool $applied): void
    {
        if ($records === []) {
            $this->line('No safe-link records found.');

            return;
        }

        $this->table(
            ['Action', 'Type', 'ID', 'Fields', 'Source Parent', 'Reason'],
            collect($records)->map(fn ($record) => [
                $applied ? 'updated' : 'would_update',
                $record['record_type'],
                $record['record_id'],
                json_encode($record['fields'], JSON_UNESCAPED_SLASHES),
                $record['source_parent_type'] . ':' . $record['source_parent_id'],
                $record['reason'],
            ])->all()
        );
    }

    protected function bookingLinksAfter(array $bookingUpdates): array
    {
        $links = [];

        foreach ($bookingUpdates as $update) {
            $booking = DB::table('bookings')
                ->where('company_id', $update['company_id'])
                ->where('id', $update['record_id'])
                ->first();

            if (! $booking) {
                continue;
            }

            $booking->lead_id = $update['fields']['lead_id'] ?? $booking->lead_id;
            $booking->opportunity_id = $update['fields']['opportunity_id'] ?? $booking->opportunity_id;

            $links[(int) $booking->id] = $booking;
        }

        return $links;
    }

    protected function jobLinksAfter(array $jobUpdates): array
    {
        $links = [];

        foreach ($jobUpdates as $update) {
            $job = DB::table('jobs')
                ->where('company_id', $update['company_id'])
                ->where('id', $update['record_id'])
                ->first();

            if (! $job) {
                continue;
            }

            $job->lead_id = $update['fields']['lead_id'] ?? $job->lead_id;
            $job->opportunity_id = $update['fields']['opportunity_id'] ?? $job->opportunity_id;

            $links[(int) $job->id] = $job;
        }

        return $links;
    }

    protected function findOpportunityCandidatesForBooking(object $booking)
    {
        $date = $this->dateValue($booking->booking_date ?: $booking->created_at);
        $windowStart = $date?->copy()->subDays(30);
        $windowEnd = $date?->copy()->addDays(30);
        $service = $this->normalizeText($booking->service_type ?: $booking->name);

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
            ->map(function ($opportunity) use ($booking, $service) {
                $score = 2;

                if ($booking->vehicle_id && (int) $booking->vehicle_id === (int) $opportunity->vehicle_id) {
                    $score += 2;
                }

                if ($service && $this->servicesSimilar($service, $opportunity->service_type ?: $opportunity->title)) {
                    $score += 2;
                }

                if (in_array($opportunity->stage, ['new', 'attempting_contact', 'collecting_details', 'manager_confirmation_pending', 'appointment', 'offer', 'closed_won'], true)) {
                    $score++;
                }

                $opportunity->score = $score;

                return $opportunity;
            })
            ->filter(fn ($opportunity) => (int) $opportunity->score >= 3)
            ->sortByDesc('score')
            ->values();
    }

    protected function highRiskInvoiceCount(?int $companyId): int
    {
        if (! Schema::hasTable('invoices')) {
            return 0;
        }

        return $this->companyScoped(DB::table('invoices'), 'invoices', $companyId)
            ->where(function ($query) {
                $query->whereNull('job_id')
                    ->orWhereNull('client_id');
            })
            ->count();
    }

    protected function countRows(string $table, ?int $companyId): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return $this->companyScoped(DB::table($table), $table, $companyId)->count();
    }

    protected function companyScoped($query, string $table, ?int $companyId)
    {
        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query;
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
