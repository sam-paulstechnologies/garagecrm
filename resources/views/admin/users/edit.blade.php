@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<h1 class="text-2xl font-bold mb-6">Edit User</h1>

@if ($errors->any())
    <div class="mb-4 text-red-600">
        <ul>
            @foreach ($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div>
        <label class="block font-medium text-sm text-gray-700">Name</label>
        <input type="text" name="name" class="form-input w-full" value="{{ old('name', $user->name) }}" required>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Email</label>
        <input type="email" name="email" class="form-input w-full" value="{{ old('email', $user->email) }}" required>
    </div>

    {{-- Role select via component --}}
    <x-user.role-select :value="old('role', $user->role)" />

    <div>
        <label class="block font-medium text-sm text-gray-700">Status</label>
        <select name="status" class="form-select w-full" required>
            <option value="1" {{ old('status', (string)$user->status) === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('status', (string)$user->status) === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Inactive users cannot log in.</p>
    </div>

    <details class="bg-gray-50 p-3 rounded">
        <summary class="cursor-pointer text-sm font-medium">Change Password (optional)</summary>
        <div class="mt-3 space-y-3">
            <div>
                <label class="block text-sm">New password</label>
                <input type="password" name="password" class="form-input w-full" minlength="8" autocomplete="new-password">
            </div>
            <div>
                <label class="block text-sm">Confirm new password</label>
                <input type="password" name="password_confirmation" class="form-input w-full" minlength="8" autocomplete="new-password">
            </div>
        </div>
    </details>

    {{-- Optional: show company/garage but disable company change for safety --}}
    @isset($companies)
    <div>
        <label class="block font-medium text-sm text-gray-700">Company</label>
        <select name="company_id" class="form-select w-full" disabled>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ (old('company_id', $user->company_id) == $c->id) ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="company_id" value="{{ $user->company_id }}">
    </div>
    @endisset

    @isset($garages)
    <div>
        <label class="block font-medium text-sm text-gray-700">Garage</label>
        <select name="garage_id" class="form-select w-full">
            <option value="">— None —</option>
            @foreach($garages as $g)
                <option value="{{ $g->id }}" {{ old('garage_id', $user->garage_id) == $g->id ? 'selected' : '' }}>
                    {{ $g->name }}
                </option>
            @endforeach
        </select>
    </div>
    @endisset

    <div class="flex justify-between">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
        <button type="submit" class="btn btn-primary">Update User</button>
    </div>
</form>
@endsection
