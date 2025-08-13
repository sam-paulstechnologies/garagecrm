@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-2xl font-semibold mb-4">Edit Profile</h2>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success mb-4">
            Profile updated successfully.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <label class="block font-medium text-sm text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-control w-full" />
        </div>

        <div class="mb-3">
            <label class="block font-medium text-sm text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control w-full" />
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <hr class="my-6">

    <form method="POST" action="{{ route('admin.profile.destroy') }}">
        @csrf
        @method('DELETE')

        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account? This action is irreversible.')">
            Delete Account
        </button>
    </form>
</div>
@endsection
