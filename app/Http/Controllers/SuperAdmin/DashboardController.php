<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\SuperAdminAuditLog;
use App\Models\System\Company;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends SuperAdminController
{
    public function __invoke(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $companyFilter = (int) $request->input('company_id', 0);

        $scope = function (Builder $query) use ($companyFilter): void {
            if ($companyFilter && Schema::hasColumn($query->from, 'company_id')) {
                $query->where('company_id', $companyFilter);
            }
        };

        $companies = Company::query()
            ->with('plan')
            ->when($request->filled('status') && Schema::hasColumn('companies', 'status'), fn ($q) => $q->where('status', $request->string('status')))
            ->get();

        $stats = [
            'total_garages' => Company::count(),
            'active_garages' => Schema::hasColumn('companies', 'status')
                ? Company::where('status', 'active')->count()
                : Company::count(),
            'trial_garages' => Company::query()
                ->where(function ($query) {
                    if (Schema::hasColumn('companies', 'status')) {
                        $query->where('status', 'trial');
                    }
                    if (Schema::hasColumn('companies', 'trial_ends_at')) {
                        $query->orWhere('trial_ends_at', '>=', now());
                    }
                })
                ->count(),
            'suspended_garages' => Schema::hasColumn('companies', 'status')
                ? Company::whereIn('status', ['suspended', 'inactive'])->count()
                : 0,
            'total_users' => $this->countRows('users'),
            'leads_month' => $this->monthCount('leads', $from, $to, $scope),
            'messages_month' => $this->monthCount('message_logs', $from, $to, $scope),
            'open_opportunities' => $this->countRows('opportunities', function (Builder $q) use ($scope) {
                $scope($q);
                if (Schema::hasColumn('opportunities', 'stage')) {
                    $q->whereNotIn('stage', ['booking_confirmed', 'closed_lost', 'closed_won']);
                }
            }),
            'bookings_month' => $this->monthCount('bookings', $from, $to, $scope),
            'jobs_month' => $this->monthCount('jobs', $from, $to, $scope),
            'invoices_month' => $this->monthCount('invoices', $from, $to, $scope),
            'failed_jobs' => $this->countRows('failed_jobs'),
            'whatsapp_issues' => $companies->filter(fn (Company $company) => count($this->channelSummary($company)['warnings']) > 0)->count(),
        ];

        $recentActions = SuperAdminAuditLog::query()
            ->with(['superAdmin', 'company'])
            ->latest()
            ->limit(8)
            ->get();

        $garagesNeedingAttention = $companies
            ->map(function (Company $company) {
                $channel = $this->channelSummary($company);
                $lastLead = $this->tableLatest('leads', $company->id);
                $adminCount = $this->countRows('users', fn (Builder $q) => $q->where('company_id', $company->id)->where('role', 'admin'));

                $warnings = $channel['warnings'];

                if (! $lastLead || \Carbon\Carbon::parse($lastLead->created_at)->lt(now()->subDays(30))) {
                    $warnings[] = 'No recent leads';
                }

                if ($adminCount === 0) {
                    $warnings[] = 'No garage admin';
                }

                if (($company->status ?? 'active') !== 'active') {
                    $warnings[] = 'Garage not active';
                }

                return (object) [
                    'company' => $company,
                    'warnings' => array_unique($warnings),
                    'last_lead' => $lastLead,
                ];
            })
            ->filter(fn ($item) => count($item->warnings) > 0)
            ->take(8)
            ->values();

        return view('super_admin.dashboard', [
            'stats' => $stats,
            'companies' => $this->companiesForFilter(),
            'recentActions' => $recentActions,
            'garagesNeedingAttention' => $garagesNeedingAttention,
            'filters' => $request->only(['date_from', 'date_to', 'company_id', 'status', 'source']),
            'range' => [$from, $to],
        ]);
    }
}
