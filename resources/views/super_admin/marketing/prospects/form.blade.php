@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => $prospect->exists ? 'Edit Prospect' : 'Create Prospect',
        'subtitle' => 'Platform prospect records are stored separately from garage leads and customer tables.'
    ])

    <form method="POST" action="{{ $prospect->exists ? route('super-admin.marketing.prospects.update', $prospect) : route('super-admin.marketing.prospects.store') }}" class="sa-card grid gap-4 rounded-3xl p-6 lg:grid-cols-2">
        @csrf
        @if($prospect->exists) @method('PUT') @endif
        @foreach([
            'business_name' => 'Business name',
            'contact_name' => 'Contact name',
            'whatsapp_number' => 'WhatsApp number',
            'email' => 'Email',
            'website' => 'Website',
            'country' => 'Country',
            'city' => 'Emirate / city',
            'business_type' => 'Business type',
            'branches_count' => 'Number of branches',
            'employees_count' => 'Number of employees',
            'source' => 'Source',
            'source_detail' => 'Source detail',
            'interested_product' => 'Interested product',
            'current_software' => 'Current software/tools',
            'lead_score' => 'Lead score',
            'consent_source' => 'Consent source',
        ] as $field => $label)
            <label class="text-sm font-bold">{{ $label }}
                <input name="{{ $field }}" value="{{ old($field, $prospect->{$field}) }}" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm">
            </label>
        @endforeach
        <label class="text-sm font-bold">Status
            <select name="status" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $prospect->status) === $status)>{{ str($status)->headline() }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-bold">Consent status
            <select name="consent_status" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm">
                @foreach(['unknown', 'opted_in', 'opted_out', 'not_required'] as $status)
                    <option value="{{ $status }}" @selected(old('consent_status', $prospect->consent_status) === $status)>{{ str($status)->headline() }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-bold lg:col-span-2">Pain points
            <textarea name="pain_points" rows="3" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm">{{ old('pain_points', $prospect->pain_points) }}</textarea>
        </label>
        <label class="text-sm font-bold lg:col-span-2">Notes
            <textarea name="notes" rows="4" class="sa-input mt-2 w-full rounded-2xl px-4 py-3 text-sm">{{ old('notes', $prospect->notes) }}</textarea>
        </label>
        <div class="lg:col-span-2">
            <button class="rounded-2xl bg-emerald-500 px-6 py-3 text-sm font-black text-white">Save Prospect</button>
        </div>
    </form>
@endsection
