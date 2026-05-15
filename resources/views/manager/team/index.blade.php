@extends('layouts.manager')

@section('title', 'Manager Team')

@section('content')
@php
    $memberPhone = function ($member) {
        return $member->phone
            ?? $member->mobile
            ?? $member->phone_number
            ?? '-';
    };

    $memberRole = function ($member) {
        return $member->role
            ?? $member->role_name
            ?? $member->designation
            ?? 'Team Member';
    };

    $memberStatus = function ($member) {
        if (isset($member->is_active)) {
            return (int) $member->is_active === 1 ? 'Active' : 'Inactive';
        }

        return $member->status ?? 'Active';
    };
@endphp

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Team</h1>
            <p class="text-muted mb-0">
                View managers, technicians, and employees assigned to your garage.
            </p>
        </div>

        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-secondary">
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('manager.team.index') }}" class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label">Search Team</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        class="form-control"
                        placeholder="Search name, email, phone, role, department"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>

                        @foreach(($roles ?? collect()) as $item)
                            <option value="{{ $item }}" @selected(($role ?? request('role')) === $item)>
                                {{ ucfirst($item) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Team Members</h5>
                <small class="text-muted">
                    Active users under this garage/company.
                </small>
            </div>

            <span class="badge bg-light text-dark">
                {{ method_exists($team, 'total') ? $team->total() : $team->count() }} member(s)
            </span>
        </div>

        <div class="card-body p-0">
            @if($team->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Member</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($team as $member)
                                @php
                                    $statusText = $memberStatus($member);
                                    $statusBadge = strtolower((string) $statusText) === 'active' ? 'success' : 'secondary';
                                @endphp

                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $member->name ?? 'User #' . $member->id }}
                                        </div>

                                        <small class="text-muted">
                                            #{{ $member->id }}
                                            @if(!empty($member->email))
                                                · {{ $member->email }}
                                            @endif
                                        </small>
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $memberRole($member) }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $memberPhone($member) }}
                                    </td>

                                    <td>
                                        {{ $member->department ?? '-' }}
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $statusBadge }}">
                                            {{ ucfirst((string) $statusText) }}
                                        </span>
                                    </td>

                                    <td>
                                        @if(!empty($member->created_at))
                                            {{ \Carbon\Carbon::parse($member->created_at)->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center">
                    <h5 class="mb-2">No team members found</h5>
                    <p class="text-muted mb-0">
                        Active team members will appear here.
                    </p>
                </div>
            @endif
        </div>

        @if(method_exists($team, 'links'))
            <div class="card-footer bg-white">
                {{ $team->links() }}
            </div>
        @endif
    </div>
</div>
@endsection