<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\CompanyModuleSetting;
use App\Models\SuperAdminAuditLog;
use App\Models\System\Company;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GarageController extends SuperAdminController
{
    public function index(Request $request)
    {
        $query = Company::query()
            ->select('companies.*')
            ->with('plan');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");

                if (Schema::hasColumn('companies', 'manager_name')) {
                    $q->orWhere('manager_name', 'like', "%{$search}%");
                }
            });
        }

        if ($request->filled('status') && Schema::hasColumn('companies', 'status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('plan_id') && Schema::hasColumn('companies', 'plan_id')) {
            $query->where('plan_id', $request->input('plan_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        foreach (['leads', 'message_logs', 'users'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                $alias = $table === 'message_logs' ? 'messages_count' : $table.'_count';
                $query->selectSub(
                    DB::table($table)
                        ->selectRaw('count(*)')
                        ->whereColumn($table.'.company_id', 'companies.id'),
                    $alias
                );
            }
        }

        $garages = $query->latest()->paginate(15)->withQueryString();

        return view('super_admin.garages.index', [
            'garages' => $garages,
            'plans' => Schema::hasTable('plans') ? DB::table('plans')->orderBy('name')->get(['id', 'name']) : collect(),
            'filters' => $request->only(['search', 'status', 'plan_id', 'date_from', 'date_to']),
        ]);
    }

    public function show(Company $garage)
    {
        $users = $this->companyUsers($garage);
        $activity = $this->activityForCompany($garage);
        $channel = $this->channelSummary($garage);
        $metrics = $this->companyMetrics($garage->id);
        $modules = $this->moduleRowsForCompany($garage);

        $risks = array_values(array_unique(array_merge(
            $channel['warnings'],
            $this->riskWarnings($garage, $metrics)
        )));

        return view('super_admin.garages.show', compact('garage', 'users', 'activity', 'channel', 'metrics', 'modules', 'risks'));
    }

    public function users(Company $garage)
    {
        return view('super_admin.garages.users', [
            'garage' => $garage,
            'users' => $this->companyUsers($garage),
        ]);
    }

    public function modules(Company $garage)
    {
        return view('super_admin.garages.modules', [
            'garage' => $garage,
            'modules' => $this->moduleRowsForCompany($garage),
        ]);
    }

    public function channels(Company $garage)
    {
        return view('super_admin.garages.channels', [
            'garage' => $garage,
            'channel' => $this->channelSummary($garage),
            'messages' => $this->recentMessagesForCompany($garage),
        ]);
    }

    public function update(Request $request, Company $garage)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'trial', 'pilot', 'suspended', 'inactive'])],
        ]);

        $before = $garage->only(array_keys($validated));

        $garage->fill($validated);

        if (($validated['status'] ?? null) === 'suspended') {
            $garage->suspended_at = $garage->suspended_at ?? now();
        } elseif (array_key_exists('status', $validated)) {
            $garage->suspended_at = null;
        }

        $garage->save();

        SuperAdminAuditLog::record('garage.updated', $garage, $garage->id, [
            'before' => $before,
            'after' => $garage->only(array_keys($validated)),
        ]);

        return back()->with('success', 'Garage details updated.');
    }

    public function activate(Company $garage)
    {
        $garage->forceFill([
            'status' => 'active',
            'suspended_at' => null,
        ])->save();

        SuperAdminAuditLog::record('garage.activated', $garage, $garage->id);

        return back()->with('success', 'Garage activated.');
    }

    public function suspend(Request $request, Company $garage)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $garage->forceFill([
            'status' => 'suspended',
            'suspended_at' => now(),
        ])->save();

        SuperAdminAuditLog::record('garage.suspended', $garage, $garage->id, [
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Garage suspended.');
    }

    public function updateModule(Request $request, Company $garage)
    {
        $catalogKeys = array_keys($this->moduleCatalog());

        $validated = $request->validate([
            'module_key' => ['required', Rule::in($catalogKeys)],
            'enabled' => ['nullable', 'boolean'],
            'locked' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $setting = CompanyModuleSetting::query()->firstOrNew([
            'company_id' => $garage->id,
            'module_key' => $validated['module_key'],
        ]);

        $before = $setting->exists ? $setting->only(['enabled', 'locked', 'notes']) : null;

        $setting->fill([
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'locked' => (bool) ($validated['locked'] ?? false),
            'notes' => $validated['notes'] ?? null,
            'enabled_by' => $setting->enabled_by ?: $request->user()->id,
            'updated_by' => $request->user()->id,
        ])->save();

        SuperAdminAuditLog::record('module.updated', $setting, $garage->id, [
            'module_key' => $setting->module_key,
            'before' => $before,
            'after' => $setting->only(['enabled', 'locked', 'notes']),
        ]);

        return back()->with('success', 'Module access updated.');
    }

    private function companyUsers(Company $garage)
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'company_id')) {
            return collect();
        }

        return DB::table('users')
            ->where('company_id', $garage->id)
            ->orderByRaw("case role when 'admin' then 1 when 'manager' then 2 when 'mechanic' then 3 when 'receptionist' then 4 when 'media_team' then 5 else 9 end")
            ->orderBy('name')
            ->get();
    }

    private function activityForCompany(Company $garage): array
    {
        return [
            'leads' => $this->recentRows('leads', $garage->id, ['id', 'name', 'phone', 'source', 'status', 'created_at']),
            'messages' => $this->recentMessagesForCompany($garage, 5),
            'bookings' => $this->recentRows('bookings', $garage->id, ['id', 'name', 'service_type', 'status', 'booking_date', 'created_at']),
            'jobs' => $this->recentRows('jobs', $garage->id, ['id', 'job_code', 'status', 'created_at']),
            'invoices' => $this->recentRows('invoices', $garage->id, ['id', 'number', 'status', 'created_at']),
        ];
    }

    private function recentRows(string $table, int $companyId, array $columns, int $limit = 5)
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return collect();
        }

        $availableColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));

        if (! in_array('id', $availableColumns, true)) {
            $availableColumns[] = 'id';
        }

        return DB::table($table)
            ->select($availableColumns)
            ->where('company_id', $companyId)
            ->latest(Schema::hasColumn($table, 'created_at') ? 'created_at' : 'id')
            ->limit($limit)
            ->get();
    }

    private function recentMessagesForCompany(Company $garage, int $limit = 10)
    {
        if (! Schema::hasTable('message_logs')) {
            return collect();
        }

        return DB::table('message_logs')
            ->where('company_id', $garage->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    private function riskWarnings(Company $garage, array $metrics): array
    {
        $warnings = [];

        if (($garage->status ?? 'active') !== 'active') {
            $warnings[] = 'Garage is not active';
        }

        if ($metrics['users'] === 0) {
            $warnings[] = 'No users configured';
        }

        if ($this->countRows('users', fn (Builder $q) => $q->where('company_id', $garage->id)->where('role', 'admin')) === 0) {
            $warnings[] = 'No garage admin user';
        }

        $lastLead = $this->tableLatest('leads', $garage->id);

        if (! $lastLead || now()->diffInDays($lastLead->created_at) > 30) {
            $warnings[] = 'No leads received in the last 30 days';
        }

        return $warnings;
    }
}
