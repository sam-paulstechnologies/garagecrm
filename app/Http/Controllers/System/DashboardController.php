<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\Booking;
use App\Models\Tenant\Lead;
use App\Models\Tenant\Job;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Client;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $companyId = auth()->user()->company_id;

        // Today's bookings
        $bookingsToday = Booking::where('company_id', $companyId)
                                ->whereDate('date', $today)
                                ->count();

        // Today's leads
        $leadsPending = Lead::where('company_id', $companyId)
                            ->whereDate('created_at', $today)
                            ->count();

        // Pending jobs created today
        $jobsInProgress = Job::where('company_id', $companyId)
                             ->where('status', 'pending')
                             ->whereDate('created_at', $today)
                             ->count();

        // Invoices due today and not yet paid
        $invoicesDue = Invoice::where('company_id', $companyId)
                              ->whereDate('due_date', $today)
                              ->where('status', '!=', 'paid')
                              ->count();

        // Top 5 clients by number of bookings
        $topClients = DB::table('clients')
            ->join('bookings', 'clients.id', '=', 'bookings.client_id')
            ->select('clients.id', 'clients.name', DB::raw('COUNT(bookings.id) as totalBookings'))
            ->where('clients.company_id', $companyId)
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('totalBookings')
            ->limit(5)
            ->get();

        return view('dashboards', compact(
            'bookingsToday',
            'leadsPending',
            'jobsInProgress',
            'invoicesDue',
            'topClients'
        ));
    }
}
