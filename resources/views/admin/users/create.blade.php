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

    <div>
        <label class="block font-medium text-sm text-gray-700">Role</label>
        @php
            $roles = ['admin' => 'Admin', 'mechanic' => 'Mechanic', 'manager' => 'Manager', 'receptionist' => 'Receptionist', 'supervisor' => 'Supervisor'];
        @endphp
        <select name="role" class="form-select w-full" required>
            @foreach($roles as $key => $label)
                <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Password</label>
        <input type="password" name="password" class="form-input w-full" required>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-input w-full" required>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">Create User</button>
    </div>
</form>
@endsection
