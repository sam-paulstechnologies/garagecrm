@extends('layouts.app')

@section('title', 'Add User')

@section('content')
<h1 class="text-2xl font-bold mb-6">Add New User</h1>

@if ($errors->any())
    <div class="mb-4 text-red-600">
        <ul>
            @foreach ($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
    @csrf

    <div>
        <label class="block font-medium text-sm text-gray-700">Name</label>
        <input type="text" name="name" class="form-input w-full" value="{{ old('name') }}" required>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Email</label>
        <input type="email" name="email" class="form-input w-full" value="{{ old('email') }}" required>
    </div>

    {{-- Role select via component --}}
    <x-user.role-select :value="old('role')" />

    <div>
        <label class="block font-medium text-sm text-gray-700">Password</label>
        <input type="password" name="password" class="form-input w-full" required minlength="8">
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-input w-full" required minlength="8">
    </div>

    {{-- Optional: Company/Garage if you keep those selectors on create --}}
    @isset($companies)
    <div>
        <label class="block font-medium text-sm text-gray-700">Company</label>
        <select name="company_id" class="form-select w-full">
            @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ old('company_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
    </div>
    @endisset

    @isset($garages)
    <div>
        <label class="block font-medium text-sm text-gray-700">Garage</label>
        <select name="garage_id" class="form-select w-full">
            <option value="">— None —</option>
            @foreach($garages as $g)
                <option value="{{ $g->id }}" {{ old('garage_id') == $g->id ? 'selected' : '' }}>
                    {{ $g->name }}
                </option>
            @endforeach
        </select>
    </div>
    @endisset

    <div>
        <label class="block font-medium text-sm text-gray-700">Status</label>
        <select name="status" class="form-select w-full" required>
            <option value="1" {{ old('status','1') == '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">Create User</button>
    </div>
</form>
@endsection
