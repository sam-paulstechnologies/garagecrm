@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Facades\Route;
@endphp

<div class="container mx-auto px-4 py-6 space-y-10">

    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">
            Welcome back, {{ auth()->user()->name }}!
        </p>

        @include('admin.dashboard.partials._ai_status')
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ([
            ['Total Users', $stats['total_users']],
            ['Total Clients', $stats['total_clients'], '+' . $stats['new_clients_this_month'] . ' this month'],
            ['Total Leads', $stats['total_leads'], '+' . $stats['new_leads_this_month'] . ' this month'],
            ['Monthly Revenue', 'AED ' . number_format($stats['revenue_this_month'], 2), $stats['bookings_this_month'] . ' bookings'],
        ] as $card)
            <div class="bg-white rounded-lg shadow p-5">
                <div class="text-sm text-gray-600">{{ $card[0] }}</div>
                <div class="text-2xl font-semibold text-gray-900">{{ $card[1] }}</div>
                @isset($card[2])
                    <div class="text-xs text-gray-500 mt-1">{{ $card[2] }}</div>
                @endisset
            </div>
        @endforeach
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- Recent Leads --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between mb-4">
                <h3 class="font-semibold">Recent Leads</h3>
                <a href="{{ route('admin.leads.index') }}" class="text-sm text-blue-600">View All</a>
            </div>

            @forelse($recentLeads as $lead)
                <div class="p-2 bg-gray-50 rounded mb-2">
                    <div class="font-medium">{{ $lead->name }}</div>
                    <div class="text-xs text-gray-500">{{ $lead->email }}</div>
                </div>
            @empty
                <div class="text-sm text-gray-400">No leads</div>
            @endforelse
        </div>

        {{-- Recent Bookings --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between mb-4">
                <h3 class="font-semibold">Recent Bookings</h3>
                <a href="{{ route('admin.bookings.index') }}" class="text-sm text-blue-600">View All</a>
            </div>

            @forelse($recentBookings as $booking)
                <div class="p-2 bg-gray-50 rounded mb-2">
                    <div class="font-medium">{{ $booking->client->name ?? 'Client' }}</div>
                    <div class="text-xs text-gray-500">{{ $booking->booking_date }}</div>
                </div>
            @empty
                <div class="text-sm text-gray-400">No bookings</div>
            @endforelse
        </div>

        {{-- Recent Opportunities --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between mb-4">
                <h3 class="font-semibold">Recent Opportunities</h3>
                <a href="{{ route('admin.opportunities.index') }}" class="text-sm text-blue-600">View All</a>
            </div>

            @forelse($recentOpportunities as $opp)
                <div class="p-2 bg-gray-50 rounded mb-2">
                    <div class="font-medium">{{ $opp->title }}</div>
                    <div class="text-xs text-gray-500">{{ $opp->client->name ?? 'Client' }}</div>
                </div>
            @empty
                <div class="text-sm text-gray-400">No opportunities</div>
            @endforelse
        </div>

        {{-- Calendar --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold flex items-center gap-2">
                    📅 Calendar
                </h3>
                <a href="{{ route('admin.calendar.index') }}" class="text-sm text-blue-600">
                    Full View
                </a>
            </div>

            <div id="dashboard-calendar" class="dashboard-calendar"></div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-4">Quick Actions</h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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

{{-- FullCalendar v5 --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">

<style>
.dashboard-calendar {
    min-height: 420px;
    border-radius: 8px;
}

.fc .fc-toolbar-title {
    font-size: 1rem;
    font-weight: 600;
}

.fc .fc-button {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.fc-daygrid-event {
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 0.75rem;
}

.fc-scroller {
    scrollbar-width: none;
}
.fc-scroller::-webkit-scrollbar {
    display: none;
}

.action-btn {
    padding: 0.75rem;
    border-radius: 0.5rem;
    text-align: center;
    font-weight: 500;
    transition: all 0.2s ease;
}
.action-btn:hover {
    transform: translateY(-1px);
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
        height: 420,
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: 'today'
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
