<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Client\Opportunity;
use App\Models\Client\CommunicationLog;
use App\Models\WhatsApp\WhatsAppMessage;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            // Guard to avoid "Attempt to read property 'company_id' on null"
            return redirect()->route('login');
        }

        $companyId = (int) $user->company_id;

        // ------------------------------
        // High-level stats (existing)
        // ------------------------------
        $stats = [
            'total_users' => User::where('company_id', $companyId)->count(),
            'total_clients' => Client::where('company_id', $companyId)->count(),
            'total_leads' => Lead::where('company_id', $companyId)->count(),
            'revenue_this_month' => Invoice::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'bookings_this_month' => Booking::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'new_clients_this_month' => Client::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'new_leads_this_month' => Lead::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        $monthlyRevenue = collect(range(0, 5))->map(function ($i) use ($companyId) {
            $month = now()->subMonths($i);
            return [
                'month'   => $month->format('M'),
                'revenue' => Invoice::where('company_id', $companyId)
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('amount'),
            ];
        })->reverse()->values();

        $recentLeads = Lead::where('company_id', $companyId)->latest()->take(5)->get();

        $recentBookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->latest()->take(5)->get();

        $recentOpportunities = Opportunity::with('client')
            ->where('company_id', $companyId)
            ->latest()->take(5)->get();

        // Calendar events (confirmed bookings from today forward)
        $calendarBookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->whereDate('booking_date', '>=', Carbon::today())
            ->get();

        $calendarEvents = $calendarBookings->map(function ($booking) {
            return [
                'title' => ($booking->client->name ?? 'Client') . ' - ' . ucfirst($booking->service_type),
                'start' => $booking->booking_date,
                'url'   => route('admin.bookings.edit', $booking->id),
                'color' => '#4f46e5',
            ];
        });

        $pendingBookings = Booking::where('company_id', $companyId)
            ->where('status', '!=', 'completed')
            ->count();

        $unpaidInvoices = Invoice::where('company_id', $companyId)
            ->where('status', '!=', 'paid')
            ->count();

        $followUpsDue = CommunicationLog::where('company_id', $companyId)
            ->where('follow_up_required', true)
            ->whereBetween('communication_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $smartKPIs = [
            'pending_bookings' => $pendingBookings,
            'unpaid_invoices'  => $unpaidInvoices,
            'followups_due'    => $followUpsDue,
        ];

        // ---------------------------------------------------------
        // WhatsApp Intelligence (KPIs, Timeline, Due/Overdue, Health)
        // ---------------------------------------------------------
        $ackWindowMins = 20;   // after outbound, becomes "Due" if no inbound in X mins
        $slaMins       = 120;  // becomes "Overdue" after SLA mins
        $lookbackHours = 24;   // Due/Overdue lookback window

        // KPIs (Today)
        $waOutboundToday = WhatsAppMessage::forCompany($companyId)
            ->whereDate('created_at', now())
            ->where('direction', 'out');

        $waInboundToday  = WhatsAppMessage::forCompany($companyId)
            ->whereDate('created_at', now())
            ->where('direction', 'in');

        $waKpis = [
            'sent_today'      => (clone $waOutboundToday)->whereIn('status', ['sent', 'delivered', 'read', 'replied'])->count(),
            'delivered_today' => (clone $waOutboundToday)->where('status', 'delivered')->count(),
            'replied_today'   => (clone $waInboundToday)->count(), // treat any inbound as a reply
            'failed_24h'      => WhatsAppMessage::forCompany($companyId)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];

        // Timeline (latest 50)
        $waTimeline = WhatsAppMessage::forCompany($companyId)
            ->with(['template:id,name'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id','direction','status','to','template_id','campaign_id','error_message','created_at']);

        // Due / Overdue (derived: outbound with no subsequent inbound within windows)
        $baseOutbound = WhatsAppMessage::forCompany($companyId)
            ->where('direction', 'out')
            ->where('created_at', '>=', now()->subHours($lookbackHours));

        $dueCount = (clone $baseOutbound)
            ->whereRaw("
                NOT EXISTS (
                    SELECT 1 FROM whatsapp_messages wi
                      WHERE wi.company_id = whatsapp_messages.company_id
                        AND wi.direction = 'in'
                        AND wi.`to` = whatsapp_messages.`to`
                        AND wi.created_at BETWEEN whatsapp_messages.created_at
                                             AND DATE_ADD(whatsapp_messages.created_at, INTERVAL ? MINUTE)
                )
            ", [$ackWindowMins])
            ->count();

        $overdueCount = (clone $baseOutbound)
            ->where('created_at', '<', now()->subMinutes($slaMins))
            ->whereRaw("
                NOT EXISTS (
                    SELECT 1 FROM whatsapp_messages wi
                      WHERE wi.company_id = whatsapp_messages.company_id
                        AND wi.direction = 'in'
                        AND wi.`to` = whatsapp_messages.`to`
                        AND wi.created_at > whatsapp_messages.created_at
                )
            ")
            ->count();

        // Queue Health
        $failedJobsCount = DB::table('failed_jobs')->count();

        $waDashboard = [
            'kpis'          => $waKpis,
            'timeline'      => $waTimeline,
            'due_count'     => $dueCount,
            'overdue_count' => $overdueCount,
            'failed_jobs'   => $failedJobsCount,
            'ack_window'    => $ackWindowMins,
            'sla_mins'      => $slaMins,
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
            'waDashboard'
        ));
    }
}
