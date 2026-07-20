@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => $segment->name, 'subtitle' => $segment->description ?: 'Static platform marketing segment.'])
    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm">
            <thead><tr><th class="p-4">Prospect</th><th>Phone</th><th>Status</th><th>Consent</th></tr></thead>
            <tbody>
                @forelse($segment->prospects as $prospect)
                    <tr><td class="p-4">{{ $prospect->business_name ?? $prospect->contact_name }}</td><td>{{ $prospect->whatsapp_number }}</td><td>{{ str($prospect->status)->headline() }}</td><td>{{ str($prospect->consent_status)->headline() }}</td></tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center sa-muted">No prospects in this segment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
