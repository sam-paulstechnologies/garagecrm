@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => 'Controlled WhatsApp Campaigns',
        'subtitle' => 'Approved-template campaigns with consent, suppression, duplicate-recipient, idempotency, and daily cap guardrails.',
        'action' => new Illuminate\Support\HtmlString('<a href="'.route('super-admin.marketing.campaigns.create').'" class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white">New Campaign</a>')
    ])

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach($buckets as $status => $total)
            <span class="sa-soft rounded-2xl px-4 py-2 text-xs font-black">{{ str($status)->headline() }} · {{ $total }}</span>
        @endforeach
    </div>

    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm">
            <thead><tr><th class="p-4">Campaign</th><th>Status</th><th>Template</th><th>Recipients</th><th>Caps</th><th></th></tr></thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td class="p-4"><span class="font-bold">{{ $campaign->name }}</span><span class="sa-label block">{{ $campaign->objective }}</span></td>
                        <td>{{ str($campaign->status)->headline() }}</td>
                        <td>{{ $campaign->template_name ?: 'Not selected' }}</td>
                        <td>{{ $campaign->recipients_count }}</td>
                        <td>{{ $campaign->batch_size }}/batch · {{ $campaign->daily_cap }}/day</td>
                        <td><a class="font-bold text-emerald-300" href="{{ route('super-admin.marketing.campaigns.show', $campaign) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center sa-muted">No campaigns have been created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $campaigns->links() }}</div>
@endsection
