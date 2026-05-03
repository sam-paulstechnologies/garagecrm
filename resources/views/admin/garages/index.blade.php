{{-- resources/views/admin/garages/index.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Garages</h1>
        <a href="{{ route('admin.garages.create') }}" class="btn btn-primary">+ Add Garage</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 28%;">Name</th>
                        <th style="width: 14%;">Phone</th>
                        <th style="width: 22%;">Email</th>
                        <th>Address</th>
                        <th style="width: 10%;">Default</th>
                        <th style="width: 14%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($garages as $garage)
                        <tr>
                            <td>
                                <a href="{{ route('admin.garages.show', $garage->id) }}">
                                    {{ $garage->name }}
                                </a>
                            </td>
                            <td>{{ $garage->phone ?? '-' }}</td>
                            <td>{{ $garage->email ?? '-' }}</td>
                            <td>{{ $garage->address ?? '-' }}</td>
                            <td>
                                @if($garage->is_default)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.garages.edit', $garage->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.garages.destroy', $garage->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this garage?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No garages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($garages->hasPages())
            <div class="card-body">
                {{ $garages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
