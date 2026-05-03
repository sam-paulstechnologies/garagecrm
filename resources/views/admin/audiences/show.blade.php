// resources/views/admin/audiences/show.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h3 class="mb-1">{{ $audience->name }}</h3>
            <div class="text-muted small">{{ $audience->description }}</div>
            <div class="mt-2">
                {!! $audience->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}
                @if($audience->is_system)
                    <span class="badge bg-dark">System</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @if(!$audience->is_system)
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.audiences.edit', $audience->id) }}">Edit</a>
            @endif
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.audiences.index') }}">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="fw-semibold mb-2">Latest Rules</div>
            <pre class="mb-0 small">{{ json_encode($rules?->rules_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="p-3">Client</th>
                        <th class="p-3">Phone</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Added By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $m)
                        <tr>
                            <td class="p-3">{{ $m->client?->name }} <span class="text-muted">#{{ $m->client_id }}</span></td>
                            <td class="p-3">{{ $m->client?->phone ?? $m->client?->whatsapp }}</td>
                            <td class="p-3">{{ $m->client?->email }}</td>
                            <td class="p-3">{{ $m->added_by }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3 text-muted" colspan="4">No members yet. Click “Rebuild”.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $members->links() }}
    </div>
</div>
@endsection
