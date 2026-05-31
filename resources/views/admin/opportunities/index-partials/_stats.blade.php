<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <a href="{{ route('admin.opportunities.index') }}" class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5">
        <div class="text-sm font-bold text-blue-300">Open Opportunities</div>
        <div class="mt-2 text-3xl font-extrabold sf-opportunity-value">{{ $opportunityCounts['open'] ?? 0 }}</div>
        <div class="mt-1 text-xs font-medium sf-opportunity-muted">Active pipeline</div>
    </a>

    <a href="{{ route('admin.opportunities.index', ['bucket' => 'appointment']) }}" class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5">
        <div class="text-sm font-bold text-orange-300">Appointment Planned</div>
        <div class="mt-2 text-3xl font-extrabold sf-opportunity-value">{{ $opportunityCounts['appointment'] ?? 0 }}</div>
        <div class="mt-1 text-xs font-medium sf-opportunity-muted">Ready to confirm</div>
    </a>

    <a href="{{ route('admin.opportunities.index', ['bucket' => 'missed_appointment']) }}" class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5">
        <div class="text-sm font-bold text-red-300">Missed Appointments</div>
        <div class="mt-2 text-3xl font-extrabold sf-opportunity-value">{{ $opportunityCounts['missed_appointment'] ?? 0 }}</div>
        <div class="mt-1 text-xs font-medium sf-opportunity-muted">Past appointment date</div>
    </a>

    <a href="{{ route('admin.opportunities.index', ['stage' => 'closed_won']) }}" class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5">
        <div class="text-sm font-bold text-green-300">Booking Confirmed</div>
        <div class="mt-2 text-3xl font-extrabold sf-opportunity-value">{{ $opportunityCounts['won'] ?? 0 }}</div>
        <div class="mt-1 text-xs font-medium sf-opportunity-muted">Converted to booking</div>
    </a>

    <a href="{{ route('admin.opportunities.index', ['stage' => 'closed_lost']) }}" class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm transition hover:-translate-y-0.5">
        <div class="text-sm font-bold text-red-300">Closed Lost</div>
        <div class="mt-2 text-3xl font-extrabold sf-opportunity-value">{{ $opportunityCounts['lost'] ?? 0 }}</div>
        <div class="mt-1 text-xs font-medium sf-opportunity-muted">Lost opportunities</div>
    </a>
</div>
