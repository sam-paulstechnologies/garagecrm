@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
        Edit Profile
    </h2>

    @if (session('status') === 'profile-updated')
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded">
            Profile updated successfully.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $user->name) }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                required
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email', $user->email) }}"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                required
            >
        </div>

        <div class="pt-4">
            <button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow"
            >
                Save Changes
            </button>
        </div>
    </form>

    <hr class="my-10">

    <form method="POST" action="{{ route('admin.profile.destroy') }}">
        @csrf
        @method('DELETE')

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Confirm Password to Delete Account
            </label>
            <input
                type="password"
                name="password"
                class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                required
            >
        </div>

        <div class="pt-4">
            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded shadow"
                onclick="return confirm('Are you sure? This cannot be undone.')"
            >
                Delete Account
            </button>
        </div>
    </form>

</div>
@endsection
