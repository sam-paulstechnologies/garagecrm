@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $keyCards = [
        [
            'title' => 'Leads',
            'value' => $stats['total_leads'],
            'note' => '+' . $stats['new_leads_this_month'] . ' this month',
            'url' => route('admin.leads.index'),
            'bg' => 'bg-white',
            'text' => 'text-gray-900',
        ],
        [
            'title' => 'Opportunities',
            'value' => $stats['total_opportunities'],
            'note' => '+' . $stats['new_opportunities_this_month'] . ' this month',
            'url' => route('admin.opportunities.index'),
            'bg' => 'bg-white',
            'text' => 'text-gray-900',
        ],
        [
            'title' => 'Bookings',
            'value' => $stats['total_bookings'],
            'note' => $stats['bookings_this_month'] . ' this month',
            'url' => route('admin.bookings.index'),
            'bg' => 'bg-white',
            'text' => 'text-gray-900',
        ],
        [
            'title' => 'Jobs',
            'value' => $stats['total_jobs'],
            'note' => $stats['jobs_this_month'] . ' this month',
            'url' => route('admin.jobs.index'),
            'bg' => 'bg-white',
            'text' => 'text-gray-900',
        ],
        [
            'title' => 'Unpaid Invoices',
            'value' => $smartKPIs['unpaid_invoices'],
            'note' => 'Payments to follow up',
            'url' => route('admin.invoices.index'),
            'bg' => 'bg-orange-50',
            'text' => 'text-orange-900',
        ],
        [
            'title' => 'Monthly Revenue',
            'value' => 'AED ' . number_format($stats['revenue_this_month'], 2),
            'note' => $stats['bookings_this_month'] . ' bookings',
            'url' => route('admin.invoices.index'),
            'bg' => 'bg-white',
            'text' => 'text-gray-900',
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

<div class="container mx-auto px-4 py-6 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="text-gray-600 mt-1">
                Welcome back, {{ auth()->user()->name }}!
            </p>

            @include('admin.dashboard.partials._ai_status')
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.sla_dashboard') }}"
               class="px-4 py-2 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100">
                SLA Dashboard
            </a>

            <a href="{{ route('admin.calendar.index') }}"
               class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                Open Calendar
            </a>
        </div>
    </div>

    {{-- Key KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-5">
        @foreach ($keyCards as $card)
            <a href="{{ $card['url'] }}"
               class="{{ $card['bg'] }} rounded-xl shadow-sm border border-gray-100 p-5 block hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm text-gray-500 font-medium">
                            {{ $card['title'] }}
                        </div>

                        <div class="text-2xl font-bold {{ $card['text'] }} mt-2">
                            {{ $card['value'] }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            {{ $card['note'] }}
                        </div>
                    </div>

                    <div class="text-gray-300 group-hover:text-gray-700 transition">
                        →
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Funnel + Needs Attention --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Lead Flow Funnel --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 xl:col-span-1">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-semibold text-gray-900">Lead Flow Funnel</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Leads → Opportunities → Bookings → Jobs → Invoices
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach ($funnelItems as $index => $item)
                    <a href="{{ $item['url'] }}"
                       class="block rounded-lg border border-gray-100 bg-gray-50 hover:bg-gray-100 transition">
                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-white border flex items-center justify-center text-xs font-semibold text-gray-600">
                                    {{ $index + 1 }}
                                </div>

                                <div class="font-medium text-gray-700">
                                    {{ $item['label'] }}
                                </div>
                            </div>

                            <div class="text-xl font-bold text-gray-900">
                                {{ $item['value'] }}
                            </div>
                        </div>
                    </a>

                    @if (!$loop->last)
                        <div class="flex justify-center text-gray-300 text-lg leading-none">
                            ↓
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Needs Attention --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-semibold text-gray-900">Needs Attention</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Action items that need review from the garage team.
                    </p>
                </div>

                <span class="text-xs text-gray-500">{{ $alerts->count() }} alert(s)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-5">
                <a href="{{ route('admin.bookings.index') }}"
                   class="bg-blue-50 border border-blue-100 rounded-xl p-5 hover:shadow-md transition">
                    <div class="text-sm text-blue-700 font-medium">Pending Bookings</div>
                    <div class="text-3xl font-bold text-blue-900 mt-2">
                        {{ $smartKPIs['pending_bookings'] }}
                    </div>
                    <div class="text-xs text-blue-700 mt-1">
                        Confirm, reschedule, or reject
                    </div>
                </a>

                <a href="{{ route('admin.jobs.index') }}"
                   class="bg-purple-50 border border-purple-100 rounded-xl p-5 hover:shadow-md transition">
                    <div class="text-sm text-purple-700 font-medium">Open Jobs</div>
                    <div class="text-3xl font-bold text-purple-900 mt-2">
                        {{ $smartKPIs['open_jobs'] }}
                    </div>
                    <div class="text-xs text-purple-700 mt-1">
                        Jobs not yet completed
                    </div>
                </a>

                <a href="{{ route('admin.invoices.index') }}"
                   class="bg-orange-50 border border-orange-100 rounded-xl p-5 hover:shadow-md transition">
                    <div class="text-sm text-orange-700 font-medium">Unpaid Invoices</div>
                    <div class="text-3xl font-bold text-orange-900 mt-2">
                        {{ $smartKPIs['unpaid_invoices'] }}
                    </div>
                    <div class="text-xs text-orange-700 mt-1">
                        AED {{ number_format($revenueSummary['pending_amount'], 2) }} pending
                    </div>
                </a>

                <a href="{{ route('admin.inbox.index') }}"
                   class="bg-red-50 border border-red-100 rounded-xl p-5 hover:shadow-md transition">
                    <div class="text-sm text-red-700 font-medium">WhatsApp Failed</div>
                    <div class="text-3xl font-bold text-red-900 mt-2">
                        {{ $waDashboard['kpis']['failed_7d'] ?? 0 }}
                    </div>
                    <div class="text-xs text-red-700 mt-1">
                        Failed messages in last 7 days
                    </div>
                </a>

                <a href="{{ route('admin.communication-logs.index') }}"
                   class="bg-green-50 border border-green-100 rounded-xl p-5 hover:shadow-md transition">
                    <div class="text-sm text-green-700 font-medium">Follow-ups Due</div>
                    <div class="text-3xl font-bold text-green-900 mt-2">
                        {{ $smartKPIs['followups_due'] }}
                    </div>
                    <div class="text-xs text-green-700 mt-1">
                        Due this week
                    </div>
                </a>

                <a href="{{ route('admin.inbox.index') }}"
                   class="bg-gray-50 border border-gray-100 rounded-xl p-5 hover:shadow-md transition">
                    <div class="text-sm text-gray-700 font-medium">Replies 7d</div>
                    <div class="text-3xl font-bold text-gray-900 mt-2">
                        {{ $waDashboard['kpis']['replies_7d'] ?? 0 }}
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        Customer WhatsApp replies
                    </div>
                </a>
            </div>

            @if ($alerts->count())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($alerts as $alert)
                        <a href="{{ $alert['url'] }}"
                           class="block bg-yellow-50 border border-yellow-100 rounded-lg px-4 py-3 text-sm text-yellow-900 hover:bg-yellow-100 transition">
                            {{ $alert['label'] }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-gray-500 bg-gray-50 rounded-lg px-4 py-3">
                    No urgent alerts. Everything looks clean.
                </div>
            @endif
        </div>
    </div>

    {{-- Pipeline Summary --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Lead Pipeline --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Lead Pipeline</h3>
                <a href="{{ route('admin.leads.index') }}" class="text-sm text-blue-600 hover:underline">View</a>
            </div>

            <div class="space-y-3">
                @foreach ($leadStatuses as $status)
                    @php
                        $count = $leadPipeline[$status] ?? $leadPipeline[strtolower($status)] ?? 0;
                    @endphp

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">{{ $status }}</span>
                        <span class="font-semibold text-gray-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Opportunity Pipeline --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Opportunity Pipeline</h3>
                <a href="{{ route('admin.opportunities.index') }}" class="text-sm text-blue-600 hover:underline">View</a>
            </div>

            <div class="space-y-3">
                @foreach ($opportunityStages as $stage)
                    @php
                        $count = $opportunityPipeline[$stage] ?? $opportunityPipeline[strtolower($stage)] ?? 0;
                    @endphp

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">{{ $stage }}</span>
                        <span class="font-semibold text-gray-900">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Revenue Snapshot --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Revenue Snapshot</h3>
                <a href="{{ route('admin.invoices.index') }}" class="text-sm text-blue-600 hover:underline">View</a>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Paid this month</span>
                    <span class="font-semibold text-gray-900">AED {{ number_format($revenueSummary['paid_this_month'], 2) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Pending amount</span>
                    <span class="font-semibold text-gray-900">AED {{ number_format($revenueSummary['pending_amount'], 2) }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Unpaid invoices</span>
                    <span class="font-semibold text-gray-900">{{ $revenueSummary['unpaid_invoice_count'] }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Average invoice</span>
                    <span class="font-semibold text-gray-900">AED {{ number_format($revenueSummary['average_invoice'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Full Row --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Recent Leads --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Recent Leads</h3>
                <a href="{{ route('admin.leads.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
            </div>

            @forelse($recentLeads as $lead)
                <a href="{{ route('admin.leads.show', $lead->id) }}"
                   class="block p-3 bg-gray-50 rounded-lg mb-2 hover:bg-gray-100 transition">
                    <div class="font-medium text-gray-900">{{ $lead->name ?? 'Unnamed Lead' }}</div>
                    <div class="text-xs text-gray-500">{{ $lead->email ?? $lead->phone ?? 'No contact info' }}</div>
                </a>
            @empty
                <div class="text-sm text-gray-400">No leads</div>
            @endforelse
        </div>

        {{-- Recent Bookings --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Recent Bookings</h3>
                <a href="{{ route('admin.bookings.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
            </div>

            @forelse($recentBookings as $booking)
                <a href="{{ route('admin.bookings.show', $booking->id) }}"
                   class="block p-3 bg-gray-50 rounded-lg mb-2 hover:bg-gray-100 transition">
                    <div class="font-medium text-gray-900">{{ $booking->client->name ?? 'Client' }}</div>
                    <div class="text-xs text-gray-500">{{ $booking->booking_date }}</div>
                </a>
            @empty
                <div class="text-sm text-gray-400">No bookings</div>
            @endforelse
        </div>

        {{-- Recent Opportunities --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Recent Opportunities</h3>
                <a href="{{ route('admin.opportunities.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
            </div>

            @forelse($recentOpportunities as $opp)
                <a href="{{ route('admin.opportunities.show', $opp->id) }}"
                   class="block p-3 bg-gray-50 rounded-lg mb-2 hover:bg-gray-100 transition">
                    <div class="font-medium text-gray-900">{{ $opp->title ?? 'Opportunity' }}</div>
                    <div class="text-xs text-gray-500">{{ $opp->client->name ?? 'Client' }}</div>
                </a>
            @empty
                <div class="text-sm text-gray-400">No opportunities</div>
            @endforelse
        </div>
    </div>

    {{-- Full Width Calendar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h3 class="font-semibold flex items-center gap-2 text-gray-900">
                    📅 Calendar
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Upcoming bookings and garage schedule.
                </p>
            </div>

            <a href="{{ route('admin.calendar.index') }}" class="text-sm text-blue-600 hover:underline">
                Full Calendar View
            </a>
        </div>

        <div id="dashboard-calendar" class="dashboard-calendar"></div>
    </div>

    {{-- WhatsApp Health --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
            <div>
                <h3 class="font-semibold text-gray-900">WhatsApp Health</h3>
                <p class="text-sm text-gray-500">
                    WhatsApp activity from message logs. Today can be zero if no messages were sent today.
                </p>
            </div>

            <a href="{{ route('admin.inbox.index') }}"
               class="text-sm text-blue-600 hover:underline">
                Open Inbox
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500">Sent Today</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $waDashboard['kpis']['sent_today'] }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500">Outbound 7d</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $waDashboard['kpis']['outbound_7d'] }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500">Replies 7d</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $waDashboard['kpis']['replies_7d'] }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500">Failed 7d</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $waDashboard['kpis']['failed_7d'] }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500">AI Replies 7d</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $waDashboard['kpis']['ai_replies_7d'] }}
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <a href="{{ route('admin.leads.create') }}"
               class="action-btn bg-blue-50 text-blue-700">+ Add Lead</a>

            <a href="{{ route('admin.clients.create') }}"
               class="action-btn bg-green-50 text-green-700">+ Add Client</a>

            <a href="{{ route('admin.bookings.create') }}"
               class="action-btn bg-purple-50 text-purple-700">+ New Booking</a>

            <a href="{{ route('admin.opportunities.create') }}"
               class="action-btn bg-yellow-50 text-yellow-700">+ New Opportunity</a>
        </div>
    </div>

</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">

<style>
.dashboard-calendar {
    min-height: 620px;
    border-radius: 8px;
}

.fc .fc-toolbar {
    flex-wrap: wrap;
    gap: 8px;
}

.fc .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 700;
}

.fc .fc-button {
    padding: 0.35rem 0.65rem;
    font-size: 0.8rem;
}

.fc-daygrid-event {
    border-radius: 6px;
    padding: 3px 7px;
    font-size: 0.78rem;
}

.fc-scroller {
    scrollbar-width: none;
}

.fc-scroller::-webkit-scrollbar {
    display: none;
}

.action-btn {
    padding: 0.85rem;
    border-radius: 0.75rem;
    text-align: center;
    font-weight: 600;
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-1px);
}

@media (max-width: 640px) {
    .dashboard-calendar {
        min-height: 460px;
    }

    .fc .fc-toolbar-title {
        font-size: 0.95rem;
    }

    .fc .fc-button {
        padding: 0.25rem 0.45rem;
        font-size: 0.72rem;
    }
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