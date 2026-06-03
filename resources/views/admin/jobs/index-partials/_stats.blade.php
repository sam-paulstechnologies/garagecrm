{{-- resources/views/admin/jobs/index-partials/_stats.blade.php --}}

@php
    $baseQuery = collect(request()->only([
        'q',
        'date_range',
        'lead_source',
        'assigned_user',
        'service_type',
        'customer_type',
        'from_date',
        'to_date',
    ]))
        ->filter(fn ($value) => filled($value) && $value !== 'all')
        ->all();

    $status = $status ?? request('status', '');
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <a
        href="{{ route('admin.jobs.index', $baseQuery) }}"
        class="sf-stat-card {{ $status === '' ? 'ring-1 ring-orange-400/30 border-orange-400/40' : '' }}"
    >
        <div class="sf-stat-label">Open Jobs</div>
        <div class="sf-stat-value">{{ $stats['open_jobs'] ?? 0 }}</div>
        <div class="sf-stat-note">Cars currently in service</div>
    </a>

    <a
        href="{{ route('admin.jobs.index', array_merge($baseQuery, ['status' => 'pending'])) }}"
        class="sf-stat-card {{ $status === 'pending' ? 'ring-1 ring-orange-400/30 border-orange-400/40' : '' }}"
    >
        <div class="sf-stat-label">Pending</div>
        <div class="sf-stat-value text-yellow-300">{{ $stats['pending'] ?? 0 }}</div>
        <div class="sf-stat-note">Waiting to start</div>
    </a>

    <a
        href="{{ route('admin.jobs.index', array_merge($baseQuery, ['status' => 'in_progress'])) }}"
        class="sf-stat-card {{ $status === 'in_progress' ? 'ring-1 ring-orange-400/30 border-orange-400/40' : '' }}"
    >
        <div class="sf-stat-label">In Progress</div>
        <div class="sf-stat-value text-blue-300">{{ $stats['in_progress'] ?? 0 }}</div>
        <div class="sf-stat-note">Work active now</div>
    </a>
</div>