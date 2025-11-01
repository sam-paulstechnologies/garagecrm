@extends('layouts.app')

@section('content')
@php use Illuminate\Support\Str; @endphp

<div class="container mx-auto px-4 py-6 space-y-10">

    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    {{-- Top Stat Cards (existing) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $cards = [
                [
                    'label' => 'Total Users',
                    'value' => $stats['total_users'],
                    'color' => 'blue',
                    'icon'  => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'
                ],
                [
                    'label' => 'Total Clients',
                    'value' => $stats['total_clients'],
                    'color' => 'green',
                    'extra' => '+'.$stats['new_clients_this_month'].' this month',
                    'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857'
                ],
                [
                    'label' => 'Total Leads',
                    'value' => $stats['total_leads'],
                    'color' => 'yellow',
                    'extra' => '+'.$stats['new_leads_this_month'].' this month',
                    'icon'  => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
                ],
                [
                    'label' => 'Monthly Revenue',
                    'value' => 'AED ' . number_format($stats['revenue_this_month'], 2),
                    'color' => 'purple',
                    'extra' => $stats['bookings_this_month'] . ' bookings',
                    'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2'
                ],
            ];
        @endphp

        @foreach($cards as $card)
        <div class="bg-white rounded-lg shadow p-5 flex items-start gap-4">
            <div class="p-3 bg-{{ $card['color'] }}-100 text-{{ $card['color'] }}-600 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                </svg>
            </div>
            <div>
                <div class="text-sm text-gray-600">{{ $card['label'] }}</div>
                <div class="text-2xl font-semibold text-gray-900">{{ $card['value'] }}</div>
                @isset($card['extra'])
                    <div class="text-xs text-{{ $card['color'] }}-600 mt-1">{{ $card['extra'] }}</div>
                @endisset
            </div>
        </div>
        @endforeach
    </div>

    {{-- WhatsApp KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $wa = $waDashboard['kpis'] ?? ['sent_today'=>0,'delivered_today'=>0,'replied_today'=>0,'failed_24h'=>0];
            $waCards = [
                ['label' => 'WA Sent (Today)',   'value' => $wa['sent_today']      ?? 0, 'color' => 'emerald', 'icon' => 'M8 12h8M12 8v8'],
                ['label' => 'Delivered (Today)', 'value' => $wa['delivered_today'] ?? 0, 'color' => 'sky',     'icon' => 'M5 13l4 4L19 7'],
                ['label' => 'Replies (Today)',   'value' => $wa['replied_today']   ?? 0, 'color' => 'indigo',  'icon' => 'M7 8h10M7 12h6m-6 4h8'],
                ['label' => 'Failed (24h)',      'value' => $wa['failed_24h']      ?? 0, 'color' => 'rose',    'icon' => 'M12 9v4m0 4h.01'],
            ];
        @endphp
        @foreach($waCards as $c)
            <div class="bg-white rounded-lg shadow p-5 flex items-start gap-4">
                <div class="p-3 bg-{{ $c['color'] }}-100 text-{{ $c['color'] }}-600 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-600">{{ $c['label'] }}</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $c['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Smart KPIs (existing) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h4 class="text-sm font-medium text-gray-500">Pending Bookings</h4>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $smartKPIs['pending_bookings'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <h4 class="text-sm font-medium text-gray-500">Unpaid Invoices</h4>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $smartKPIs['unpaid_invoices'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <h4 class="text-sm font-medium text-gray-500">Follow-ups This Week</h4>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $smartKPIs['followups_due'] }}</p>
        </div>
    </div>

    {{-- WhatsApp Timeline + Attention Needed --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Timeline (2 columns) --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">WhatsApp Timeline (Latest 50)</h3>
                <div class="text-xs text-gray-500">
                    Ack: {{ $waDashboard['ack_window'] }}m · SLA: {{ $waDashboard['sla_mins'] }}m
                </div>
            </div>
            <div class="divide-y">
                @forelse(($waDashboard['timeline'] ?? []) as $m)
                    <div class="py-3 flex items-start gap-3">
                        <div class="mt-1">
                            @php
                                $isOut = $m->direction === 'out';
                                $dot   = $isOut ? 'bg-blue-500' : 'bg-green-500';
                            @endphp
                            <span class="inline-block w-2 h-2 rounded-full {{ $dot }}"></span>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    @class([
                                        'bg-blue-100 text-blue-800'   => $m->direction === 'out',
                                        'bg-green-100 text-green-800' => $m->direction === 'in',
                                    ])">
                                    {{ strtoupper($m->direction) }}
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded-full
                                    @class([
                                        'bg-gray-100 text-gray-800'       => $m->status === 'queued',
                                        'bg-sky-100 text-sky-800'         => $m->status === 'sent',
                                        'bg-emerald-100 text-emerald-800' => $m->status === 'delivered',
                                        'bg-indigo-100 text-indigo-800'   => $m->status === 'read',
                                        'bg-violet-100 text-violet-800'   => $m->status === 'replied',
                                        'bg-rose-100 text-rose-800'       => $m->status === 'failed',
                                        'bg-stone-100 text-stone-800'     => !in_array($m->status, ['queued','sent','delivered','read','replied','failed']),
                                    ])">
                                    {{ $m->status }}
                                </span>
                                @if($m->campaign_id)
                                    <span class="text-[11px] text-gray-500">Campaign #{{ $m->campaign_id }}</span>
                                @endif
                                @if($m->template?->name)
                                    <span class="text-[11px] text-gray-500">Template: {{ $m->template->name }}</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-800 mt-1">
                                To: <span class="font-medium">{{ $m->to }}</span>
                                <span class="mx-2 text-gray-300">•</span>
                                <span class="text-gray-500">{{ $m->created_at->format('d M, H:i') }}</span>
                            </div>
                            @if($m->error_message && $m->status === 'failed')
                                <div class="text-xs text-rose-700 bg-rose-50 border border-rose-100 rounded p-2 mt-2">
                                    {{ Str::limit($m->error_message, 140) }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No WhatsApp activity yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Attention Needed --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Attention Needed</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded bg-amber-50">
                    <div class="text-sm text-amber-800">Follow-ups Due</div>
                    <div class="text-xl font-semibold text-amber-900">{{ $waDashboard['due_count'] ?? 0 }}</div>
                </div>
                <div class="flex items-center justify-between p-3 rounded bg-rose-50">
                    <div class="text-sm text-rose-800">Overdue (SLA)</div>
                    <div class="text-xl font-semibold text-rose-900">{{ $waDashboard['overdue_count'] ?? 0 }}</div>
                </div>
                <div class="flex items-center justify-between p-3 rounded bg-slate-50">
                    <div class="text-sm text-slate-700">Failed Jobs (Queue)</div>
                    <div class="text-xl font-semibold text-slate-900">{{ $waDashboard['failed_jobs'] ?? 0 }}</div>
                </div>
            </div>
            <div class="mt-4 text-xs text-gray-500">
                * Due = no reply within {{ $waDashboard['ack_window'] ?? 20 }}m of last outbound.<br>
                * Overdue = still no reply past {{ $waDashboard['sla_mins'] ?? 120 }}m.
            </div>
        </div>
    </div>

    {{-- Revenue Trend Chart (existing) --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend (Last 6 Months)</h3>
        @php
            $monthlyData = $monthlyRevenue->toArray();
            $maxRevenue = count($monthlyData) ? max(array_column($monthlyData, 'revenue')) : 0;
        @endphp
        <div class="h-64 flex items-end justify-between space-x-3">
            @foreach($monthlyRevenue as $data)
                @php $height = $maxRevenue > 0 ? ($data['revenue'] / $maxRevenue) * 200 : 0; @endphp
                <div class="flex flex-col items-center w-1/6">
                    <div class="bg-blue-500 w-6 rounded-t" style="height: {{ $height }}px;"></div>
                    <p class="text-xs text-gray-600 mt-2">{{ $data['month'] }}</p>
                    <p class="text-xs font-medium">AED {{ number_format($data['revenue'], 0) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Leads / Bookings / Opportunities + Calendar --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        @php
            $recentData = [
                'Recent Leads' => [
                    'items' => $recentLeads,
                    'route' => 'admin.leads.index',
                    'title' => fn($item) => $item->name,
                    'subtitle' => fn($item) => $item->email,
                    'badge' => fn($item) => ucfirst($item->status ?? 'new'),
                    'badgeClass' => fn($item) => match($item->status) {
                        'qualified' => 'bg-green-100 text-green-800',
                        'new' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800'
                    }
                ],
                'Recent Bookings' => [
                    'items' => $recentBookings,
                    'route' => 'admin.bookings.index',
                    'title' => fn($item) => $item->client->name ?? 'N/A',
                    'subtitle' => fn($item) => $item->date ?? $item->booking_date,
                    'badge' => fn($item) => $item->service_type ?? 'Service',
                    'badgeClass' => fn() => 'bg-blue-100 text-blue-800'
                ],
                'Recent Opportunities' => [
                    'items' => $recentOpportunities,
                    'route' => 'admin.opportunities.index',
                    'title' => fn($item) => $item->title,
                    'subtitle' => fn($item) => $item->client->name ?? 'N/A',
                    'badge' => fn($item) => ucfirst(str_replace('_', ' ', $item->stage ?? 'new')),
                    'badgeClass' => fn($item) => match($item->stage) {
                        'closed_won' => 'bg-green-100 text-green-800',
                        'closed_lost' => 'bg-red-100 text-red-800',
                        default => 'bg-yellow-100 text-yellow-800'
                    }
                ],
            ];
        @endphp

        @foreach($recentData as $header => $block)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $header }}</h3>
                    <a href="{{ route($block['route']) }}" class="text-sm text-blue-600 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    @forelse($block['items'] as $item)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-medium text-gray-900">{{ $block['title']($item) }}</p>
                                <p class="text-sm text-gray-600">{{ $block['subtitle']($item) }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $block['badgeClass']($item) }}">
                                {{ $block['badge']($item) }}
                            </span>
                        </div>
                    @empty
                        <div class="p-3 bg-gray-50 rounded text-gray-500 text-sm">
                            No recent items.
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- Calendar --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">📅 Calendar</h3>
                <a href="{{ route('admin.calendar.index') }}" class="text-sm text-blue-600 hover:underline">Full View</a>
            </div>
            <div id="dashboard-calendar" class="mt-2 rounded border" style="min-height: 520px;"></div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $actions = [
                    ['label' => 'Add Lead',        'color' => 'blue',   'route' => 'admin.leads.create'],
                    ['label' => 'Add Client',      'color' => 'green',  'route' => 'admin.clients.create'],
                    ['label' => 'New Booking',     'color' => 'purple', 'route' => 'admin.bookings.create'],
                    ['label' => 'New Opportunity', 'color' => 'yellow', 'route' => 'admin.opportunities.create'],
                ];
            @endphp
            @foreach($actions as $action)
                <a href="{{ route($action['route']) }}" class="flex items-center p-3 bg-{{ $action['color'] }}-50 rounded-lg hover:bg-{{ $action['color'] }}-100 transition">
                    <svg class="w-5 h-5 text-{{ $action['color'] }}-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="text-sm font-medium text-{{ $action['color'] }}-900">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('styles')
    {{-- FullCalendar CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css" rel="stylesheet" />
    <style>
        #dashboard-calendar { font-size: 0.8rem; }
        .fc .fc-toolbar-title { font-size: 0.95rem; font-weight: 600; }
        .fc .fc-button { font-size: 0.7rem !important; padding: 0.25rem 0.5rem !important; }
        .fc .fc-button-primary { background-color: #3b82f6; border: none; color: #fff; }
        .fc .fc-button-primary:hover { background-color: #2563eb; }
        .fc .fc-daygrid-day-number, .fc .fc-col-header-cell-cushion { font-size: 0.75rem; }
        .fc .fc-scrollgrid-section-body td { padding: 4px !important; }
    </style>
@endpush

@push('scripts')
    {{-- FullCalendar JS --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('dashboard-calendar');
            if (!el) return;

            const calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                height: 'auto',
                timeZone: 'local',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                navLinks: true,
                nowIndicator: true,
                events: '{{ route('admin.calendar.events') }}',
                eventClick: function(info){
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.location.href = info.event.url;
                    }
                },
                loading: function(isLoading) {
                    el.style.opacity = isLoading ? '0.6' : '1';
                },
                eventSources: [{
                    url: '{{ route('admin.calendar.events') }}',
                    failure: function() { console.error('Failed to load calendar events.'); }
                }]
            });

            calendar.render();
        });
    </script>
@endpush
