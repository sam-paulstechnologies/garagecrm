@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => 'Demo Appointments',
        'subtitle' => 'Book and review SayaraForce demo appointments for platform prospects only.'
    ])
    <form method="POST" action="{{ route('super-admin.marketing.appointments.store') }}" class="sa-card mb-5 grid gap-3 rounded-3xl p-5 lg:grid-cols-5">
        @csrf
        <select name="prospect_id" class="sa-input rounded-2xl px-4 py-3 text-sm" required><option value="">Prospect</option>@foreach($prospects as $prospect)<option value="{{ $prospect->id }}">{{ $prospect->business_name ?? $prospect->contact_name }}</option>@endforeach</select>
        <input name="starts_at" type="datetime-local" class="sa-input rounded-2xl px-4 py-3 text-sm" required>
        <input name="duration_minutes" type="number" value="30" class="sa-input rounded-2xl px-4 py-3 text-sm" required>
        <input name="timezone" value="Asia/Dubai" class="sa-input rounded-2xl px-4 py-3 text-sm" required>
        <button class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white">Book Demo</button>
        <input name="meeting_mode" value="online" class="sa-input rounded-2xl px-4 py-3 text-sm">
        <input name="meeting_link" placeholder="Meeting link" class="sa-input rounded-2xl px-4 py-3 text-sm lg:col-span-2">
        <input name="internal_notes" placeholder="Internal notes" class="sa-input rounded-2xl px-4 py-3 text-sm lg:col-span-2">
    </form>
    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm">
            <thead><tr><th class="p-4">When</th><th>Status</th><th>Mode</th><th>Link</th></tr></thead>
            <tbody>
                @forelse($appointments as $appointment)
                    <tr><td class="p-4">{{ $appointment->starts_at?->format('d M Y H:i') }}</td><td>{{ str($appointment->status)->headline() }}</td><td>{{ str($appointment->meeting_mode)->headline() }}</td><td>{{ $appointment->meeting_link }}</td></tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center sa-muted">No demo appointments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
