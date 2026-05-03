@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Journey Dashboard</h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.journeys.enrollments.index') }}">Enrollments</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journey</th>
                            <th>Steps</th>
                            <th>Active</th>
                            <th>Paused</th>
                            <th>Completed</th>
                            <th>Waiting</th>
                            <th>Stuck</th>
                            <th>Last Activity</th>
                            <th>Avg mins since update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            @php $j = $r['journey']; @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $j->name }}</div>
                                    <div class="text-muted small">Trigger: {{ $j->trigger_key ?? '-' }}</div>
                                </td>
                                <td>{{ $j->steps_count }}</td>
                                <td>{{ $r['active'] }}</td>
                                <td>{{ $r['paused'] }}</td>
                                <td>{{ $r['completed'] }}</td>
                                <td>{{ $r['waiting'] }}</td>
                                <td class="{{ $r['stuck'] ? 'text-danger fw-semibold' : '' }}">{{ $r['stuck'] }}</td>
                                <td class="small text-muted">{{ $r['last_activity'] ? $r['last_activity']->format('Y-m-d H:i') : '—' }}</td>
                                <td class="small text-muted">{{ $r['avg_minutes_since_update'] !== null ? $r['avg_minutes_since_update'] : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
