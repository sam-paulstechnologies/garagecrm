@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Platform WhatsApp Channel', 'subtitle' => 'Read-only operational visibility plus secure setup for the PaulsTechnologies WhatsApp Business number.'])
    <div class="grid gap-5 xl:grid-cols-2">
        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Current Channel</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="sa-label">Name</dt><dd>{{ $channel?->name ?? 'PaulsTechnologies LLC' }}</dd></div>
                <div class="flex justify-between"><dt class="sa-label">Display number</dt><dd>{{ $channel?->display_phone_number ?? '+971527427692' }}</dd></div>
                <div class="flex justify-between"><dt class="sa-label">Phone Number ID</dt><dd>{{ $channel?->masked_phone_number_id ?? '1070...0019' }}</dd></div>
                <div class="flex justify-between"><dt class="sa-label">Status</dt><dd>{{ str($channel?->connection_status ?? 'not_connected')->headline() }}</dd></div>
                <div class="flex justify-between"><dt class="sa-label">Active</dt><dd>{{ $channel?->is_active ? 'Yes' : 'No' }}</dd></div>
                <div class="flex justify-between"><dt class="sa-label">Last API error</dt><dd>{{ $channel?->last_api_error ?: 'None' }}</dd></div>
            </dl>
        </div>
        <form method="POST" action="{{ route('super-admin.marketing.channel.store') }}" class="sa-card rounded-3xl p-5">
            @csrf
            <h2 class="text-lg font-black">Setup / Update</h2>
            <div class="mt-4 grid gap-3">
                <input name="name" value="{{ old('name', $channel?->name ?? 'PaulsTechnologies LLC') }}" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Channel name">
                <input name="display_phone_number" value="{{ old('display_phone_number', $channel?->display_phone_number ?? '+971527427692') }}" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Display number">
                <input name="phone_number_id" value="{{ old('phone_number_id', $channel?->phone_number_id ?? '1070868312780019') }}" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Phone Number ID">
                <input name="waba_id" value="{{ old('waba_id', $channel?->waba_id) }}" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="WABA ID">
                <input name="meta_business_id" value="{{ old('meta_business_id', $channel?->meta_business_id) }}" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Meta Business ID">
                <input name="access_token" type="password" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Access token (stored encrypted)">
                <input name="verify_token" type="password" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Verify token">
                <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="is_active" value="1" @checked($channel?->is_active)> Active</label>
                <button class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white">Save Channel</button>
            </div>
        </form>
    </div>
@endsection
