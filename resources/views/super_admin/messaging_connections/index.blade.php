@extends('super_admin.layout')

@section('title', 'Messaging Connections | SayaraForce')

@section('super_admin_content')
<div class="sa-card mb-6 rounded-3xl p-6">
    <p class="text-xs font-semibold uppercase tracking-wide text-orange-300">Platform diagnostics</p>
    <h1 class="sf-display mt-2 text-3xl" style="color:var(--sf-text-strong)">Messaging Connections</h1>
    <p class="sa-muted mt-2 text-sm font-medium">Tenant-scoped WhatsApp provisioning, health and recovery. Credentials are never displayed.</p>

    <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
        <select name="company_id" class="sa-input rounded-2xl px-4 py-3 text-sm" aria-label="Garage">
            <option value="">All garages</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected((string)($filters['company_id'] ?? '') === (string)$company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
        <select name="status" class="sa-input rounded-2xl px-4 py-3 text-sm" aria-label="Status">
            <option value="">All statuses</option>
            @foreach(['pending','authorizing','discovering_assets','subscribing','verifying','connected','requires_action','failed','disconnected'] as $value)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ str($value)->replace('_',' ')->title() }}</option>
            @endforeach
        </select>
        <input name="product_key" value="{{ $filters['product_key'] ?? '' }}" class="sa-input rounded-2xl px-4 py-3 text-sm" placeholder="Product key">
        <button class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white hover:bg-orange-600">Filter</button>
    </form>
</div>

<div class="sa-card overflow-hidden rounded-3xl">
    <div class="overflow-x-auto">
        <table class="sa-table min-w-full">
            <thead><tr>
                <th class="px-5 py-4 text-left">Garage / product</th>
                <th class="px-5 py-4 text-left">Mode</th>
                <th class="px-5 py-4 text-left">Status</th>
                <th class="px-5 py-4 text-left">Last verified</th>
                <th class="px-5 py-4 text-right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($connections as $connection)
                <tr>
                    <td class="px-5 py-4 text-sm"><strong style="color:var(--sf-text-strong)">{{ $connection->company?->name ?: 'Deleted garage' }}</strong><span class="sa-muted mt-1 block text-xs">{{ $connection->product_key }}</span></td>
                    <td class="px-5 py-4 text-sm">{{ str($connection->connection_mode)->replace('_',' ')->title() }}</td>
                    <td class="px-5 py-4 text-sm"><span class="{{ $connection->status->value === 'connected' ? 'sf-badge-green' : ($connection->status->value === 'requires_action' ? 'sf-badge-red' : 'sf-badge-yellow') }}">{{ str($connection->status->value)->replace('_',' ')->title() }}</span></td>
                    <td class="sa-muted px-5 py-4 text-sm">{{ $connection->last_verified_at?->toDayDateTimeString() ?: 'Not checked' }}</td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('super-admin.messaging-connections.show', $connection) }}" class="inline-flex min-h-10 items-center rounded-xl border border-orange-400/30 px-4 py-2 text-sm font-semibold text-orange-300 hover:bg-orange-500/10">Diagnostics</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="sa-muted px-5 py-10 text-center text-sm">No messaging connections found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t px-5 py-4" style="border-color:var(--sf-border)">{{ $connections->links() }}</div>
</div>
@endsection
