<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\Job\Invoice;
use App\Models\Client\Opportunity;
use App\Models\Client\CommunicationLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $stats = [
            'total_users' => User::where('company_id', $companyId)->count(),
            'total_clients' => Client::where('company_id', $companyId)->count(),
            'total_leads' => Lead::where('company_id', $companyId)->count(),
            'revenue_this_month' => Invoice::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'bookings_this_month' => Booking::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'new_clients_this_month' => Client::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'new_leads_this_month' => Lead::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        $monthlyRevenue = collect(range(0, 5))->map(function ($i) use ($companyId) {
            $month = now()->subMonths($i);
            return [
                'month' => $month->format('M'),
                'revenue' => Invoice::where('company_id', $companyId)
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('amount'),
            ];
        })->reverse()->values();

        $recentLeads = Lead::where('company_id', $companyId)->latest()->take(5)->get();

        $recentBookings = Booking::with('client')
            ->where('company_id', $companyId)->latest()->take(5)->get();

        $recentOpportunities = Opportunity::with('client')
            ->where('company_id', $companyId)->latest()->take(5)->get();

        // ✅ Fixed column: booking_date
        $calendarBookings = Booking::with('client')
            ->where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->whereDate('booking_date', '>=', Carbon::today())
            ->get();

        $calendarEvents = $calendarBookings->map(function ($booking) {
            return [
                'title' => $booking->client->name . ' - ' . ucfirst($booking->service_type),
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
            'unpaid_invoices' => $unpaidInvoices,
            'followups_due' => $followUpsDue,
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
            'smartKPIs'
        ));
    }
}
