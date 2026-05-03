@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-1">Manage Enrollment</h3>
            <div class="text-muted">
                <div><strong>Journey:</strong> {{ $journey->name ?? ('#'.$journey->id) }}</div>
                <div><strong>Trigger:</strong> {{ $journey->trigger_key ?? '-' }}</div>
                <div><strong>Status:</strong> {{ $enrollment->status ?? 'active' }}</div>
                <div><strong>Health:</strong> {{ $health['label'] ?? 'On Track' }}</div>
                <div><strong>Entity:</strong> {{ class_basename($enrollment->enrollable_type) }} #{{ $enrollment->enrollable_id }}</div>
                <div><strong>Step:</strong> {{ $enrollment->current_step_position }}</div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.journeys.enrollments.index') }}">Back</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.journeys.enrollments.timeline', $enrollment) }}">Timeline</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Manual Controls (Phase 9C)</strong></div>
                <div class="card-body">

                    <form class="mb-2" method="POST" action="{{ route('admin.journeys.enrollments.pause', $enrollment) }}">
                        @csrf
                        <div class="input-group">
                            <input class="form-control form-control-sm" name="reason" placeholder="Reason (optional)">
                            <button class="btn btn-sm btn-secondary" type="submit">Pause</button>
                        </div>
                    </form>

                    <form class="mb-2" method="POST" action="{{ route('admin.journeys.enrollments.resume', $enrollment) }}">
                        @csrf
                        <div class="input-group">
                            <input class="form-control form-control-sm" name="reason" placeholder="Reason (optional)">
                            <button class="btn btn-sm btn-primary" type="submit">Resume</button>
                        </div>
                    </form>

                    <form class="mb-2" method="POST" action="{{ route('admin.journeys.enrollments.skip', $enrollment) }}">
                        @csrf
                        <div class="input-group">
                            <input class="form-control form-control-sm" name="reason" placeholder="Reason (optional)">
                            <button class="btn btn-sm btn-warning" type="submit">Skip 1 Step</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.journeys.enrollments.forceAdvance', $enrollment) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-4">
                                <input class="form-control form-control-sm" name="position" type="number" min="0"
                                       value="{{ (int)$enrollment->current_step_position }}">
                            </div>
                            <div class="col-8">
                                <input class="form-control form-control-sm" name="reason" placeholder="Reason (optional)">
                            </div>
                        </div>
                        <button class="btn btn-sm btn-danger mt-2" type="submit">Force Advance</button>
                    </form>

                    <div class="text-muted small mt-3">
                        Every action is logged in <strong>Audit Trail</strong> and appears in the timeline.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Audit Trail (Phase 9D)</strong></div>
                <div class="card-body">
                    @if($actions->isEmpty())
                        <div class="text-muted">No actions recorded yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>At</th>
                                        <th>Action</th>
                                        <th>Actor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($actions as $a)
                                        <tr>
                                            <td class="small text-muted">{{ $a->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td class="fw-semibold">{{ strtoupper($a->action) }}</td>
                                            <td class="small text-muted">User#{{ $a->actor_user_id }}</td>
                                        </tr>
                                        @if(!empty($a->payload))
                                            <tr>
                                                <td colspan="3">
                                                    <pre class="mb-0 small">{{ json_encode($a->payload, JSON_PRETTY_PRINT) }}</pre>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
