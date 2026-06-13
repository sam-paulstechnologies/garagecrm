{{-- resources/views/admin/dashboard/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
    <style>
        .sf-dashboard-page {
            max-width: 1600px !important;
        }
    </style>
@endpush

@section('content')
    @php
        /*
        |--------------------------------------------------------------------------
        | Helper Functions
        |--------------------------------------------------------------------------
        */

        $toCount = function ($value, $default = 0) {
            if ($value instanceof \Illuminate\Support\Collection) {
                return $value->count();
            }

            if ($value instanceof \Illuminate\Pagination\AbstractPaginator) {
                return $value->total();
            }

            if (is_array($value)) {
                return count($value);
            }

            return is_numeric($value) ? (int) $value : (int) $default;
        };

        $toMoney = function ($value, $default = 0) {
            if ($value instanceof \Illuminate\Support\Collection) {
                return (float) $value->sum(function ($item) {
                    if (is_array($item)) {
                        return $item['amount']
                            ?? $item['total']
                            ?? $item['paid_amount']
                            ?? $item['invoice_amount']
                            ?? $item['grand_total']
                            ?? 0;
                    }

                    if (is_object($item)) {
                        return $item->amount
                            ?? $item->total
                            ?? $item->paid_amount
                            ?? $item->invoice_amount
                            ?? $item->grand_total
                            ?? 0;
                    }

                    return is_numeric($item) ? $item : 0;
                });
            }

            if (is_array($value)) {
                return (float) collect($value)->sum(function ($item) {
                    if (is_array($item)) {
                        return $item['amount']
                            ?? $item['total']
                            ?? $item['paid_amount']
                            ?? $item['invoice_amount']
                            ?? $item['grand_total']
                            ?? 0;
                    }

                    if (is_object($item)) {
                        return $item->amount
                            ?? $item->total
                            ?? $item->paid_amount
                            ?? $item->invoice_amount
                            ?? $item->grand_total
                            ?? 0;
                    }

                    return is_numeric($item) ? $item : 0;
                });
            }

            return is_numeric($value) ? (float) $value : (float) $default;
        };

        /*
        |--------------------------------------------------------------------------
        | Existing Controller Payloads
        |--------------------------------------------------------------------------
        */

        $stats = $stats ?? [];
        $smartKPIs = $smartKPIs ?? [];
        $revenueSummary = $revenueSummary ?? [];
        $waDashboard = $waDashboard ?? ['kpis' => []];

        /*
        |--------------------------------------------------------------------------
        | Dashboard Filters
        |--------------------------------------------------------------------------
        */

        $dashboardFilters = [
            'date_range' => request('date_range', 'this_month'),
            'lead_source' => request('lead_source', 'all'),
            'assigned_user' => request('assigned_user', 'all'),
            'service_type' => request('service_type', 'all'),
            'customer_type' => request('customer_type', 'all'),
            'from_date' => request('from_date'),
            'to_date' => request('to_date'),
        ];

        $dashboardPeriodLabel = match ($dashboardFilters['date_range']) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'custom' => 'Selected Period',
            'all_time' => 'All Dates',
            default => 'This Month',
        };

        $dashboardContextParts = [$dashboardPeriodLabel];

        if (($dashboardFilters['lead_source'] ?? 'all') !== 'all') {
            $dashboardContextParts[] = str($dashboardFilters['lead_source'])
                ->replace('_', ' ')
                ->title()
                ->toString();
        }

        if (($dashboardFilters['assigned_user'] ?? 'all') !== 'all') {
            $assignedUserName = collect($assignedUsers ?? [])
                ->firstWhere('id', (int) $dashboardFilters['assigned_user'])?->name;

            $dashboardContextParts[] = $assignedUserName
                ? 'Staff: ' . $assignedUserName
                : 'Selected Staff';
        }

        $dashboardContextLabel = implode(' / ', array_filter($dashboardContextParts));

        /*
        |--------------------------------------------------------------------------
        | Top Summary Counts
        |--------------------------------------------------------------------------
        */

        $leadCount = $toCount(
            $stats['total_leads']
                ?? $leadCount
                ?? $leadsCount
                ?? $totalLeads
                ?? $leads
                ?? 0
        );

        $opportunityCount = $toCount(
            $stats['total_opportunities']
                ?? $opportunityCount
                ?? $opportunitiesCount
                ?? $totalOpportunities
                ?? $opportunities
                ?? 0
        );

        $bookingCount = $toCount(
            $stats['total_bookings']
                ?? $bookingCount
                ?? $bookingsCount
                ?? $totalBookings
                ?? $bookings
                ?? 0
        );

        $jobCount = $toCount(
            $stats['total_jobs']
                ?? $jobCount
                ?? $jobsCount
                ?? $totalJobs
                ?? $jobs
                ?? 0
        );

        $invoiceCount = $toCount(
            $stats['total_invoices']
                ?? $invoiceCount
                ?? $invoicesCount
                ?? $totalInvoices
                ?? $invoices
                ?? 0
        );

        $unpaidInvoiceCount = $toCount(
            $smartKPIs['unpaid_invoices']
                ?? $revenueSummary['unpaid_invoice_count']
                ?? $unpaidInvoiceCount
                ?? $unpaidInvoicesCount
                ?? $totalUnpaidInvoices
                ?? $unpaidInvoices
                ?? 0
        );

        $monthlyRevenue = $toMoney(
            $stats['revenue_this_month']
                ?? $revenueSummary['paid_this_month']
                ?? $monthlyRevenue
                ?? $revenueThisMonth
                ?? $paidThisMonth
                ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Needs Attention
        |--------------------------------------------------------------------------
        */

        $pendingBookingsCount = $toCount(
            $smartKPIs['pending_bookings']
                ?? $pendingBookingsCount
                ?? $pendingBookings
                ?? 0
        );

        $openJobsCount = $toCount(
            $smartKPIs['open_jobs']
                ?? $openJobsCount
                ?? $openJobs
                ?? 0
        );

        $whatsappFailedCount = $toCount(
            $smartKPIs['whatsapp_failed']
                ?? $waDashboard['kpis']['failed_7d']
                ?? $whatsappFailedCount
                ?? $failedWhatsAppCount
                ?? 0
        );

        $followUpsDueCount = $toCount(
            $smartKPIs['followups_due']
                ?? $followUpsDueCount
                ?? $dueFollowUpsCount
                ?? 0
        );

        $replies7dCount = $toCount(
            $waDashboard['kpis']['replies_7d']
                ?? $replies7dCount
                ?? $whatsappReplies7d
                ?? 0
        );

        $unpaidAmount = $toMoney(
            $revenueSummary['pending_amount']
                ?? $unpaidAmount
                ?? $pendingInvoiceAmount
                ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Revenue Snapshot
        |--------------------------------------------------------------------------
        */

        $paidThisMonth = $toMoney(
            $revenueSummary['paid_this_month']
                ?? $monthlyRevenue
                ?? 0
        );

        $pendingAmount = $toMoney(
            $revenueSummary['pending_amount']
                ?? $unpaidAmount
                ?? 0
        );

        $averageInvoice = $toMoney(
            $revenueSummary['average_invoice']
                ?? $averageInvoice
                ?? $avgInvoiceAmount
                ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Pipelines
        |--------------------------------------------------------------------------
        */

        $leadPipeline = $leadPipeline ?? [
            'New' => 0,
            'Attempting Contact' => 0,
            'Contact on Hold' => 0,
            'Qualified' => 0,
            'Disqualified' => 0,
        ];

        $opportunityPipeline = $opportunityPipeline ?? [
            'New' => 0,
            'Attempting Contact' => 0,
            'Appointment' => 0,
            'Offer' => 0,
            'Closed Won' => 0,
            'Closed Lost' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Recent Lists
        |--------------------------------------------------------------------------
        */

        $recentLeads = collect($recentLeads ?? $latestLeads ?? []);
        $recentBookings = collect($recentBookings ?? $latestBookings ?? []);
        $recentOpportunities = collect($recentOpportunities ?? $latestOpportunities ?? []);

        /*
        |--------------------------------------------------------------------------
        | Calendar
        |--------------------------------------------------------------------------
        */

        $calendarEvents = collect(
            $calendarEvents
                ?? $upcomingBookings
                ?? $upcomingEvents
                ?? []
        );

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Health
        |--------------------------------------------------------------------------
        */

        $sentToday = $toCount(
            $waDashboard['kpis']['sent_today']
                ?? $sentToday
                ?? $whatsappSentToday
                ?? $messagesSentToday
                ?? 0
        );

        $outbound7d = $toCount(
            $waDashboard['kpis']['outbound_7d']
                ?? $outbound7d
                ?? $whatsappOutbound7d
                ?? $messagesOutbound7d
                ?? 0
        );

        $replies7d = $toCount(
            $waDashboard['kpis']['replies_7d']
                ?? $replies7d
                ?? $whatsappReplies7d
                ?? $customerReplies7d
                ?? 0
        );

        $failed7d = $toCount(
            $waDashboard['kpis']['failed_7d']
                ?? $failed7d
                ?? $whatsappFailed7d
                ?? $failedWhatsAppCount
                ?? 0
        );

        $aiReplies7d = $toCount(
            $waDashboard['kpis']['ai_replies_7d']
                ?? $aiReplies7d
                ?? $whatsappAiReplies7d
                ?? $aiResponseCount7d
                ?? 0
        );
    @endphp

    <div class="sf-page sf-dashboard-page mx-auto w-full max-w-[1600px] px-4 py-4 sm:px-6 lg:px-8 space-y-3">

        {{-- Hero --}}
        @include('admin.dashboard.partials._hero')

        {{-- Dashboard Filters --}}
        @include('admin.dashboard.partials._dashboard_filters')

        {{-- Top KPI Cards --}}
        @include('admin.dashboard.partials._summary_cards')

        {{-- Funnel + Needs Attention --}}
        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
            @include('admin.dashboard.partials._lead_flow')
            @include('admin.dashboard.partials._needs_attention')
        </div>

        {{-- Lead Pipeline + Opportunity Pipeline + Revenue Snapshot --}}
        @include('admin.dashboard.partials._pipeline_panels')

        {{-- Recent Leads / Bookings / Opportunities --}}
        @include('admin.dashboard.partials.recent-panels')

        {{-- Calendar Preview --}}
        @include('admin.dashboard.partials._calendar_preview')

        {{-- WhatsApp Health --}}
        @include('admin.dashboard.partials._whatsapp_health')

        {{-- Dashboard Bottom Quick Actions --}}
        @include('admin.dashboard.partials._quick_actions')

    </div>
@endsection
