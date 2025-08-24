<!-- Recent Leads, Bookings, Opportunities, Calendar -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    @php
        $recentData = [
            'Recent Leads' => [
                'items' => $recentLeads,
                'route' => 'admin.leads.index',
                'title' => fn($item) => $item->name,
                'subtitle' => fn($item) => $item->email,
                'badge' => fn($item) => ucfirst($item->status),
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
                'subtitle' => fn($item) => $item->date,
                'badge' => fn($item) => $item->service_type ?? 'Service',
                'badgeClass' => fn() => 'bg-blue-100 text-blue-800'
            ],
            'Recent Opportunities' => [
                'items' => $recentOpportunities,
                'route' => 'admin.opportunities.index',
                'title' => fn($item) => $item->title,
                'subtitle' => fn($item) => $item->client->name ?? 'N/A',
                'badge' => fn($item) => ucfirst(str_replace('_', ' ', $item->stage)),
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

    {{-- 📅 Embedded FullCalendar Panel --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">📅 Calendar</h3>
            <a href="{{ route('admin.calendar.index') }}" class="text-sm text-blue-600 hover:underline">Full View</a>
        </div>
        <div id="dashboard-calendar" class="mt-2 rounded border" style="min-height: 520px;"></div>
    </div>
</div>

@push('styles')
    {{-- ✅ Correct CSS for FullCalendar --}}
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
    {{-- ✅ Use the "index.global" JS build --}}
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
                events: '{{ route('admin.calendar.events') }}', // 🔗 pulls from JSON endpoint
                eventClick: function(info){
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.location.href = info.event.url;
                    }
                },
                loading: function(isLoading) {
                    // Optional: show a simple loading state
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
