@extends('layouts.app')

@section('title', 'Company Settings')

@section('content')
<h1 class="text-2xl font-bold mb-6">Company Profile</h1>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Current Plan Block -->
@include('admin.company.partials.current-plan')

<form method="POST" action="{{ route('admin.company.update') }}" enctype="multipart/form-data" class="space-y-6 bg-white p-6 rounded shadow">
    @csrf
    @method('PUT')

    <div>
        <label class="block font-medium text-sm text-gray-700">Company Name</label>
        <input type="text" name="name" class="form-input w-full" value="{{ old('name', $company->name) }}" required>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Email</label>
        <input type="email" name="email" class="form-input w-full" value="{{ old('email', $company->email) }}">
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Phone</label>
        <input type="text" name="phone" class="form-input w-full" value="{{ old('phone', $company->phone) }}">
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Address</label>
        <textarea name="address" class="form-input w-full" rows="3">{{ old('address', $company->address) }}</textarea>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700">Logo</label>
        @if($company->logo)
            <div class="mb-2">
                <img src="{{ asset('storage/' . $company->logo) }}" alt="Company Logo" class="h-16">
            </div>
        @endif
        <input type="file" name="logo" accept="image/*" class="form-input w-full">
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Save Changes
        </button>
    </div>
</form>
@endsection
