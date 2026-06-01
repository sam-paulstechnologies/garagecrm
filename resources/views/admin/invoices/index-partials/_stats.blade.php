<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <a href="{{ route('admin.invoices.index') }}" class="sf-stat-card">
        <div class="sf-stat-label">Total Invoices</div>
        <div class="sf-stat-value">{{ $stats['total'] ?? 0 }}</div>
        <div class="sf-stat-note">All captured invoices</div>
    </a>

    <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" class="sf-stat-card">
        <div class="sf-stat-label">Paid</div>
        <div class="sf-stat-value text-green-300">{{ $stats['paid'] ?? 0 }}</div>
        <div class="sf-stat-note">Revenue-ready</div>
    </a>

    <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}" class="sf-stat-card">
        <div class="sf-stat-label">Pending</div>
        <div class="sf-stat-value text-yellow-300">{{ $stats['pending'] ?? 0 }}</div>
        <div class="sf-stat-note">Awaiting payment</div>
    </a>

    <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="sf-stat-card">
        <div class="sf-stat-label">Overdue</div>
        <div class="sf-stat-value text-red-300">{{ $stats['overdue'] ?? 0 }}</div>
        <div class="sf-stat-note">Needs attention</div>
    </a>

    <div class="sf-stat-card">
        <div class="sf-stat-label">ROI Revenue</div>
        <div class="mt-2 text-2xl font-extrabold tracking-tight text-orange-300">
            AED {{ number_format((float) ($stats['roi_revenue'] ?? 0), 2) }}
        </div>
        <div class="sf-stat-note">Paid invoice value</div>
    </div>
</div>
