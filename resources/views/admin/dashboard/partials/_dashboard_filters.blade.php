{{-- resources/views/admin/dashboard/partials/_dashboard_filters.blade.php --}}

@php
    $selectedRange = request('date_range', 'this_month');
    $selectedSource = request('lead_source', 'all');
    $selectedAssignedUser = request('assigned_user', 'all');
    $selectedServiceType = request('service_type', 'all');
    $selectedCustomerType = request('customer_type', 'all');

    $assignedUsers = collect($assignedUsers ?? $users ?? $teamMembers ?? []);
@endphp

<div
    x-data="{ dateRange: '{{ $selectedRange }}' }"
    class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm"
>
    <form method="GET" action="{{ url()->current() }}">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-extrabold tracking-tight text-white">
                    Filter Dashboard
                </h2>
                <p class="mt-1 text-sm text-slate-400">
                    Narrow the performance view by period, lead source, owner, service type, and customer type.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <a
                    href="{{ url()->current() }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-5 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    Reset Filters
                </a>

                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-6 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    Apply Filters
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

            {{-- Date Range --}}
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Date Range
                </label>

                <select
                    name="date_range"
                    x-model="dateRange"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="last_7_days">Last 7 days</option>
                    <option value="this_month">This month</option>
                    <option value="last_month">Last month</option>
                    <option value="all_time">All Time</option>
                    <option value="custom">Custom range</option>
                </select>
            </div>

            {{-- Lead Source --}}
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Lead Source
                </label>

                <select
                    name="lead_source"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedSource === 'all')>All Sources</option>
                    <option value="whatsapp" @selected($selectedSource === 'whatsapp')>WhatsApp</option>
                    <option value="website" @selected($selectedSource === 'website')>Website</option>
                    <option value="meta" @selected($selectedSource === 'meta')>Meta</option>
                    <option value="google" @selected($selectedSource === 'google')>Google</option>
                    <option value="manual" @selected($selectedSource === 'manual')>Manual</option>
                </select>
            </div>

            {{-- Assigned User --}}
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Assigned User
                </label>

                <select
                    name="assigned_user"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedAssignedUser === 'all')>All Users</option>

                    @foreach($assignedUsers as $assignedUser)
                        <option value="{{ $assignedUser->id }}" @selected((string) $selectedAssignedUser === (string) $assignedUser->id)>
                            {{ $assignedUser->name ?? 'User #' . $assignedUser->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Service Type --}}
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Service Type
                </label>

                <select
                    name="service_type"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedServiceType === 'all')>All Services</option>
                    <option value="general_service" @selected($selectedServiceType === 'general_service')>General Service</option>
                    <option value="repair" @selected($selectedServiceType === 'repair')>Repair</option>
                    <option value="inspection" @selected($selectedServiceType === 'inspection')>Inspection</option>
                    <option value="detailing" @selected($selectedServiceType === 'detailing')>Detailing</option>
                    <option value="tyres" @selected($selectedServiceType === 'tyres')>Tyres</option>
                    <option value="battery" @selected($selectedServiceType === 'battery')>Battery</option>
                    <option value="other" @selected($selectedServiceType === 'other')>Other</option>
                </select>
            </div>

            {{-- Customer Type --}}
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Customer Type
                </label>

                <select
                    name="customer_type"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedCustomerType === 'all')>All Customers</option>
                    <option value="new" @selected($selectedCustomerType === 'new')>New Customer</option>
                    <option value="returning" @selected($selectedCustomerType === 'returning')>Returning Customer</option>
                </select>
            </div>
        </div>

        {{-- Custom Date Range: shows immediately when Custom range is selected --}}
        <div
            x-cloak
            x-show="dateRange === 'custom'"
            x-transition
            class="mt-4 grid grid-cols-1 gap-4 rounded-2xl border border-orange-500/20 bg-orange-500/10 p-4 md:grid-cols-2 lg:max-w-xl"
        >
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-200">
                    From Date
                </label>

                <input
                    type="date"
                    name="from_date"
                    value="{{ request('from_date') }}"
                    class="h-11 w-full rounded-xl border border-orange-400/30 bg-slate-950 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-200">
                    To Date
                </label>

                <input
                    type="date"
                    name="to_date"
                    value="{{ request('to_date') }}"
                    class="h-11 w-full rounded-xl border border-orange-400/30 bg-slate-950 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
            </div>
        </div>
    </form>
</div>