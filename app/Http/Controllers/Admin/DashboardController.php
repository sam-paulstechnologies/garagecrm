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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;

class DashboardController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $companyId = $this->companyId();

        /*
        |--------------------------------------------------------------------------
        | AI Status for badge
        |--------------------------------------------------------------------------
        */
        $get = fn($k, $d = null) => DB::table('company_settings')
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
        | Main Dashboard Stats - Company Scoped
        |--------------------------------------------------------------------------
        */
        $stats = [
            'total_users' => User::where('company_id', $companyId)->count(),

            'total_clients' => Client::where('company_id', $companyId)
                ->where('is_archived', false)
                ->count(),

            'total_leads' => Lead::where('company_id', $companyId)
                ->count(),

            'total_opportunities' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->count(),

            'total_bookings' => Booking::where('company_id', $companyId)
                ->where('is_archived', false)
                ->count(),

            'total_jobs' => Job::where('company_id', $companyId)
                ->count(),

            'total_invoices' => Invoice::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),

            'revenue_this_month' => Invoice::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),

            'bookings_this_month' => Booking::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereMonth('booking_date', now()->month)
                ->whereYear('booking_date', now()->year)
                ->count(),

            'jobs_this_month' => Job::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            'invoices_this_month' => Invoice::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            'new_clients_this_month' => Client::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            'new_leads_this_month' => Lead::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            'new_opportunities_this_month' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Revenue Summary
        |--------------------------------------------------------------------------
        */
        $paidRevenueThisMonth = Invoice::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $pendingInvoiceAmount = Invoice::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'paid')
            ->sum('amount');

        $averageInvoiceValue = Invoice::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->avg('amount') ?? 0;

        $revenueSummary = [
            'paid_this_month'      => $paidRevenueThisMonth,
            'pending_amount'       => $pendingInvoiceAmount,
            'average_invoice'      => $averageInvoiceValue,
            'unpaid_invoice_count' => Invoice::where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->where('status', '!=', 'paid')
                ->count(),
        ];

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
        | Pipeline Summary
        |--------------------------------------------------------------------------
        */
        $leadPipeline = Lead::where('company_id', $companyId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $opportunityPipeline = Opportunity::where('company_id', $companyId)
            ->where('is_archived', false)
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Recent records
        |--------------------------------------------------------------------------
        */
        $recentLeads = Lead::where('company_id', $companyId)
            ->latest()
            ->take(5)
            ->get();

        $recentBookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->latest()
            ->take(5)
            ->get();

        $recentOpportunities = Opportunity::with('client')
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->latest()
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Mini Calendar
        |--------------------------------------------------------------------------
        | Same active booking logic as full calendar.
        */
        $calendarBookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('is_archived')
                    ->orWhere('is_archived', false);
            })
            ->whereDate('booking_date', '>=', Carbon::today())
            ->get();

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
        | Smart KPIs / Alerts
        |--------------------------------------------------------------------------
        */
        $pendingBookings = Booking::where('company_id', $companyId)
            ->where('is_archived', false)
            ->where('status', '!=', Booking::STATUS_COMPLETED)
            ->count();

        $unpaidInvoices = Invoice::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'paid')
            ->count();

        $followUpsDue = CommunicationLog::where('company_id', $companyId)
            ->where('follow_up_required', true)
            ->whereBetween('communication_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $openJobs = Job::where('company_id', $companyId)
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

        if ($followUpsDue > 0) {
            $alerts->push([
                'label' => $followUpsDue . ' follow-up(s) due this week',
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
        $waStart = now()->subDays(7);

        $waKpis = [
            'sent_today' => DB::table('message_logs')
                ->where('company_id', $companyId)
                ->where('channel', 'whatsapp')
                ->where('direction', 'out')
                ->whereDate('created_at', now())
                ->count(),

            'outbound_7d' => DB::table('message_logs')
                ->where('company_id', $companyId)
                ->where('channel', 'whatsapp')
                ->where('direction', 'out')
                ->where('created_at', '>=', $waStart)
                ->whereIn('provider_status', ['queued', 'sent', 'delivered', 'read'])
                ->count(),

            'replies_7d' => DB::table('message_logs')
                ->where('company_id', $companyId)
                ->where('channel', 'whatsapp')
                ->where('direction', 'in')
                ->where('created_at', '>=', $waStart)
                ->count(),

            'failed_7d' => DB::table('message_logs')
                ->where('company_id', $companyId)
                ->where('channel', 'whatsapp')
                ->where('created_at', '>=', $waStart)
                ->whereIn('provider_status', ['failed', 'undelivered', 'error'])
                ->count(),

            'ai_replies_7d' => DB::table('message_logs')
                ->where('company_id', $companyId)
                ->where('channel', 'whatsapp')
                ->where('is_ai', true)
                ->where('created_at', '>=', $waStart)
                ->count(),
        ];

        $waTimeline = DB::table('message_logs')
            ->where('company_id', $companyId)
            ->where('channel', 'whatsapp')
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
            'alerts'
        ));
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