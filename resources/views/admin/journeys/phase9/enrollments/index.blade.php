@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Journey Enrollments</h3>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.journeys.dashboard') }}">Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journey</th>
                            <th>Entity</th>
                            <th>Step</th>
                            <th>Status</th>
                            <th>Health</th>
                            <th>Updated</th>
                            <th width="160">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $e)
                            @php
                                $h = $e->_health ?? ['badge'=>'on_track','label'=>'On Track'];
                                $badgeClass = match($h['badge']) {
                                    'stuck' => 'bg-danger',
                                    'waiting' => 'bg-warning text-dark',
                                    'paused' => 'bg-secondary',
                                    'completed' => 'bg-success',
                                    default => 'bg-primary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $e->journey->name ?? ('Journey#'.$e->journey_id) }}</div>
                                    <div class="text-muted small">Trigger: {{ $e->journey->trigger_key ?? '-' }}</div>
                                </td>
                                <td>{{ class_basename($e->enrollable_type) }} #{{ $e->enrollable_id }}</td>
                                <td>{{ $e->current_step_position }}</td>
                                <td>{{ $e->status ?? 'active' }}</td>
                                <td><span class="badge {{ $badgeClass }}">{{ $h['label'] }}</span></td>
                                <td class="small text-muted">{{ $e->updated_at ? $e->updated_at->format('Y-m-d H:i') : '—' }}</td>
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.journeys.enrollments.show', $e) }}">Manage</a>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.journeys.enrollments.timeline', $e) }}">Timeline</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $enrollments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
