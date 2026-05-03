// resources/views/admin/audiences/index.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Audiences</h3>
            <div class="text-muted small">Client-level audiences used for campaigns and journeys</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.audiences.unassigned') }}" class="btn btn-outline-secondary btn-sm">Unassigned</a>
            <form method="POST" action="{{ route('admin.audiences.rebuild') }}">
                @csrf
                <button class="btn btn-outline-primary btn-sm" type="submit">Rebuild</button>
            </form>
            <a href="{{ route('admin.audiences.create') }}" class="btn btn-primary btn-sm">+ New Audience</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="p-3">Name</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Active</th>
                        <th class="p-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audiences as $a)
                        <tr>
                            <td class="p-3">
                                <div class="fw-semibold">{{ $a->name }}</div>
                                <div class="text-muted small">{{ $a->description }}</div>
                            </td>
                            <td class="p-3">
                                <span class="badge bg-secondary">{{ $a->entity_type }}</span>
                                @if($a->is_system)
                                    <span class="badge bg-dark">System</span>
                                @endif
                            </td>
                            <td class="p-3">
                                {!! $a->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}
                            </td>
                            <td class="p-3 text-end">
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.audiences.show', $a->id) }}">View</a>
                                @if(!$a->is_system)
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.audiences.edit', $a->id) }}">Edit</a>
                                    <form class="d-inline" method="POST" action="{{ route('admin.audiences.destroy', $a->id) }}" onsubmit="return confirm('Delete this audience?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-3 text-muted" colspan="4">No audiences yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $audiences->links() }}
    </div>
</div>
@endsection
