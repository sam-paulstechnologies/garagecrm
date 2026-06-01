<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\CommunicationLog;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $companyId = $this->companyId();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Filters
        |--------------------------------------------------------------------------
        */
        [$fromDate, $toDate, $applyDateFilter] = $this->resolveDateRange($request);

        $filters = [
            'date_range'        => $request->get('date_range', 'this_month'),
            'lead_source'       => $request->get('lead_source', 'all'),
            'assigned_user'     => $request->get('assigned_user', 'all'),
            'service_type'      => $request->get('service_type', 'all'),
            'customer_type'     => $request->get('customer_type', 'all'),
            'from_date'         => $request->get('from_date'),
            'to_date'           => $request->get('to_date'),
            'from'              => $fromDate,
            'to'                => $toDate,
            'apply_date_filter' => $applyDateFilter,
        ];

        $assignedUsers = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        /*
        |--------------------------------------------------------------------------
        | AI Status for badge
        |--------------------------------------------------------------------------
        */
        $get = fn ($k, $d = null) => DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', $k)
            ->value('value') ?? $d;

        $aiEnabled = ($get('ai.enabled', '0') === '1');

        $aiThreshold = is_numeric($get('ai.confidence_threshold'))
            ? (float) $get('ai.confidence_threshold')
            : (float) env('AI_CONFIDENCE_THRESHOLD', 0.60);

        $aiFirst = ($get('ai.first_reply', '0') === '1');

        $aiStatus = [
            'enabled'   => $aiEnabled,
            'threshold' => $aiThreshold,
            'first'     => $aiFirst,
            'label'     => $aiEnabled ? 'AI On' : 'AI Off',
            'color'     => $aiEnabled ? '#10b981' : '#9ca3af',
        ];

        /*
        |--------------------------------------------------------------------------
        | Base Queries - Company Scoped + Filtered
        |--------------------------------------------------------------------------
        */
        $clientsQuery = Client::where('company_id', $companyId)
            ->where('is_archived', false);

        if ($applyDateFilter) {
            $clientsQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $leadsQuery = $this->applyDashboardFilters(
            Lead::where('company_id', $companyId),
            Lead::class,
            $filters,
            'created_at'
        );

        $opportunitiesQuery = $this->applyDashboardFilters(
            Opportunity::where('company_id', $companyId)->where('is_archived', false),
            Opportunity::class,
            $filters,
            'created_at'
        );

        $bookingsQuery = $this->applyDashboardFilters(
            Booking::where('company_id', $companyId)->where('is_archived', false),
            Booking::class,
            $filters,
            'booking_date'
        );

        $jobsQuery = $this->applyDashboardFilters(
            Job::where('company_id', $companyId),
            Job::class,
            $filters,
            'created_at'
        );

        $invoicesQuery = $this->applyDashboardFilters(
            Invoice::where('company_id', $companyId)->whereNull('deleted_at'),
            Invoice::class,
            $filters,
            'created_at'
        );

        /*
        |--------------------------------------------------------------------------
        | Main Dashboard Stats - Filtered
        |--------------------------------------------------------------------------
        */
        $stats = [
            'total_users' => User::where('company_id', $companyId)->count(),

            'total_clients' => (clone $clientsQuery)->count(),

            'total_leads' => (clone $leadsQuery)->count(),

            'total_opportunities' => (clone $opportunitiesQuery)->count(),

            'total_bookings' => (clone $bookingsQuery)->count(),

            'total_jobs' => (clone $jobsQuery)->count(),

            'total_invoices' => (clone $invoicesQuery)->count(),

            'revenue_this_month' => (clone $invoicesQuery)->sum('amount'),

            'bookings_this_month' => (clone $bookingsQuery)->count(),

            'jobs_this_month' => (clone $jobsQuery)->count(),

            'invoices_this_month' => (clone $invoicesQuery)->count(),

            'new_clients_this_month' => (clone $clientsQuery)->count(),

            'new_leads_this_month' => (clone $leadsQuery)->count(),

            'new_opportunities_this_month' => (clone $opportunitiesQuery)->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Revenue Summary - Filtered
        |--------------------------------------------------------------------------
        */
        $paidRevenueThisMonth = (clone $invoicesQuery)
            ->where('status', 'paid')
            ->sum('amount');

        $pendingInvoiceAmount = (clone $invoicesQuery)
            ->where('status', '!=', 'paid')
            ->sum('amount');

        $averageInvoiceValue = (clone $invoicesQuery)
            ->avg('amount') ?? 0;

        $revenueSummary = [
            'paid_this_month'      => $paidRevenueThisMonth,
            'pending_amount'       => $pendingInvoiceAmount,
            'average_invoice'      => $averageInvoiceValue,
            'unpaid_invoice_count' => (clone $invoicesQuery)
                ->where('status', '!=', 'paid')
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Monthly Revenue Chart - Always last 6 months
        |--------------------------------------------------------------------------
        */
        $monthlyRevenue = collect(range(0, 5))->map(function ($i) use ($companyId) {
            $month = now()->subMonths($i);

            return [
                'month'   => $month->format('M'),
                'revenue' => Invoice::where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('amount'),
            ];
        })->reverse()->values();

        /*
        |--------------------------------------------------------------------------
        | Pipeline Summary - Filtered
        |--------------------------------------------------------------------------
        */
        $leadPipeline = (clone $leadsQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $opportunityPipeline = (clone $opportunitiesQuery)
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Recent records - Filtered
        |--------------------------------------------------------------------------
        */
        $recentLeads = (clone $leadsQuery)
            ->latest()
            ->take(5)
            ->get();

        $recentBookings = (clone $bookingsQuery)
            ->with('client')
            ->latest()
            ->take(5)
            ->get();

        $recentOpportunities = (clone $opportunitiesQuery)
            ->with('client')
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Mini Calendar
        |--------------------------------------------------------------------------
        | Calendar now uses the selected dashboard date range.
        | This fixes mismatch where KPI showed bookings this month but calendar showed 0.
        |--------------------------------------------------------------------------
        */
        $calendarBookings = $this->applyDashboardFilters(
            Booking::with('client')
                ->where('company_id', $companyId)
                ->where(function ($q) {
                    $q->whereNull('is_archived')
                        ->orWhere('is_archived', false);
                }),
            Booking::class,
            $filters,
            'booking_date',
            true
        )->get();

        $calendarEvents = $calendarBookings
            ->map(function ($booking) {
                $start = $this->resolveCalendarStart($booking);

                if (!$start) {
                    return null;
                }

                $end = $this->resolveCalendarEnd($booking, $start);

                $titleParts = [
                    $booking->client->name ?? 'Client',
                    $booking->service_type ?: ($booking->name ?? 'Booking'),
                ];

                $url = RouteFacade::has('admin.bookings.show')
                    ? route('admin.bookings.show', $booking->id)
                    : url('/admin/bookings/' . $booking->id);

                return [
                    'id'              => $booking->id,
                    'title'           => implode(' - ', array_filter($titleParts)),
                    'start'           => $start->toIso8601String(),
                    'end'             => $end?->toIso8601String(),
                    'url'             => $url,
                    'backgroundColor' => $this->calendarStatusColor($booking->status),
                    'borderColor'     => $this->calendarStatusColor($booking->status),
                    'textColor'       => '#ffffff',
                    'color'           => $this->calendarStatusColor($booking->status),
                ];
            })
            ->filter()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Smart KPIs / Alerts - Filtered
        |--------------------------------------------------------------------------
        */
        $pendingBookings = (clone $bookingsQuery)
            ->where('status', '!=', Booking::STATUS_COMPLETED)
            ->count();

        $unpaidInvoices = (clone $invoicesQuery)
            ->where('status', '!=', 'paid')
            ->count();

        $followUpsDueQuery = CommunicationLog::where('company_id', $companyId)
            ->where('follow_up_required', true);

        if ($applyDateFilter) {
            $followUpsDueQuery->whereBetween('communication_date', [$fromDate, $toDate]);
        }

        $followUpsDue = $followUpsDueQuery->count();

        $openJobs = (clone $jobsQuery)
            ->whereNotIn('status', ['completed', 'cancelled', 'closed'])
            ->count();

        $smartKPIs = [
            'pending_bookings' => $pendingBookings,
            'unpaid_invoices'  => $unpaidInvoices,
            'followups_due'    => $followUpsDue,
            'open_jobs'        => $openJobs,
        ];

        $alerts = collect();

        if ($pendingBookings > 0) {
            $alerts->push([
                'label' => $pendingBookings . ' pending booking(s) need attention',
                'url'   => route('admin.bookings.index'),
            ]);
        }

        if ($unpaidInvoices > 0) {
            $alerts->push([
                'label' => $unpaidInvoices . ' unpaid invoice(s)',
                'url'   => route('admin.invoices.index'),
            ]);
        }

        if ($followUpsDue > 0 && RouteFacade::has('admin.communication-logs.index')) {
            $alerts->push([
                'label' => $followUpsDue . ' follow-up(s) due in selected period',
                'url'   => route('admin.communication-logs.index'),
            ]);
        }

        if ($openJobs > 0) {
            $alerts->push([
                'label' => $openJobs . ' open job(s)',
                'url'   => route('admin.jobs.index'),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp KPIs - From message_logs
        |--------------------------------------------------------------------------
        */
        $waBase = DB::table('message_logs')
            ->where('company_id', $companyId)
            ->where('channel', 'whatsapp');

        if ($applyDateFilter) {
            $waBase->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $waKpis = [
            'sent_today' => DB::table('message_logs')
                ->where('company_id', $companyId)
                ->where('channel', 'whatsapp')
                ->where('direction', 'out')
                ->whereDate('created_at', now())
                ->count(),

            'outbound_7d' => (clone $waBase)
                ->where('direction', 'out')
                ->whereIn('provider_status', ['queued', 'sent', 'delivered', 'read'])
                ->count(),

            'replies_7d' => (clone $waBase)
                ->where('direction', 'in')
                ->count(),

            'failed_7d' => (clone $waBase)
                ->whereIn('provider_status', ['failed', 'undelivered', 'error'])
                ->count(),

            'ai_replies_7d' => (clone $waBase)
                ->where('is_ai', true)
                ->count(),
        ];

        $waTimeline = (clone $waBase)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get([
                'id',
                'direction',
                'provider_status',
                'to_number',
                'from_number',
                'template',
                'is_ai',
                'created_at',
            ]);

        $waDashboard = [
            'kpis'          => $waKpis,
            'timeline'      => $waTimeline,
            'due_count'     => 0,
            'overdue_count' => 0,
            'failed_jobs'   => DB::table('failed_jobs')->count(),
            'ack_window'    => 20,
            'sla_mins'      => 120,
        ];

        return view('admin.dashboard.index', compact(
            'stats',
            'monthlyRevenue',
            'recentLeads',
            'recentBookings',
            'recentOpportunities',
            'calendarEvents',
            'pendingBookings',
            'unpaidInvoices',
            'followUpsDue',
            'smartKPIs',
            'waDashboard',
            'aiStatus',
            'leadPipeline',
            'opportunityPipeline',
            'revenueSummary',
            'alerts',
            'filters',
            'assignedUsers'
        ));
    }

    private function resolveDateRange(Request $request): array
    {
        $range = $request->get('date_range', 'this_month');

        return match ($range) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
                true,
            ],

            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                true,
            ],

            'last_7_days' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                true,
            ],

            'this_month' => [
                now()->startOfMonth()->startOfDay(),
                now()->endOfDay(),
                true,
            ],

            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth()->startOfDay(),
                now()->subMonthNoOverflow()->endOfMonth()->endOfDay(),
                true,
            ],

            'all_time' => [
                Carbon::create(1970, 1, 1)->startOfDay(),
                now()->endOfDay(),
                false,
            ],

            'custom' => [
                $request->filled('from_date')
                    ? Carbon::parse($request->get('from_date'))->startOfDay()
                    : now()->startOfMonth(),

                $request->filled('to_date')
                    ? Carbon::parse($request->get('to_date'))->endOfDay()
                    : now()->endOfDay(),

                true,
            ],

            default => [
                now()->startOfMonth()->startOfDay(),
                now()->endOfDay(),
                true,
            ],
        };
    }

    private function applyDashboardFilters(
        Builder $query,
        string $modelClass,
        array $filters,
        string $dateColumn = 'created_at',
        bool $applyDate = true
    ): Builder {
        $table = (new $modelClass)->getTable();

        if (
            $applyDate &&
            ($filters['apply_date_filter'] ?? true) &&
            Schema::hasColumn($table, $dateColumn)
        ) {
            $query->whereBetween($dateColumn, [$filters['from'], $filters['to']]);
        }

        if (($filters['lead_source'] ?? 'all') !== 'all') {
            $leadSourceColumn = $this->firstExistingColumn($table, [
                'lead_source',
                'source',
                'source_type',
                'channel',
                'external_source',
            ]);

            if ($leadSourceColumn) {
                $query->where($leadSourceColumn, $filters['lead_source']);
            }
        }

        if (($filters['assigned_user'] ?? 'all') !== 'all') {
            $assignedColumn = $this->firstExistingColumn($table, [
                'assigned_user_id',
                'assigned_to',
                'manager_id',
                'user_id',
                'owner_id',
            ]);

            if ($assignedColumn) {
                $query->where($assignedColumn, $filters['assigned_user']);
            }
        }

        if (($filters['service_type'] ?? 'all') !== 'all') {
            $serviceColumn = $this->firstExistingColumn($table, [
                'service_type',
                'service',
                'looking_for',
                'job_type',
                'booking_type',
            ]);

            if ($serviceColumn) {
                $query->where($serviceColumn, $filters['service_type']);
            }
        }

        if (($filters['customer_type'] ?? 'all') !== 'all' && Schema::hasColumn($table, 'client_id')) {
            $query->whereIn('client_id', function ($subQuery) use ($filters) {
                $subQuery->select('id')
                    ->from('clients');

                if (($filters['customer_type'] ?? 'all') === 'new') {
                    $subQuery->whereBetween('created_at', [$filters['from'], $filters['to']]);
                }

                if (($filters['customer_type'] ?? 'all') === 'returning') {
                    $subQuery->where('created_at', '<', $filters['from']);
                }
            });
        }

        return $query;
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function resolveCalendarStart($booking): ?Carbon
    {
        if (!empty($booking->scheduled_at)) {
            return Carbon::parse($booking->scheduled_at);
        }

        $date = $booking->booking_date ?? null;

        if (!$date) {
            return null;
        }

        $start = Carbon::parse($date);

        $slot = strtolower((string) $booking->slot);

        if (str_contains($slot, 'morning')) {
            return $start->setTime(9, 0);
        }

        if (str_contains($slot, 'afternoon')) {
            return $start->setTime(13, 0);
        }

        if (str_contains($slot, 'evening')) {
            return $start->setTime(16, 0);
        }

        return $start->setTime(10, 0);
    }

    private function resolveCalendarEnd($booking, Carbon $start): Carbon
    {
        if (!empty($booking->scheduled_end_at)) {
            return Carbon::parse($booking->scheduled_end_at);
        }

        $hours = is_numeric($booking->expected_duration)
            ? max((int) $booking->expected_duration, 1)
            : 2;

        return $start->copy()->addHours($hours);
    }

    private function calendarStatusColor($status): string
    {
        return match (strtolower((string) $status)) {
            Booking::STATUS_CONFIRMED => '#22c55e',
            Booking::STATUS_PENDING => '#f59e0b',
            Booking::STATUS_SCHEDULED => '#6366f1',
            Booking::STATUS_VEHICLE_RECEIVED => '#8b5cf6',
            Booking::STATUS_COMPLETED => '#0ea5e9',
            Booking::STATUS_CANCELED => '#ef4444',
            default => '#6b7280',
        };
    }
}