@extends('super_admin.layout')

@section('title', 'Super Admin Dashboard')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Platform Overview</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-white lg:text-5xl">SayaraForce Control Center</h1>
                <p class="mt-3 max-w-3xl text-sm font-semibold sa-muted">
                    Monitor garages, tenants, users, WhatsApp intake, operational volume, and platform health from one secure platform-owner area.
                </p>
            </div>

            <form method="GET" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <select name="company_id" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                    <option value="">All garages</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-black text-white shadow-lg shadow-orange-950/20">Apply</button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Total garages', $stats['total_garages'], 'All onboarded tenants'],
            ['Active garages', $stats['active_garages'], 'Currently active'],
            ['Trial / pilot', $stats['trial_garages'], 'Launch or pilot garages'],
            ['Suspended / inactive', $stats['suspended_garages'], 'Need review'],
            ['Total users', $stats['total_users'], 'Across all garages'],
            ['Leads this month', $stats['leads_month'], 'Captured in selected range'],
            ['Messages this month', $stats['messages_month'], 'Inbound and outbound logs'],
            ['Open opportunities', $stats['open_opportunities'], 'Pipeline not closed'],
            ['Bookings this month', $stats['bookings_month'], 'Created in range'],
            ['Jobs this month', $stats['jobs_month'], 'Created in range'],
            ['Invoices this month', $stats['invoices_month'], 'Created in range'],
            ['Failed jobs', $stats['failed_jobs'], 'Queue failures'],
        ] as [$label, $value, $hint])
            <div class="rounded-3xl sa-card p-5">
                <p class="text-sm font-extrabold sa-label">{{ $label }}</p>
                <p class="mt-4 text-4xl font-black text-white">{{ $value }}</p>
                <p class="mt-2 text-sm font-semibold sa-muted">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <section class="rounded-3xl sa-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-white">Garages Needing Attention</h2>
                    <p class="mt-1 text-sm font-semibold sa-muted">Operational risks based on available records.</p>
                </div>
                @include('super_admin.partials._badge', ['tone' => $stats['whatsapp_issues'] ? 'orange' : 'green', 'label' => $stats['whatsapp_issues'].' channel issue(s)'])
            </div>

            <div class="mt-5 space-y-3">
                @forelse($garagesNeedingAttention as $item)
                    <a href="{{ route('super-admin.garages.show', $item->company) }}" class="block rounded-2xl sa-soft p-4 transition hover:border-orange-400/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-black text-white">{{ $item->company->name }}</p>
                                <p class="mt-1 text-xs font-bold sa-muted">Last lead: {{ $item->last_lead?->created_at ? \Carbon\Carbon::parse($item->last_lead->created_at)->format('d M Y, h:i A') : 'No lead found' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($item->warnings as $warning)
                                    @include('super_admin.partials._badge', ['tone' => 'orange', 'label' => $warning])
                                @endforeach
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl sa-soft p-5 text-sm font-bold sa-muted">No garage attention items found for the selected range.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Recent Super Admin Actions</h2>
            <p class="mt-1 text-sm font-semibold sa-muted">Every platform-owner write action should appear here.</p>

            <div class="mt-5 space-y-3">
                @forelse($recentActions as $log)
                    <div class="rounded-2xl sa-soft p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-white">{{ str($log->action)->headline() }}</p>
                                <p class="mt-1 text-xs font-bold sa-muted">{{ $log->company?->name ?? 'Platform' }} by {{ $log->superAdmin?->name ?? 'Unknown' }}</p>
                            </div>
                            <span class="text-xs font-bold sa-label">{{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl sa-soft p-5 text-sm font-bold sa-muted">No Super Admin actions recorded yet.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
