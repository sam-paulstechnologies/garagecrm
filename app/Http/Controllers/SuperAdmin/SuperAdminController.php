<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CompanyModuleSetting;
use App\Models\System\Company;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class SuperAdminController extends Controller
{
    protected function dateRange(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    protected function companiesForFilter()
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function countRows(string $table, ?callable $callback = null): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($callback) {
            $callback($query);
        }

        return (int) $query->count();
    }

    protected function monthCount(string $table, Carbon $from, Carbon $to, ?callable $callback = null): int
    {
        return $this->countRows($table, function (Builder $query) use ($from, $to, $callback) {
            if (Schema::hasColumn($query->from, 'created_at')) {
                $query->whereBetween('created_at', [$from, $to]);
            }

            if ($callback) {
                $callback($query);
            }
        });
    }

    protected function applyCompanyFilter(Builder $query, Request $request): void
    {
        if ($request->filled('company_id') && Schema::hasColumn($query->from, 'company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }
    }

    protected function moduleCatalog(): array
    {
        return CompanyModuleSetting::catalog();
    }

    protected function moduleRowsForCompany(Company $company)
    {
        $settings = CompanyModuleSetting::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('module_key');

        return collect($this->moduleCatalog())
            ->map(function (array $module, string $key) use ($company, $settings) {
                $setting = $settings->get($key);

                return (object) [
                    'company_id' => $company->id,
                    'module_key' => $key,
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'enabled' => $setting ? (bool) $setting->enabled : true,
                    'locked' => $setting ? (bool) $setting->locked : false,
                    'notes' => $setting?->notes,
                    'updated_at' => $setting?->updated_at,
                ];
            })
            ->values();
    }

    protected function maskIdentifier(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Not configured';
        }

        if (Str::length($value) <= 8) {
            return str_repeat('*', max(0, Str::length($value) - 2)).Str::substr($value, -2);
        }

        return Str::substr($value, 0, 4).'...'.Str::substr($value, -4);
    }

    protected function tableLatest(string $table, ?int $companyId = null)
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'created_at')) {
            return null;
        }

        $query = DB::table($table)->latest('created_at');

        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    protected function companyMetrics(int $companyId): array
    {
        return [
            'users' => $this->countRows('users', fn (Builder $q) => $q->where('company_id', $companyId)),
            'leads' => $this->countRows('leads', fn (Builder $q) => $q->where('company_id', $companyId)),
            'messages' => $this->countRows('message_logs', fn (Builder $q) => $q->where('company_id', $companyId)),
            'opportunities' => $this->countRows('opportunities', fn (Builder $q) => $q->where('company_id', $companyId)),
            'bookings' => $this->countRows('bookings', fn (Builder $q) => $q->where('company_id', $companyId)),
            'jobs' => $this->countRows('jobs', fn (Builder $q) => $q->where('company_id', $companyId)),
            'invoices' => $this->countRows('invoices', fn (Builder $q) => $q->where('company_id', $companyId)),
        ];
    }

    protected function channelSummary(Company $company): array
    {
        $lastInbound = null;
        $lastOutbound = null;
        $failedMessages = 0;
        $receivedMessages = 0;

        if (Schema::hasTable('message_logs')) {
            $base = DB::table('message_logs')->where('company_id', $company->id);

            if (Schema::hasColumn('message_logs', 'direction')) {
                $lastInbound = (clone $base)->where('direction', 'in')->latest('created_at')->first();
                $lastOutbound = (clone $base)->where('direction', 'out')->latest('created_at')->first();
                $receivedMessages = (int) (clone $base)->where('direction', 'in')->count();
            }

            if (Schema::hasColumn('message_logs', 'provider_status')) {
                $failedMessages = (int) (clone $base)
                    ->whereIn('provider_status', ['failed', 'error', 'undelivered'])
                    ->count();
            }
        }

        $hasPhone = filled($company->meta_phone_number_id);
        $hasWaba = filled($company->meta_waba_id);
        $isActive = (bool) ($company->is_whatsapp_active ?? false);

        return [
            'phone_number_id' => $this->maskIdentifier($company->meta_phone_number_id),
            'waba_id' => $this->maskIdentifier($company->meta_waba_id),
            'provider' => $hasPhone || $hasWaba ? 'Meta WhatsApp' : 'Not configured',
            'status' => $company->whatsapp_connection_label ?? ($isActive ? 'Connected' : 'Not Connected'),
            'last_inbound' => $lastInbound,
            'last_outbound' => $lastOutbound,
            'failed_messages' => $failedMessages,
            'received_messages' => $receivedMessages,
            'warnings' => array_values(array_filter([
                ! $hasPhone ? 'Missing phone number ID' : null,
                ! $hasWaba ? 'Missing WABA ID' : null,
                ! $isActive ? 'Channel not marked active' : null,
                $failedMessages > 0 ? $failedMessages.' failed message(s)' : null,
            ])),
        ];
    }

    protected function healthChecks(): array
    {
        $dbOk = true;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $dbOk = false;
        }

        $cacheOk = true;

        try {
            Cache::put('super_admin_health_probe', 'ok', 10);
            $cacheOk = Cache::get('super_admin_health_probe') === 'ok';
        } catch (\Throwable) {
            $cacheOk = false;
        }

        return [
            'db' => $dbOk,
            'cache' => $cacheOk,
            'storage_link' => is_link(public_path('storage')) || is_dir(public_path('storage')),
        ];
    }
}
