@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<h1 class="text-2xl font-bold mb-6">Users</h1>

@if (session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="mb-4 text-right">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Add User</a>
</div>

<table class="table table-bordered w-full text-sm">
    <thead class="bg-gray-100 text-left">
        <tr>
            <th class="px-4 py-2">Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $user)
            <tr class="border-t">
                <td class="px-4 py-2">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-800">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td>
                    <form action="{{ route('admin.users.toggleStatus', $user) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $user->status ? 'btn-success' : 'btn-secondary' }}">
                            {{ $user->status ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </td>
                <td class="flex gap-2 py-2 flex-wrap">
                    <!-- Edit -->
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning">Edit</a>

                    <!-- Reset Password -->
                    <form action="{{ route('admin.users.resetPassword', $user) }}" method="POST"
                          onsubmit="return confirm('Reset password for {{ $user->name }}?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-dark">Reset Password</button>
                    </form>

                    <!-- Delete -->
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this user?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-gray-500 py-4">No users found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
