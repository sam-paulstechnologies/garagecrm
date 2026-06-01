{{-- resources/views/admin/clients/index-partials/_filters.blade.php --}}

@php
    $filters = $filters ?? [];

    $q = $q ?? request('q', '');

    $selectedCustomerType = $filters['customer_type'] ?? request('customer_type', 'all');
    $selectedVehicleMake = $filters['vehicle_make'] ?? request('vehicle_make', 'all');
    $selectedServiceHistory = $filters['service_history'] ?? request('service_history', 'all');
    $selectedLastActivity = $filters['last_activity'] ?? request('last_activity', 'all');
    $selectedSource = $filters['source'] ?? request('source', 'all');

    $vehicleMakes = collect($vehicleMakes ?? []);
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <form method="GET" action="{{ route('admin.clients.index') }}" class="space-y-5">

        <div>
            <h2 class="text-base font-extrabold tracking-tight text-white">
                Search & Filter Clients
            </h2>

            <p class="mt-1 text-xs font-medium text-slate-400">
                Find customers by contact details, vehicle information, source, or service history.
            </p>
        </div>

        {{-- Search --}}
        <div>
            <label for="client-search" class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Search
            </label>

            <input
                type="text"
                id="client-search"
                name="q"
                value="{{ $q }}"
                placeholder="Search client, phone, email, WhatsApp, vehicle make/model, plate number, VIN..."
                class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-semibold text-white outline-none transition placeholder:text-slate-600 focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
            >
        </div>

        {{-- Filters --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

            {{-- Customer Type --}}
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Customer Type
                </label>

                <select
                    name="customer_type"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedCustomerType === 'all')>All Customers</option>
                    <option value="new" @selected($selectedCustomerType === 'new')>New Customers</option>
                    <option value="returning" @selected($selectedCustomerType === 'returning')>Returning Customers</option>
                    <option value="vip" @selected($selectedCustomerType === 'vip')>VIP Customers</option>
                </select>
            </div>

            {{-- Vehicle Make --}}
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Vehicle Make
                </label>

                <select
                    name="vehicle_make"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedVehicleMake === 'all')>All Makes</option>

                    @foreach($vehicleMakes as $make)
                        <option value="{{ $make->id }}" @selected((string) $selectedVehicleMake === (string) $make->id)>
                            {{ $make->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Service History --}}
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Service History
                </label>

                <select
                    name="service_history"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedServiceHistory === 'all')>All Clients</option>
                    <option value="has_booking" @selected($selectedServiceHistory === 'has_booking')>Has Booking</option>
                    <option value="has_job" @selected($selectedServiceHistory === 'has_job')>Has Job</option>
                    <option value="has_invoice" @selected($selectedServiceHistory === 'has_invoice')>Has Invoice</option>
                    <option value="has_unpaid_invoice" @selected($selectedServiceHistory === 'has_unpaid_invoice')>Has Unpaid Invoice</option>
                </select>
            </div>

            {{-- Last Activity --}}
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Last Activity
                </label>

                <select
                    name="last_activity"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedLastActivity === 'all')>All Time</option>
                    <option value="last_7_days" @selected($selectedLastActivity === 'last_7_days')>Last 7 Days</option>
                    <option value="this_month" @selected($selectedLastActivity === 'this_month')>This Month</option>
                    <option value="last_90_days" @selected($selectedLastActivity === 'last_90_days')>Last 90 Days</option>
                </select>
            </div>

            {{-- Source --}}
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Source
                </label>

                <select
                    name="source"
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-bold text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
                    <option value="all" @selected($selectedSource === 'all')>All Sources</option>
                    <option value="whatsapp" @selected($selectedSource === 'whatsapp')>WhatsApp</option>
                    <option value="website" @selected($selectedSource === 'website')>Website</option>
                    <option value="meta" @selected($selectedSource === 'meta')>Meta</option>
                    <option value="google" @selected($selectedSource === 'google')>Google</option>
                    <option value="manual" @selected($selectedSource === 'manual')>Manual</option>
                </select>
            </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-slate-800 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-semibold text-slate-500">
                Filters apply to active clients only. Archived clients are available from the archive button.
            </p>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.clients.index') }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-5 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-6 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    Apply Filters
                </button>
            </div>
        </div>

    </form>
</div>