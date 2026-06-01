<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <a href="{{ route('admin.jobs.index') }}" class="sf-stat-card">
        <div class="sf-stat-label">Open Jobs</div>
        <div class="sf-stat-value">{{ $stats['open_jobs'] ?? 0 }}</div>
        <div class="sf-stat-note">Cars currently in service</div>
    </a>

    <a href="{{ route('admin.jobs.index', ['status' => 'pending']) }}" class="sf-stat-card">
        <div class="sf-stat-label">Pending</div>
        <div class="sf-stat-value text-yellow-300">{{ $stats['pending'] ?? 0 }}</div>
        <div class="sf-stat-note">Waiting to start</div>
    </a>

    <a href="{{ route('admin.jobs.index', ['status' => 'in_progress']) }}" class="sf-stat-card">
        <div class="sf-stat-label">In Progress</div>
        <div class="sf-stat-value text-blue-300">{{ $stats['in_progress'] ?? 0 }}</div>
        <div class="sf-stat-note">Work active now</div>
    </a>
</div>
