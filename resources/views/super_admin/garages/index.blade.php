@extends('super_admin.layout')

@section('title', 'Super Admin Garages')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Garage / Tenant Management</p>
                <h1 class="mt-2 text-3xl font-black text-white">All Garages</h1>
                <p class="mt-2 text-sm font-semibold sa-muted">Search tenants, review usage, and open health, users, modules, and channels.</p>
            </div>
            <form method="GET" class="grid gap-2 md:grid-cols-3 xl:grid-cols-6">
                <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, phone, email..." class="sa-input rounded-2xl px-3 py-2 text-sm font-bold md:col-span-2">
                <select name="status" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                    <option value="">All statuses</option>
                    @foreach(['active' => 'Active', 'trial' => 'Trial', 'pilot' => 'Pilot', 'suspended' => 'Suspended', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="plan_id" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                    <option value="">All plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(($filters['plan_id'] ?? '') == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <button class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-black text-white">Filter</button>
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl sa-card">
        <div class="overflow-x-auto">
            <table class="min-w-full sa-table">
                <thead>
                    <tr>
                        <th class="px-5 py-4 text-left">Garage</th>
                        <th class="px-5 py-4 text-left">Owner / Admin</th>
                        <th class="px-5 py-4 text-left">Status</th>
                        <th class="px-5 py-4 text-left">Plan</th>
                        <th class="px-5 py-4 text-left">Usage</th>
                        <th class="px-5 py-4 text-left">Created</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($garages as $garage)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('super-admin.garages.show', $garage) }}" class="font-black text-white hover:text-orange-300">{{ $garage->name }}</a>
                                <div class="mt-1 text-xs font-bold sa-muted">{{ $garage->phone ?? 'No phone' }} @if($garage->email) | {{ $garage->email }} @endif</div>
                                <div class="mt-1 text-xs font-bold sa-label">{{ $garage->address ?? 'No location set' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-white">{{ $garage->manager_name ?? 'Not set' }}</div>
                                <div class="text-xs font-bold sa-muted">{{ $garage->manager_email ?? $garage->manager_phone ?? 'No manager contact' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @php($status = $garage->status ?? 'active')
                                @include('super_admin.partials._badge', ['tone' => in_array($status, ['active', 'trial', 'pilot'], true) ? 'green' : 'red', 'label' => str($status)->headline()])
                            </td>
                            <td class="px-5 py-4 font-bold sa-muted">{{ $garage->plan?->name ?? 'No plan' }}</td>
                            <td class="px-5 py-4">
                                <div class="text-xs font-bold sa-muted">Leads: {{ $garage->leads_count ?? 0 }}</div>
                                <div class="text-xs font-bold sa-muted">Messages: {{ $garage->messages_count ?? 0 }}</div>
                                <div class="text-xs font-bold sa-muted">Users: {{ $garage->users_count ?? 0 }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $garage->created_at?->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('super-admin.garages.show', $garage) }}" class="rounded-xl border border-orange-400/30 px-3 py-2 text-xs font-black text-orange-300">View</a>
                                    <a href="{{ route('super-admin.garages.users', $garage) }}" class="rounded-xl border border-slate-400/30 px-3 py-2 text-xs font-black sa-muted">Users</a>
                                    <a href="{{ route('super-admin.garages.modules', $garage) }}" class="rounded-xl border border-slate-400/30 px-3 py-2 text-xs font-black sa-muted">Modules</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm font-bold sa-muted">No garages found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-700/50 px-5 py-4">{{ $garages->links() }}</div>
    </div>
@endsection
