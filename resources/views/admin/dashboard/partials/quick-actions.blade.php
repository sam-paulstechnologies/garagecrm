<!-- Quick Actions -->
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
            $actions = [
                ['label' => 'Add Lead', 'color' => 'blue', 'route' => 'admin.leads.create'],
                ['label' => 'Add Client', 'color' => 'green', 'route' => 'admin.clients.create'],
                ['label' => 'New Booking', 'color' => 'purple', 'route' => 'admin.bookings.create'],
                ['label' => 'New Opportunity', 'color' => 'yellow', 'route' => 'admin.opportunities.create']
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
