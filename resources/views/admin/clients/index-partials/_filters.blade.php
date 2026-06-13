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

    $customerTypeLabels = [
        'all' => 'All Customers',
        'new' => 'New Customers',
        'returning' => 'Returning Customers',
        'vip' => 'VIP Customers',
    ];

    $serviceHistoryLabels = [
        'all' => 'All Clients',
        'has_booking' => 'Has Booking',
        'has_job' => 'Has Job',
        'has_invoice' => 'Has Invoice',
        'has_unpaid_invoice' => 'Has Unpaid Invoice',
    ];

    $lastActivityLabels = [
        'all' => 'All Time',
        'last_7_days' => 'Last 7 Days',
        'this_month' => 'This Month',
        'last_90_days' => 'Last 90 Days',
    ];

    $sourceLabels = [
        'all' => 'All Sources',
        'whatsapp' => 'WhatsApp',
        'website' => 'Website',
        'meta' => 'Meta',
        'google' => 'Google',
        'manual' => 'Manual',
    ];

    $vehicleMakeLabel = 'All Makes';

    if ($selectedVehicleMake !== 'all') {
        $matchedMake = $vehicleMakes->firstWhere('id', (int) $selectedVehicleMake);
        $vehicleMakeLabel = $matchedMake->name ?? 'Make #' . $selectedVehicleMake;
    }

    $activeSummary = [
        $q ? 'Search: ' . $q : 'No Search',
        $customerTypeLabels[$selectedCustomerType] ?? ucfirst(str_replace('_', ' ', $selectedCustomerType)),
        $vehicleMakeLabel,
        $serviceHistoryLabels[$selectedServiceHistory] ?? ucfirst(str_replace('_', ' ', $selectedServiceHistory)),
        $lastActivityLabels[$selectedLastActivity] ?? ucfirst(str_replace('_', ' ', $selectedLastActivity)),
        $sourceLabels[$selectedSource] ?? ucfirst(str_replace('_', ' ', $selectedSource)),
    ];

    $hasActiveFilters =
        filled($q) ||
        $selectedCustomerType !== 'all' ||
        $selectedVehicleMake !== 'all' ||
        $selectedServiceHistory !== 'all' ||
        $selectedLastActivity !== 'all' ||
        $selectedSource !== 'all';
@endphp

<style>
    .sf-client-filter-panel {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-client-filter-title {
        color: #ffffff;
    }

    .sf-client-filter-muted {
        color: #cbd5e1;
    }

    .sf-client-filter-box {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-client-filter-label {
        color: #94a3b8;
    }

    .sf-client-filter-control {
        border-color: rgba(255, 255, 255, 0.14);
        background: rgba(15, 23, 42, 0.85);
        color: #ffffff;
    }

    .sf-client-filter-control::placeholder {
        color: #64748b;
    }

    .sf-client-filter-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-client-filter-secondary-btn {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sf-client-filter-secondary-btn:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    html[data-theme="light"] .sf-client-filter-panel {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06) !important;
    }

    html[data-theme="light"] .sf-client-filter-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-client-filter-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-client-filter-box {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-client-filter-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-client-filter-control {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-client-filter-control::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-client-filter-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    .sf-client-filter-pill:hover,
    .sf-client-filter-pill:focus {
        border-color: rgba(251, 146, 60, 0.50);
        background: rgba(249, 115, 22, 0.16);
        color: #fed7aa;
    }

    html[data-theme="light"] .sf-client-filter-pill:hover,
    html[data-theme="light"] .sf-client-filter-pill:focus {
        border-color: #fdba74 !important;
        background: #fff7ed !important;
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-client-filter-secondary-btn {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-client-filter-secondary-btn:hover {
        background: #f8fafc !important;
    }
</style>

<div
    id="sfClientFilters"
    class="sf-client-filter-panel rounded-2xl border px-4 py-3 shadow-sm"
    data-index-filter-panel
>
    <form method="GET" action="{{ route('admin.clients.index') }}">

        {{-- Compact collapsed row --}}
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="sf-client-filter-title text-sm font-extrabold tracking-tight">
                        Search & Filter Clients
                    </h2>

                    @if($hasActiveFilters)
                        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-2 py-0.5 text-[11px] font-black text-orange-300">
                            Active
                        </span>
                    @endif

                    <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                        @foreach($activeSummary as $summaryIndex => $summaryItem)
                            @php
                                $summaryTarget = [
                                    0 => '#client-search',
                                    1 => '[name="customer_type"]',
                                    2 => '[name="vehicle_make"]',
                                    3 => '[name="service_history"]',
                                    4 => '[name="last_activity"]',
                                    5 => '[name="source"]',
                                ][$summaryIndex] ?? null;
                            @endphp

                            <button
                                type="button"
                                class="sf-client-filter-pill inline-flex cursor-pointer rounded-full border px-2 py-0.5 text-[11px] font-bold transition focus:outline-none focus:ring-2 focus:ring-orange-400/40"
                                data-index-filter-chip
                                data-filter-target="{{ $summaryTarget }}"
                                aria-label="Open {{ $summaryItem }} filter"
                            >
                                {{ $summaryItem }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <button
                type="button"
                id="sfClientFiltersToggle"
                class="sf-client-filter-secondary-btn inline-flex h-8 w-fit shrink-0 items-center justify-center rounded-lg border px-3 text-xs font-bold transition"
                aria-expanded="false"
                data-index-filter-toggle
            >
                Show Filters
            </button>
        </div>

        {{-- Expandable body --}}
        <div id="sfClientFiltersBody" class="mt-3 hidden" data-index-filter-body>

            {{-- Search --}}
            <div class="sf-client-filter-box rounded-xl border p-3">
                <label for="client-search" class="sf-client-filter-label mb-1.5 block text-[11px] font-extrabold uppercase tracking-wide">
                    Search
                </label>

                <input
                    type="text"
                    id="client-search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search client, phone, email, WhatsApp, vehicle make/model, plate number, VIN..."
                    class="sf-client-filter-control h-10 w-full rounded-lg border px-3 text-sm font-semibold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >
            </div>

            {{-- Filters --}}
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">

                {{-- Customer Type --}}
                <div class="sf-client-filter-box rounded-xl border p-3">
                    <label class="sf-client-filter-label mb-1.5 block text-[11px] font-extrabold uppercase tracking-wide">
                        Customer Type
                    </label>

                    <select
                        name="customer_type"
                        class="sf-client-filter-control h-10 w-full rounded-lg border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
                        <option value="all" @selected($selectedCustomerType === 'all')>All Customers</option>
                        <option value="new" @selected($selectedCustomerType === 'new')>New Customers</option>
                        <option value="returning" @selected($selectedCustomerType === 'returning')>Returning Customers</option>
                        <option value="vip" @selected($selectedCustomerType === 'vip')>VIP Customers</option>
                    </select>
                </div>

                {{-- Vehicle Make --}}
                <div class="sf-client-filter-box rounded-xl border p-3">
                    <label class="sf-client-filter-label mb-1.5 block text-[11px] font-extrabold uppercase tracking-wide">
                        Vehicle Make
                    </label>

                    <select
                        name="vehicle_make"
                        class="sf-client-filter-control h-10 w-full rounded-lg border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
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
                <div class="sf-client-filter-box rounded-xl border p-3">
                    <label class="sf-client-filter-label mb-1.5 block text-[11px] font-extrabold uppercase tracking-wide">
                        Service History
                    </label>

                    <select
                        name="service_history"
                        class="sf-client-filter-control h-10 w-full rounded-lg border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
                        <option value="all" @selected($selectedServiceHistory === 'all')>All Clients</option>
                        <option value="has_booking" @selected($selectedServiceHistory === 'has_booking')>Has Booking</option>
                        <option value="has_job" @selected($selectedServiceHistory === 'has_job')>Has Job</option>
                        <option value="has_invoice" @selected($selectedServiceHistory === 'has_invoice')>Has Invoice</option>
                        <option value="has_unpaid_invoice" @selected($selectedServiceHistory === 'has_unpaid_invoice')>Has Unpaid Invoice</option>
                    </select>
                </div>

                {{-- Last Activity --}}
                <div class="sf-client-filter-box rounded-xl border p-3">
                    <label class="sf-client-filter-label mb-1.5 block text-[11px] font-extrabold uppercase tracking-wide">
                        Last Activity
                    </label>

                    <select
                        name="last_activity"
                        class="sf-client-filter-control h-10 w-full rounded-lg border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
                        <option value="all" @selected($selectedLastActivity === 'all')>All Time</option>
                        <option value="last_7_days" @selected($selectedLastActivity === 'last_7_days')>Last 7 Days</option>
                        <option value="this_month" @selected($selectedLastActivity === 'this_month')>This Month</option>
                        <option value="last_90_days" @selected($selectedLastActivity === 'last_90_days')>Last 90 Days</option>
                    </select>
                </div>

                {{-- Source --}}
                <div class="sf-client-filter-box rounded-xl border p-3">
                    <label class="sf-client-filter-label mb-1.5 block text-[11px] font-extrabold uppercase tracking-wide">
                        Source
                    </label>

                    <select
                        name="source"
                        class="sf-client-filter-control h-10 w-full rounded-lg border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
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

            {{-- Actions --}}
            <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-white/10 pt-3">
                <a
                    href="{{ route('admin.clients.index') }}"
                    class="sf-client-filter-secondary-btn inline-flex h-9 items-center justify-center rounded-lg border px-4 text-xs font-bold transition"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-orange-500 px-5 text-xs font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

@include('admin.partials._index_filter_chip_script')
