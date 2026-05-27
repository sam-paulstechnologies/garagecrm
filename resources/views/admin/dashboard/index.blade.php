@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $keyCards = [
        [
            'title' => 'Leads',
            'value' => $stats['total_leads'],
            'note' => '+' . $stats['new_leads_this_month'] . ' this month',
            'url' => route('admin.leads.index'),
            'accent' => 'blue',
            'icon' => '📈',
        ],
        [
            'title' => 'Opportunities',
            'value' => $stats['total_opportunities'],
            'note' => '+' . $stats['new_opportunities_this_month'] . ' this month',
            'url' => route('admin.opportunities.index'),
            'accent' => 'orange',
            'icon' => '🎯',
        ],
        [
            'title' => 'Bookings',
            'value' => $stats['total_bookings'],
            'note' => $stats['bookings_this_month'] . ' this month',
            'url' => route('admin.bookings.index'),
            'accent' => 'blue',
            'icon' => '📅',
        ],
        [
            'title' => 'Jobs',
            'value' => $stats['total_jobs'],
            'note' => $stats['jobs_this_month'] . ' this month',
            'url' => route('admin.jobs.index'),
            'accent' => 'green',
            'icon' => '🛠️',
        ],
        [
            'title' => 'Unpaid Invoices',
            'value' => $smartKPIs['unpaid_invoices'],
            'note' => 'Payments to follow up',
            'url' => route('admin.invoices.index'),
            'accent' => 'red',
            'icon' => '💳',
        ],
        [
            'title' => 'Monthly Revenue',
            'value' => 'AED ' . number_format($stats['revenue_this_month'], 2),
            'note' => $stats['bookings_this_month'] . ' bookings',
            'url' => route('admin.invoices.index'),
            'accent' => 'orange',
            'icon' => '💰',
        ],
    ];

    $funnelItems = [
        [
            'label' => 'Leads',
            'value' => $stats['total_leads'],
            'url' => route('admin.leads.index'),
        ],
        [
            'label' => 'Opportunities',
            'value' => $stats['total_opportunities'],
            'url' => route('admin.opportunities.index'),
        ],
        [
            'label' => 'Bookings',
            'value' => $stats['total_bookings'],
            'url' => route('admin.bookings.index'),
        ],
        [
            'label' => 'Jobs',
            'value' => $stats['total_jobs'],
            'url' => route('admin.jobs.index'),
        ],
        [
            'label' => 'Invoices',
            'value' => $stats['total_invoices'],
            'url' => route('admin.invoices.index'),
        ],
    ];

    $leadStatuses = [
        'New',
        'Attempting Contact',
        'Contact on Hold',
        'Qualified',
        'Disqualified',
    ];

    $opportunityStages = [
        'New',
        'Attempting Contact',
        'Appointment',
        'Offer',
        'Closed Won',
        'Closed Lost',
    ];
@endphp

<div class="sf-page space-y-8">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-600 ring-1 ring-orange-100">
                Admin Control Center
            </div>

            <h1 class="sf-page-title mt-3">
                Admin Dashboard
            </h1>

            <p class="sf-page-subtitle">
                Welcome back, {{ auth()->user()->name }}. Track leads, bookings, jobs, revenue, and WhatsApp activity from one place.
            </p>

            <div class="mt-4">
                @include('admin.dashboard.partials._ai_status')
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.sla_dashboard') }}" class="sf-btn-secondary">
                SLA Dashboard
            </a>

            <a href="{{ route('admin.calendar.index') }}" class="sf-btn-primary">
                Open Calendar
            </a>
        </div>
    </div>

    {{-- Key KPI Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($keyCards as $card)
            @php
                $accentClass = match ($card['accent']) {
                    'orange' => 'bg-orange-50 text-orange-600 ring-orange-100',
                    'green' => 'bg-green-50 text-green-600 ring-green-100',
                    'red' => 'bg-red-50 text-red-600 ring-red-100',
                    default => 'bg-blue-50 text-blue-600 ring-blue-100',
                };

                $valueClass = match ($card['accent']) {
                    'orange' => 'text-orange-600',
                    'green' => 'text-green-600',
                    'red' => 'text-red-500',
                    default => 'text-blue-600',
                };

                $cardSpanClass = $loop->index >= 4 ? 'xl:col-span-2' : '';
            @endphp

            <a href="{{ $card['url'] }}" class="sf-stat-card group overflow-hidden {{ $cardSpanClass }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="sf-stat-label">
                            {{ $card['title'] }}
                        </div>

                        <div class="mt-2 whitespace-nowrap text-2xl font-extrabold tracking-tight {{ $valueClass }}">
                            {{ $card['value'] }}
                        </div>

                        <div class="sf-stat-note">
                            {{ $card['note'] }}
                        </div>
                    </div>

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-base ring-1 {{ $accentClass }}">
                        {{ $card['icon'] }}
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between text-xs font-bold text-slate-400">
                    <span>View details</span>
                    <span class="transition group-hover:translate-x-1 group-hover:text-orange-400">→</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Funnel + Needs Attention --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Lead Flow Funnel --}}
        <div class="sf-card xl:col-span-1">
            <div class="sf-card-header">
                <h3 class="sf-section-title">
                    Lead Flow Funnel
                </h3>
                <p class="sf-section-subtitle">
                    Leads → Opportunities → Bookings → Jobs → Invoices
                </p>
            </div>

            <div class="sf-card-body">
                <div class="space-y-3">
                    @foreach ($funnelItems as $index => $item)
                        <a href="{{ $item['url'] }}"
                           class="group block rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-2xl bg-white/5 text-xs font-extrabold text-orange-300 ring-1 ring-white/10">
                                        {{ $index + 1 }}
                                    </div>

                                    <div class="font-bold text-slate-200 group-hover:text-white">
                                        {{ $item['label'] }}
                                    </div>
                                </div>

                                <div class="text-xl font-extrabold text-white">
                                    {{ $item['value'] }}
                                </div>
                            </div>
                        </a>

                        @if (!$loop->last)
                            <div class="flex justify-center text-lg leading-none text-orange-400">
                                ↓
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Needs Attention --}}
        <div class="sf-card xl:col-span-2">
            <div class="sf-card-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="sf-section-title">
                        Needs Attention
                    </h3>
                    <p class="sf-section-subtitle">
                        Action items that need review from the garage team.
                    </p>
                </div>

                <span class="sf-badge-orange">
                    {{ $alerts->count() }} alert(s)
                </span>
            </div>

            <div class="sf-card-body">
                <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <a href="{{ route('admin.bookings.index') }}"
                       class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-5 transition hover:-translate-y-0.5 hover:border-blue-400/40">
                        <div class="text-sm font-bold text-blue-200">Pending Bookings</div>
                        <div class="mt-2 text-3xl font-extrabold text-white">
                            {{ $smartKPIs['pending_bookings'] }}
                        </div>
                        <div class="mt-1 text-xs font-semibold text-blue-200">
                            Confirm, reschedule, or reject
                        </div>
                    </a>

                    <a href="{{ route('admin.jobs.index') }}"
                       class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:-translate-y-0.5 hover:border-orange-400/30">
                        <div class="text-sm font-bold text-slate-300">Open Jobs</div>
                        <div class="mt-2 text-3xl font-extrabold text-white">
                            {{ $smartKPIs['open_jobs'] }}
                        </div>
                        <div class="mt-1 text-xs font-semibold text-slate-400">
                            Jobs not yet completed
                        </div>
                    </a>

                    <a href="{{ route('admin.invoices.index') }}"
                       class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-5 transition hover:-translate-y-0.5 hover:border-orange-400/40">
                        <div class="text-sm font-bold text-orange-300">Unpaid Invoices</div>
                        <div class="mt-2 text-3xl font-extrabold text-white">
                            {{ $smartKPIs['unpaid_invoices'] }}
                        </div>
                        <div class="mt-1 text-xs font-semibold text-orange-300">
                            AED {{ number_format($revenueSummary['pending_amount'], 2) }} pending
                        </div>
                    </a>

                    <a href="{{ route('admin.inbox.index') }}"
                       class="rounded-2xl border border-red-400/20 bg-red-500/10 p-5 transition hover:-translate-y-0.5 hover:border-red-400/40">
                        <div class="text-sm font-bold text-red-300">WhatsApp Failed</div>
                        <div class="mt-2 text-3xl font-extrabold text-white">
                            {{ $waDashboard['kpis']['failed_7d'] ?? 0 }}
                        </div>
                        <div class="mt-1 text-xs font-semibold text-red-300">
                            Failed messages in last 7 days
                        </div>
                    </a>

                    <a href="{{ route('admin.communication-logs.index') }}"
                       class="rounded-2xl border border-green-400/20 bg-green-500/10 p-5 transition hover:-translate-y-0.5 hover:border-green-400/40">
                        <div class="text-sm font-bold text-green-300">Follow-ups Due</div>
                        <div class="mt-2 text-3xl font-extrabold text-white">
                            {{ $smartKPIs['followups_due'] }}
                        </div>
                        <div class="mt-1 text-xs font-semibold text-green-300">
                            Due this week
                        </div>
                    </a>

                    <a href="{{ route('admin.inbox.index') }}"
                       class="rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:-translate-y-0.5 hover:border-orange-400/30">
                        <div class="text-sm font-bold text-slate-300">Replies 7d</div>
                        <div class="mt-2 text-3xl font-extrabold text-white">
                            {{ $waDashboard['kpis']['replies_7d'] ?? 0 }}
                        </div>
                        <div class="mt-1 text-xs font-semibold text-slate-400">
                            Customer WhatsApp replies
                        </div>
                    </a>
                </div>

                @if ($alerts->count())
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach ($alerts as $alert)
                            <a href="{{ $alert['url'] }}"
                               class="block rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-3 text-sm font-bold text-yellow-200 transition hover:bg-yellow-500/20">
                                {{ $alert['label'] }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="sf-alert-success">
                        No urgent alerts. Everything looks clean.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Pipeline Summary --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Lead Pipeline --}}
        <div class="sf-card">
            <div class="sf-card-header flex items-center justify-between gap-3">
                <div>
                    <h3 class="sf-section-title">Lead Pipeline</h3>
                    <p class="sf-section-subtitle">Lead status breakdown</p>
                </div>

                <a href="{{ route('admin.leads.index') }}" class="sf-link">View</a>
            </div>

            <div class="sf-card-body space-y-3">
                @foreach ($leadStatuses as $status)
                    @php
                        $count = $leadPipeline[$status] ?? $leadPipeline[strtolower($status)] ?? 0;
                    @endphp

                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm">
                        <span class="font-semibold text-slate-300">{{ $status }}</span>
                        <span class="sf-badge-blue">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Opportunity Pipeline --}}
        <div class="sf-card">
            <div class="sf-card-header flex items-center justify-between gap-3">
                <div>
                    <h3 class="sf-section-title">Opportunity Pipeline</h3>
                    <p class="sf-section-subtitle">Opportunity stage breakdown</p>
                </div>

                <a href="{{ route('admin.opportunities.index') }}" class="sf-link">View</a>
            </div>

            <div class="sf-card-body space-y-3">
                @foreach ($opportunityStages as $stage)
                    @php
                        $count = $opportunityPipeline[$stage] ?? $opportunityPipeline[strtolower($stage)] ?? 0;
                    @endphp

                    <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm">
                        <span class="font-semibold text-slate-300">{{ $stage }}</span>
                        <span class="sf-badge-orange">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Revenue Snapshot --}}
        <div class="sf-gradient-panel">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-extrabold text-white">Revenue Snapshot</h3>
                    <p class="mt-1 text-xs font-medium text-blue-50">Invoice and payment summary</p>
                </div>

                <a href="{{ route('admin.invoices.index') }}"
                   class="rounded-xl bg-white/15 px-3 py-2 text-sm font-bold text-white ring-1 ring-white/20 transition hover:bg-white/25">
                    View
                </a>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                    <span class="font-semibold text-blue-50">Paid this month</span>
                    <span class="font-extrabold text-white">AED {{ number_format($revenueSummary['paid_this_month'], 2) }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                    <span class="font-semibold text-blue-50">Pending amount</span>
                    <span class="font-extrabold text-white">AED {{ number_format($revenueSummary['pending_amount'], 2) }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                    <span class="font-semibold text-blue-50">Unpaid invoices</span>
                    <span class="font-extrabold text-white">{{ $revenueSummary['unpaid_invoice_count'] }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                    <span class="font-semibold text-blue-50">Average invoice</span>
                    <span class="font-extrabold text-white">AED {{ number_format($revenueSummary['average_invoice'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Full Row --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Recent Leads --}}
        <div class="sf-card">
            <div class="sf-card-header flex items-center justify-between gap-3">
                <h3 class="sf-section-title">Recent Leads</h3>
                <a href="{{ route('admin.leads.index') }}" class="sf-link">View All</a>
            </div>

            <div class="sf-card-body">
                @forelse($recentLeads as $lead)
                    <a href="{{ route('admin.leads.show', $lead->id) }}"
                       class="mb-2 block rounded-2xl border border-white/10 bg-slate-950/60 p-3 transition hover:border-orange-400/30 hover:bg-slate-900">
                        <div class="font-bold text-white">{{ $lead->name ?? 'Unnamed Lead' }}</div>
                        <div class="text-xs font-medium text-slate-400">{{ $lead->email ?? $lead->phone ?? 'No contact info' }}</div>
                    </a>
                @empty
                    <div class="sf-empty">No leads</div>
                @endforelse
            </div>
        </div>

        {{-- Recent Bookings --}}
        <div class="sf-card">
            <div class="sf-card-header flex items-center justify-between gap-3">
                <h3 class="sf-section-title">Recent Bookings</h3>
                <a href="{{ route('admin.bookings.index') }}" class="sf-link">View All</a>
            </div>

            <div class="sf-card-body">
                @forelse($recentBookings as $booking)
                    <a href="{{ route('admin.bookings.show', $booking->id) }}"
                       class="mb-2 block rounded-2xl border border-white/10 bg-slate-950/60 p-3 transition hover:border-orange-400/30 hover:bg-slate-900">
                        <div class="font-bold text-white">{{ $booking->client->name ?? 'Client' }}</div>
                        <div class="text-xs font-medium text-slate-400">{{ $booking->booking_date }}</div>
                    </a>
                @empty
                    <div class="sf-empty">No bookings</div>
                @endforelse
            </div>
        </div>

        {{-- Recent Opportunities --}}
        <div class="sf-card">
            <div class="sf-card-header flex items-center justify-between gap-3">
                <h3 class="sf-section-title">Recent Opportunities</h3>
                <a href="{{ route('admin.opportunities.index') }}" class="sf-link">View All</a>
            </div>

            <div class="sf-card-body">
                @forelse($recentOpportunities as $opp)
                    <a href="{{ route('admin.opportunities.show', $opp->id) }}"
                       class="mb-2 block rounded-2xl border border-white/10 bg-slate-950/60 p-3 transition hover:border-orange-400/30 hover:bg-slate-900">
                        <div class="font-bold text-white">{{ $opp->title ?? 'Opportunity' }}</div>
                        <div class="text-xs font-medium text-slate-400">{{ $opp->client->name ?? 'Client' }}</div>
                    </a>
                @empty
                    <div class="sf-empty">No opportunities</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Full Width Calendar --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="sf-section-title flex items-center gap-2">
                    📅 Calendar
                </h3>
                <p class="sf-section-subtitle">
                    Upcoming bookings and garage schedule.
                </p>
            </div>

            <a href="{{ route('admin.calendar.index') }}" class="sf-link">
                Full Calendar View
            </a>
        </div>

        <div class="sf-card-body">
            <div id="dashboard-calendar" class="dashboard-calendar"></div>
        </div>
    </div>

    {{-- WhatsApp Health --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="sf-section-title">WhatsApp Health</h3>
                <p class="sf-section-subtitle">
                    WhatsApp activity from message logs. Today can be zero if no messages were sent today.
                </p>
            </div>

            <a href="{{ route('admin.inbox.index') }}" class="sf-link">
                Open Inbox
            </a>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-5">
                <div class="sf-mini-card">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Sent Today</div>
                    <div class="mt-1 text-2xl font-extrabold text-white">
                        {{ $waDashboard['kpis']['sent_today'] }}
                    </div>
                </div>

                <div class="sf-mini-card">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Outbound 7d</div>
                    <div class="mt-1 text-2xl font-extrabold text-white">
                        {{ $waDashboard['kpis']['outbound_7d'] }}
                    </div>
                </div>

                <div class="sf-mini-card">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Replies 7d</div>
                    <div class="mt-1 text-2xl font-extrabold text-white">
                        {{ $waDashboard['kpis']['replies_7d'] }}
                    </div>
                </div>

                <div class="sf-mini-card">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Failed 7d</div>
                    <div class="mt-1 text-2xl font-extrabold text-red-400">
                        {{ $waDashboard['kpis']['failed_7d'] }}
                    </div>
                </div>

                <div class="sf-mini-card">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">AI Replies 7d</div>
                    <div class="mt-1 text-2xl font-extrabold text-blue-300">
                        {{ $waDashboard['kpis']['ai_replies_7d'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h3 class="sf-section-title">Quick Actions</h3>
            <p class="sf-section-subtitle">
                Create records quickly without leaving the dashboard flow.
            </p>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('admin.leads.create') }}" class="sf-btn-soft-blue">
                    + Add Lead
                </a>

                <a href="{{ route('admin.clients.create') }}" class="sf-btn-secondary">
                    + Add Client
                </a>

                <a href="{{ route('admin.bookings.create') }}" class="sf-btn-primary">
                    + New Booking
                </a>

                <a href="{{ route('admin.opportunities.create') }}" class="sf-btn-orange">
                    + New Opportunity
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">

<style>
.dashboard-calendar {
    min-height: 620px;
    border-radius: 16px;
}

.fc {
    font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: #e2e8f0;
}

.fc .fc-toolbar {
    flex-wrap: wrap;
    gap: 8px;
}

.fc .fc-toolbar-title {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 800;
}

.fc .fc-button {
    border: 0 !important;
    border-radius: 12px !important;
    background: #f97316 !important;
    padding: 0.4rem 0.7rem !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    box-shadow: none !important;
}

.fc .fc-button:hover {
    background: #ea580c !important;
}

.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    background: #2563eb !important;
}

.fc-theme-standard td,
.fc-theme-standard th,
.fc-theme-standard .fc-scrollgrid {
    border-color: rgba(255, 255, 255, 0.12);
}

.fc-theme-standard .fc-scrollgrid {
    background: rgba(15, 23, 42, 0.45);
}

.fc .fc-col-header-cell-cushion,
.fc .fc-daygrid-day-number {
    color: #cbd5e1;
    font-weight: 700;
}

.fc .fc-day-today {
    background: rgba(249, 115, 22, 0.12) !important;
}

.fc-daygrid-event {
    border-radius: 10px;
    padding: 3px 7px;
    font-size: 0.78rem;
    font-weight: 700;
}

.fc-scroller {
    scrollbar-width: none;
}

.fc-scroller::-webkit-scrollbar {
    display: none;
}

@media (max-width: 640px) {
    .dashboard-calendar {
        min-height: 460px;
    }

    .fc .fc-toolbar-title {
        font-size: 0.95rem;
    }

    .fc .fc-button {
        padding: 0.25rem 0.45rem !important;
        font-size: 0.72rem !important;
    }
}
/* =========================================================
   Dashboard Light Mode Correction v2
   ========================================================= */

/* Top navigation light mode */
html[data-theme="light"] nav,
html[data-theme="light"] .sf-topbar,
html[data-theme="light"] .sf-navbar,
html[data-theme="light"] .app-navigation,
html[data-theme="light"] header {
    background: #ffffff !important;
    color: #0f172a !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08) !important;
}

html[data-theme="light"] nav a,
html[data-theme="light"] header a,
html[data-theme="light"] nav button,
html[data-theme="light"] header button {
    color: #0f172a !important;
}

html[data-theme="light"] nav .bg-slate-950,
html[data-theme="light"] nav .bg-slate-900,
html[data-theme="light"] nav .bg-white\/5,
html[data-theme="light"] header .bg-slate-950,
html[data-theme="light"] header .bg-slate-900,
html[data-theme="light"] header .bg-white\/5 {
    background: #f8fafc !important;
    color: #0f172a !important;
}

/* Dashboard width and breathing space */
html[data-theme="light"] .sf-page {
    background: #f4f7fb !important;
}

html[data-theme="light"] main {
    background: #f4f7fb !important;
}

/* Card consistency */
html[data-theme="light"] .sf-card,
html[data-theme="light"] .sf-stat-card,
html[data-theme="light"] .sf-panel,
html[data-theme="light"] .rounded-2xl,
html[data-theme="light"] .rounded-3xl {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07) !important;
}

/* Inner rows should not look like dark pills */
html[data-theme="light"] .sf-page .bg-slate-950\/60,
html[data-theme="light"] .sf-page .bg-slate-900\/60,
html[data-theme="light"] .sf-page .bg-slate-900,
html[data-theme="light"] .sf-page .bg-slate-800,
html[data-theme="light"] .sf-page .bg-gray-700,
html[data-theme="light"] .sf-page .bg-gray-800 {
    background: #f8fafc !important;
    color: #0f172a !important;
    border-color: #e2e8f0 !important;
}

/* Text readability */
html[data-theme="light"] .sf-page h1,
html[data-theme="light"] .sf-page h2,
html[data-theme="light"] .sf-page h3,
html[data-theme="light"] .sf-page h4,
html[data-theme="light"] .sf-page p,
html[data-theme="light"] .sf-page span,
html[data-theme="light"] .sf-page div {
    color: inherit;
}

html[data-theme="light"] .sf-page .text-white,
html[data-theme="light"] .sf-page .text-slate-50,
html[data-theme="light"] .sf-page .text-slate-100,
html[data-theme="light"] .sf-page .text-slate-200 {
    color: #0f172a !important;
}

html[data-theme="light"] .sf-page .text-slate-300,
html[data-theme="light"] .sf-page .text-slate-400,
html[data-theme="light"] .sf-page .text-gray-300,
html[data-theme="light"] .sf-page .text-gray-400,
html[data-theme="light"] .sf-page .text-white\/60,
html[data-theme="light"] .sf-page .text-white\/70 {
    color: #64748b !important;
}

/* Revenue snapshot should stay premium and readable */
html[data-theme="light"] .sf-gradient-panel,
html[data-theme="light"] .revenue-card,
html[data-theme="light"] .revenue-snapshot {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 45%, #f97316 100%) !important;
    border: 0 !important;
    color: #ffffff !important;
}

html[data-theme="light"] .sf-gradient-panel *,
html[data-theme="light"] .revenue-card *,
html[data-theme="light"] .revenue-snapshot * {
    color: #ffffff !important;
}

html[data-theme="light"] .sf-gradient-panel .bg-white\/10,
html[data-theme="light"] .sf-gradient-panel .bg-white\/15,
html[data-theme="light"] .sf-gradient-panel .bg-white\/20 {
    background: rgba(255, 255, 255, 0.20) !important;
}

/* WhatsApp Health cards */
html[data-theme="light"] .sf-page .whatsapp-health-card,
html[data-theme="light"] .sf-page .health-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #0f172a !important;
}

html[data-theme="light"] .sf-page .whatsapp-health-card *,
html[data-theme="light"] .sf-page .health-card * {
    color: #0f172a !important;
}

/* Quick Actions */
html[data-theme="light"] .sf-page .quick-actions,
html[data-theme="light"] .sf-page .quick-action-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
}

html[data-theme="light"] .sf-page .quick-actions .btn,
html[data-theme="light"] .sf-page .quick-actions a,
html[data-theme="light"] .sf-page .quick-actions button {
    opacity: 1 !important;
    color: #ffffff !important;
    background: #f97316 !important;
    border-color: #f97316 !important;
    box-shadow: 0 10px 22px rgba(249, 115, 22, 0.22) !important;
}

html[data-theme="light"] .sf-page .quick-actions .btn:first-child,
html[data-theme="light"] .sf-page .quick-actions a:first-child,
html[data-theme="light"] .sf-page .quick-actions button:first-child {
    color: #1d4ed8 !important;
    background: #eff6ff !important;
    border-color: #bfdbfe !important;
}

/* Calendar */
html[data-theme="light"] .fc,
html[data-theme="light"] .fc-view-harness,
html[data-theme="light"] .fc-scrollgrid {
    background: #ffffff !important;
}

html[data-theme="light"] .fc-daygrid-day {
    background: #ffffff !important;
}

html[data-theme="light"] .fc-daygrid-day:hover {
    background: #f8fafc !important;
}

html[data-theme="light"] .fc-day-today {
    background: #fff7ed !important;
}

/* Make disabled/faded looking elements readable */
html[data-theme="light"] .opacity-40,
html[data-theme="light"] .opacity-50,
html[data-theme="light"] .opacity-60 {
    opacity: 1 !important;
}

</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('dashboard-calendar');
    if (!el) return;

    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        height: 620,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: @json($calendarEvents),
        eventClick(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        }
    });

    calendar.render();
});
</script>
@endpush