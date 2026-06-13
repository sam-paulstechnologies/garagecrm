<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectJourneyIntegrity extends Command
{
    protected $signature = 'journey:inspect-integrity {--company_id= : Limit counts to one company}';

    protected $description = 'Inspect read-only service journey link integrity across leads, opportunities, bookings, jobs, and invoices.';

    public function handle(): int
    {
        $companyId = $this->option('company_id') !== null
            ? (int) $this->option('company_id')
            : null;

        $this->info('Service journey integrity snapshot');

        if ($companyId) {
            $this->line("Company scope: {$companyId}");
        } else {
            $this->line('Company scope: all companies');
        }

        $this->newLine();

        $this->table(['Metric', 'Count'], $this->summaryRows($companyId));

        $this->newLine();
        $this->table(['Mismatch', 'Count'], $this->mismatchRows($companyId));

        if (! $companyId) {
            $this->newLine();
            $this->table(['Company ID', 'Leads', 'Opportunities', 'Bookings', 'Jobs', 'Invoices'], $this->companyRows());
        }

        return self::SUCCESS;
    }

    protected function summaryRows(?int $companyId): array
    {
        return [
            ['Leads', $this->countRows('leads', $companyId)],
            ['Opportunities', $this->countRows('opportunities', $companyId)],
            ['Opportunities without lead', $this->countWhereNull('opportunities', 'lead_id', $companyId)],
            ['Bookings', $this->countRows('bookings', $companyId)],
            ['Bookings without opportunity', $this->countWhereNull('bookings', 'opportunity_id', $companyId)],
            ['Bookings without lead', $this->countWhereNull('bookings', 'lead_id', $companyId)],
            ['Jobs', $this->countRows('jobs', $companyId)],
            ['Jobs without booking', $this->countWhereNull('jobs', 'booking_id', $companyId)],
            ['Jobs without opportunity', $this->countWhereNull('jobs', 'opportunity_id', $companyId)],
            ['Jobs without lead', $this->countWhereNull('jobs', 'lead_id', $companyId)],
            ['Invoices', $this->countRows('invoices', $companyId)],
            ['Invoices without job', $this->countWhereNull('invoices', 'job_id', $companyId)],
            ['Invoices without booking', $this->countWhereNull('invoices', 'booking_id', $companyId)],
            ['Invoices without opportunity', $this->countWhereNull('invoices', 'opportunity_id', $companyId)],
            ['Invoices without lead', $this->countWhereNull('invoices', 'lead_id', $companyId)],
        ];
    }

    protected function mismatchRows(?int $companyId): array
    {
        return [
            [
                'Opportunities linked to lead from another company',
                $this->countCompanyMismatch('opportunities', 'lead_id', 'leads', $companyId),
            ],
            [
                'Bookings linked to opportunity from another company',
                $this->countCompanyMismatch('bookings', 'opportunity_id', 'opportunities', $companyId),
            ],
            [
                'Bookings linked to lead from another company',
                $this->countCompanyMismatch('bookings', 'lead_id', 'leads', $companyId),
            ],
            [
                'Jobs linked to booking from another company',
                $this->countCompanyMismatch('jobs', 'booking_id', 'bookings', $companyId),
            ],
            [
                'Jobs linked to opportunity from another company',
                $this->countCompanyMismatch('jobs', 'opportunity_id', 'opportunities', $companyId),
            ],
            [
                'Jobs linked to lead from another company',
                $this->countCompanyMismatch('jobs', 'lead_id', 'leads', $companyId),
            ],
            [
                'Invoices linked to job from another company',
                $this->countCompanyMismatch('invoices', 'job_id', 'jobs', $companyId),
            ],
            [
                'Invoices linked to booking from another company',
                $this->countCompanyMismatch('invoices', 'booking_id', 'bookings', $companyId),
            ],
            [
                'Invoices linked to opportunity from another company',
                $this->countCompanyMismatch('invoices', 'opportunity_id', 'opportunities', $companyId),
            ],
            [
                'Invoices linked to lead from another company',
                $this->countCompanyMismatch('invoices', 'lead_id', 'leads', $companyId),
            ],
        ];
    }

    protected function companyRows(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        return DB::table('companies')
            ->orderBy('id')
            ->get(['id'])
            ->map(fn ($company) => [
                $company->id,
                $this->countRows('leads', (int) $company->id),
                $this->countRows('opportunities', (int) $company->id),
                $this->countRows('bookings', (int) $company->id),
                $this->countRows('jobs', (int) $company->id),
                $this->countRows('invoices', (int) $company->id),
            ])
            ->all();
    }

    protected function countRows(string $table, ?int $companyId): string|int
    {
        if (! Schema::hasTable($table)) {
            return 'n/a';
        }

        return $this->companyScoped(DB::table($table), $table, $companyId)->count();
    }

    protected function countWhereNull(string $table, string $column, ?int $companyId): string|int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 'n/a';
        }

        return $this->companyScoped(DB::table($table), $table, $companyId)
            ->whereNull($column)
            ->count();
    }

    protected function countCompanyMismatch(string $table, string $foreignKey, string $relatedTable, ?int $companyId): string|int
    {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable($relatedTable)
            || ! Schema::hasColumn($table, $foreignKey)
            || ! Schema::hasColumn($table, 'company_id')
            || ! Schema::hasColumn($relatedTable, 'company_id')
        ) {
            return 'n/a';
        }

        $query = DB::table("{$table} as source")
            ->join("{$relatedTable} as related", "source.{$foreignKey}", '=', 'related.id')
            ->whereNotNull("source.{$foreignKey}")
            ->whereColumn('source.company_id', '!=', 'related.company_id');

        if ($companyId) {
            $query->where('source.company_id', $companyId);
        }

        return $query->count();
    }

    protected function companyScoped(Builder $query, string $table, ?int $companyId): Builder
    {
        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }
}
