@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => $campaign->name,
        'subtitle' => str($campaign->status)->headline().' · '.($campaign->template_name ?: 'No template selected')
    ])

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="sa-card rounded-3xl p-5 xl:col-span-2">
            <h2 class="text-lg font-black">Launch Controls</h2>
            <p class="sa-muted mt-2 text-sm">Prepare recipients first. Approval is required before dispatch. Sends are queued to `platform-marketing` and respect campaign idempotency keys.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('super-admin.marketing.campaigns.prepare', $campaign) }}">@csrf<button class="rounded-2xl bg-white/10 px-4 py-2 text-xs font-black text-white">Prepare Recipients</button></form>
                <form method="POST" action="{{ route('super-admin.marketing.campaigns.approve', $campaign) }}">@csrf<button class="rounded-2xl bg-emerald-500 px-4 py-2 text-xs font-black text-white">Approve</button></form>
                <form method="POST" action="{{ route('super-admin.marketing.campaigns.launch', $campaign) }}">@csrf<button class="rounded-2xl bg-orange-500 px-4 py-2 text-xs font-black text-white">Queue Launch</button></form>
                <form method="POST" action="{{ route('super-admin.marketing.campaigns.pause', $campaign) }}">@csrf<button class="rounded-2xl bg-white/10 px-4 py-2 text-xs font-black text-white">Pause</button></form>
                <form method="POST" action="{{ route('super-admin.marketing.campaigns.stop', $campaign) }}">@csrf<button class="rounded-2xl bg-red-500 px-4 py-2 text-xs font-black text-white">Stop</button></form>
            </div>
        </div>
        <div class="sa-card rounded-3xl p-5">
            <h2 class="text-lg font-black">Safety Snapshot</h2>
            <dl class="mt-4 space-y-3 text-sm">
                @foreach($campaign->safety_snapshot ?? [] as $key => $value)
                    <div class="flex justify-between"><dt class="sa-label">{{ str($key)->headline() }}</dt><dd class="font-bold">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </div>
    </div>

    <div class="sa-card mt-5 overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm">
            <thead><tr><th class="p-4">Recipient</th><th>Phone</th><th>Status</th><th>Last error</th></tr></thead>
            <tbody>
                @forelse($campaign->recipients as $recipient)
                    <tr><td class="p-4">{{ $recipient->prospect?->business_name ?? $recipient->prospect?->contact_name }}</td><td>{{ $recipient->normalized_phone }}</td><td>{{ str($recipient->status)->headline() }}</td><td>{{ $recipient->last_error }}</td></tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center sa-muted">No recipients prepared yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
