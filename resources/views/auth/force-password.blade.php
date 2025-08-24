{{-- resources/views/auth/force-password.blade.php --}}
@extends('layouts.app')

@section('title', 'Set a new password')

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-2">Set a new password</h2>
    <p class="text-sm text-gray-600 mb-4">
        Your password was reset by an administrator. Please create a new one to continue.
    </p>

    @if ($errors->any())
        <div class="mb-3 p-3 rounded bg-red-100 text-red-800">
            <ul class="list-disc ml-4">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-3 p-3 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.force.update') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium">New password</label>
            <input type="password" name="password" class="form-input w-full" required minlength="8" autocomplete="new-password">
        </div>

        <div>
            <label class="block text-sm font-medium">Confirm password</label>
            <input type="password" name="password_confirmation" class="form-input w-full" required minlength="8" autocomplete="new-password">
        </div>

        <button class="btn btn-primary w-full">Save & Continue</button>
    </form>
</div>
@endsection
