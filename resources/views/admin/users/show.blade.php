@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">User Details</h1>

    <div class="card p-4">
        <p><strong>Name:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
        <p><strong>Created At:</strong> {{ $user->created_at->format('d M Y') }}</p>
        <p><strong>Last Updated:</strong> {{ $user->updated_at->format('d M Y') }}</p>
    </div>

    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary mt-4">Back to List</a>
</div>
@endsection
