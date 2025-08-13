@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow">
    <h2 class="text-2xl font-semibold mb-6">
        {{ isset($lead) ? 'Edit Lead' : 'Create Lead' }}
    </h2>

    <form 
        id="lead-form"
        action="{{ isset($lead) ? route('admin.leads.update', $lead->id) : route('admin.leads.store') }}" 
        method="POST" 
        class="space-y-6"
    >
        @csrf
        @if(isset($lead))
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Name *</label>
                <input type="text" name="name" 
                    value="{{ old('name', $lead->name ?? '') }}" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" 
                    value="{{ old('email', $lead->email ?? '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Phone -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" name="phone" 
                    value="{{ old('phone', $lead->phone ?? '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Status *</label>
                <select name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach(['new','attempting_contact','contact_on_hold','qualified','disqualified','converted'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $lead->status ?? '') === $status)>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Source -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Source</label>
                <input type="text" name="source" 
                    value="{{ old('source', $lead->source ?? '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Preferred Channel -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Preferred Channel</label>
                <select name="preferred_channel" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach(['email', 'phone', 'whatsapp'] as $channel)
                        <option value="{{ $channel }}" @selected(old('preferred_channel', $lead->preferred_channel ?? 'phone') === $channel)>
                            {{ ucfirst($channel) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Assigned To -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Assigned To (User ID)</label>
                <input type="number" name="assigned_to" 
                    value="{{ old('assigned_to', $lead->assigned_to ?? '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Client (optional relationship) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Linked Client</label>
                <select name="client_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- None --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $lead->client_id ?? '') == $client->id)>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Is Hot -->
            <div class="flex items-center mt-6">
                <input type="checkbox" name="is_hot" id="is_hot" value="1"
                    @checked(old('is_hot', $lead->is_hot ?? false))
                    class="mr-2 border-gray-300 rounded shadow-sm">
                <label for="is_hot" class="text-sm text-gray-700">Mark as Hot Lead</label>
            </div>

            <!-- Last Contacted -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Last Contacted At</label>
                <input type="date" name="last_contacted_at" 
                    value="{{ old('last_contacted_at', isset($lead) && $lead->last_contacted_at ? $lead->last_contacted_at->format('Y-m-d') : '') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes', $lead->notes ?? '') }}</textarea>
        </div>

        <!-- Lead Score Reason -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Lead Score Reason</label>
            <textarea name="lead_score_reason" rows="2"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('lead_score_reason', $lead->lead_score_reason ?? '') }}</textarea>
        </div>

        <!-- Submit -->
        <div class="pt-4">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                {{ isset($lead) ? 'Update Lead' : 'Create Lead' }}
            </button>
        </div>
    </form>
</div>
@endsection
