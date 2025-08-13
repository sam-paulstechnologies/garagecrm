@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Company Profile</h1>
    <form action="{{ route('settings.company.update') }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-4">
            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $company->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('company_name')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $company->email) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('email')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $company->phone) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('phone')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
            <textarea name="address" id="address" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('address', $company->address) }}</textarea>
            @error('address')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <h2 class="text-xl font-bold mt-8 mb-4">Integrations</h2>

        <div class="mb-4">
            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
            <input type="text" name="whatsapp_number" id="whatsapp_number" value="{{ old('whatsapp_number', $company->whatsapp_number) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('whatsapp_number')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email_integration" class="block text-sm font-medium text-gray-700">Email Integration</label>
            <input type="text" name="email_integration" id="email_integration" value="{{ old('email_integration', $company->email_integration) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('email_integration')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Update Profile</button>
    </form>
</div>
@endsection