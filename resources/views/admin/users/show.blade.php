@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">User Details</h1>

    <div class="card p-4 space-y-1">
        <p><strong>Name:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
        <p>
            <strong>Status:</strong>
            <span class="px-2 py-1 rounded text-xs {{ $user->status ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                {{ $user->status ? 'Active' : 'Inactive' }}
            </span>
        </p>
        <p><strong>Company:</strong> {{ optional($user->company)->name ?? '—' }}</p>
        <p><strong>Garage:</strong> {{ optional($user->garage)->name ?? '—' }}</p>
        <p><strong>Created At:</strong> {{ $user->created_at->format('d M Y') }}</p>
        <p><strong>Last Updated:</strong> {{ $user->updated_at->format('d M Y') }}</p>
    </div>

    <div class="mt-4 flex gap-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to List</a>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">Edit</a>
        <form action="{{ route('admin.users.toggleStatus', $user) }}" method="POST" class="inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn {{ $user->status ? 'btn-success' : 'btn-secondary' }}">
                {{ $user->status ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>
@endsection
