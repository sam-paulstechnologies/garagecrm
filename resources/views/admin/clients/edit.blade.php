@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-6">Edit Client</h1>

    <form action="{{ route('admin.clients.update', $client->id) }}" method="POST" class="bg-white shadow rounded p-6 space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input name="name" type="text" value="{{ old('name', $client->name) }}" class="mt-1 w-full border rounded px-3 py-2">
                @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input name="email" type="email" value="{{ old('email', $client->email) }}" class="mt-1 w-full border rounded px-3 py-2">
                @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input name="phone" type="text" value="{{ old('phone', $client->phone) }}" class="mt-1 w-full border rounded px-3 py-2">
                @error('phone')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                <input name="whatsapp" type="text" value="{{ old('whatsapp', $client->whatsapp) }}" class="mt-1 w-full border rounded px-3 py-2">
                @error('whatsapp')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Identity --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Gender</label>
                <select name="gender" class="mt-1 w-full border rounded px-3 py-2">
                    <option value="">—</option>
                    @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('gender', $client->gender) == $val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                <input name="dob" type="date" value="{{ old('dob', optional($client->dob)->format('Y-m-d')) }}" class="mt-1 w-full border rounded px-3 py-2">
                @error('dob')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-2 mt-6 md:mt-8">
                <input id="is_vip" name="is_vip" type="checkbox" value="1" @checked(old('is_vip', $client->is_vip)) class="h-4 w-4">
                <label for="is_vip" class="text-sm text-gray-700">VIP</label>
            </div>
        </div>

        {{-- Address / Location --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Location</label>
                <input name="location" type="text" value="{{ old('location', $client->location) }}" class="mt-1 w-full border rounded px-3 py-2">
                @error('location')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Preferred Channel</label>
                <select name="preferred_channel" class="mt-1 w-full border rounded px-3 py-2">
                    <option value="">—</option>
                    @foreach(['Call','WhatsApp','Email','SMS'] as $opt)
                        <option value="{{ $opt }}" @selected(old('preferred_channel', $client->preferred_channel) == $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                @error('preferred_channel')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <input name="address" type="text" value="{{ old('address', $client->address) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">City</label>
                <input name="city" type="text" value="{{ old('city', $client->city) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">State</label>
                <input name="state" type="text" value="{{ old('state', $client->state) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                <input name="postal_code" type="text" value="{{ old('postal_code', $client->postal_code) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Country</label>
                <input name="country" type="text" value="{{ old('country', $client->country) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
        </div>

        {{-- CRM fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Source</label>
                <input name="source" type="text" value="{{ old('source', $client->source) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <input name="status" type="text" value="{{ old('status', $client->status) }}" class="mt-1 w-full border rounded px-3 py-2">
            </div>
        </div>

        {{-- Internal Notes (client row text column) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Internal Notes</label>
            <textarea name="notes" rows="3" class="mt-1 w-full border rounded px-3 py-2">{{ old('notes', $client->notes) }}</textarea>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded">
                Update Client
            </button>
            <a href="{{ route('admin.clients.index') }}" class="text-indigo-600 hover:underline">Cancel</a>
        </div>
    </form>
</div>
@endsection
