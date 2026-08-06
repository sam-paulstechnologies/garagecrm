@extends('super_admin.layout')

@section('title', 'Messaging Diagnostics | SayaraForce')

@section('super_admin_content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <a href="{{ route('super-admin.messaging-connections.index') }}" class="text-sm font-semibold text-orange-300">← All messaging connections</a>
        <h1 class="sf-display mt-3 text-3xl" style="color:var(--sf-text-strong)">{{ $connection->company?->name ?: 'Deleted garage' }}</h1>
        <p class="sa-muted mt-2 text-sm">{{ $connection->product_key }} · {{ $connection->provider }}</p>
    </div>
    <form method="POST" action="{{ route('super-admin.messaging-connections.retry', $connection) }}" onsubmit="return confirm('Retry the saved provisioning state without reopening Embedded Signup?');">
        @csrf
        <button class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white hover:bg-orange-600">Retry provisioning</button>
    </form>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <section class="sa-card rounded-3xl p-6 xl:col-span-2">
        <h2 class="sf-section-title text-xl">Connection</h2>
        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach([
                'Product key' => $connection->product_key,
                'Status' => str($connection->status->value)->replace('_',' ')->title(),
                'Connection mode' => str($connection->connection_mode)->replace('_',' ')->title(),
                'WABA ID' => $connection->waba_id ?: 'Not available',
                'Phone Number ID' => $phone?->phone_number_id ?: 'Not available',
                'Display number' => $phone?->display_phone_number ?: 'Not available',
                'Registration' => $phone?->registration_status ?: 'Not checked',
                'Coexistence' => $phone?->coexistence_status ?: 'Not applicable',
                'Subscription' => optional($connection->checks->firstWhere('check_key','app_subscription'))->status ?: 'Not checked',
                'Token expiry' => $connection->token_expires_at?->toDayDateTimeString() ?: 'No expiry supplied',
                'Last webhook' => $connection->company?->whatsapp_last_webhook_at?->toDayDateTimeString() ?: 'Not received',
                'Last outbound result' => optional($connection->checks->firstWhere('check_key','last_outbound'))->summary ?: 'Not checked',
            ] as $label => $value)
                <div class="sa-soft rounded-2xl p-4"><dt class="sa-label text-xs font-semibold uppercase tracking-wide">{{ $label }}</dt><dd class="mt-1 break-words text-sm font-semibold" style="color:var(--sf-text-strong)">{{ $value }}</dd></div>
            @endforeach
        </dl>
        @if($connection->failure_code)
            <div class="mt-5 rounded-2xl border border-red-400/30 bg-red-500/10 p-4 text-sm text-red-200">
                <strong>{{ $connection->failure_code }}</strong><span class="mt-1 block">{{ $connection->failure_message }}</span>
            </div>
        @endif
    </section>

    <section class="sa-card rounded-3xl p-6">
        <h2 class="sf-section-title text-xl">Health checks</h2>
        <div class="mt-4 space-y-3">
            @forelse($connection->checks as $check)
                <div class="sa-soft rounded-2xl p-4">
                    <div class="flex items-center justify-between gap-2"><strong class="text-sm">{{ str($check->check_key)->replace('_',' ')->title() }}</strong><span class="text-xs font-semibold">{{ ucfirst($check->status) }}</span></div>
                    <p class="sa-muted mt-2 text-xs leading-5">{{ $check->summary }}</p>
                    @if($check->provider_error_code)<p class="mt-1 text-xs text-amber-300">Code: {{ $check->provider_error_code }}</p>@endif
                </div>
            @empty
                <p class="sa-muted text-sm">No checks recorded.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="sa-card mt-6 rounded-3xl p-6">
    <h2 class="sf-section-title text-xl">Provisioning and audit history</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="sa-table min-w-full"><thead><tr><th class="px-4 py-3 text-left">Time</th><th class="px-4 py-3 text-left">Operation</th><th class="px-4 py-3 text-left">Result</th><th class="px-4 py-3 text-left">Failure code</th></tr></thead>
            <tbody>@forelse($connection->audits as $audit)<tr><td class="px-4 py-3 text-sm">{{ $audit->occurred_at?->toDayDateTimeString() }}</td><td class="px-4 py-3 text-sm">{{ str($audit->operation)->replace('_',' ')->title() }}</td><td class="px-4 py-3 text-sm">{{ ucfirst($audit->result) }}</td><td class="sa-muted px-4 py-3 text-sm">{{ $audit->provider_error_code ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="sa-muted px-4 py-8 text-center text-sm">No audit history recorded.</td></tr>@endforelse</tbody>
        </table>
    </div>
</section>
@endsection
