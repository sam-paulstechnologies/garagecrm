@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Marketing Reports', 'subtitle' => 'Prospect source, campaign, template, AI, demo, opt-out, and funnel performance using actual platform data.'])
    <div class="grid gap-5 xl:grid-cols-2">
        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Source Performance</h2>
            <div class="mt-4 space-y-3">
                @forelse($sourcePerformance as $row)
                    <div class="sa-soft flex justify-between rounded-2xl p-3 text-sm"><span>{{ $row->source_name }}</span><strong>{{ $row->total }}</strong></div>
                @empty
                    <p class="sa-muted text-sm">No source data yet.</p>
                @endforelse
            </div>
        </div>
        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Campaign Performance</h2>
            <div class="mt-4 space-y-3">
                @forelse($campaignPerformance as $campaign)
                    <div class="sa-soft flex justify-between rounded-2xl p-3 text-sm"><span>{{ $campaign->name }}</span><strong>{{ $campaign->recipients_count }} recipients</strong></div>
                @empty
                    <p class="sa-muted text-sm">No campaign data yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
