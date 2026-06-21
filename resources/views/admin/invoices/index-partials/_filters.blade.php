{{-- resources/views/admin/invoices/index-partials/_filters.blade.php --}}

@php
    $currentStatus = $status ?? request('status', '');
    $currentSearch = $q ?? request('q', request('search', ''));

    $invoiceFilters = $invoiceFilters ?? [];

    $selectedRange = $invoiceFilters['date_range'] ?? request('date_range', 'all_time');
    $selectedSource = $invoiceFilters['lead_source'] ?? request('lead_source', 'all');
    $selectedAssignedUser = $invoiceFilters['assigned_user'] ?? request('assigned_user', 'all');
    $selectedServiceType = $invoiceFilters['service_type'] ?? request('service_type', 'all');
    $selectedCustomerType = $invoiceFilters['customer_type'] ?? request('customer_type', 'all');

    $assignedUsers = collect($assignedUsers ?? $users ?? $teamMembers ?? []);

    $statusLabels = [
        '' => 'All Invoices',
        'paid' => 'Paid',
        'pending' => 'Pending',
        'overdue' => 'Overdue',
    ];

    $rangeLabels = [
        'all_time' => 'All Time',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'custom' => 'Custom range',
    ];

    $sourceLabels = [
        'all' => 'All Sources',
        'whatsapp' => 'WhatsApp',
        'website' => 'Website',
        'meta' => 'Meta',
        'google' => 'Google',
        'manual' => 'Manual',
    ];

    $serviceLabels = [
        'all' => 'All Services',
        'general_service' => 'General Service',
        'repair' => 'Repair',
        'inspection' => 'Inspection',
        'detailing' => 'Detailing',
        'tyres' => 'Tyres',
        'battery' => 'Battery',
        'other' => 'Other',
    ];

    $customerLabels = [
        'all' => 'All Customers',
        'new' => 'New Customer',
        'returning' => 'Returning Customer',
        'existing' => 'Existing Customer',
        'fleet' => 'Fleet',
        'corporate' => 'Corporate',
    ];

    $assignedUserLabel = 'All Users';

    if ($selectedAssignedUser !== 'all') {
        $matchedUser = $assignedUsers->firstWhere('id', (int) $selectedAssignedUser);
        $assignedUserLabel = $matchedUser->name ?? 'User #' . $selectedAssignedUser;
    }

    $activeSummary = [
        filled($currentSearch) ? 'Search: ' . $currentSearch : 'No Search',
        $statusLabels[$currentStatus] ?? ucwords(str_replace('_', ' ', $currentStatus)),
        $rangeLabels[$selectedRange] ?? ucfirst(str_replace('_', ' ', $selectedRange)),
        $sourceLabels[$selectedSource] ?? ucfirst(str_replace('_', ' ', $selectedSource)),
        $assignedUserLabel,
        $serviceLabels[$selectedServiceType] ?? ucfirst(str_replace('_', ' ', $selectedServiceType)),
        $customerLabels[$selectedCustomerType] ?? ucfirst(str_replace('_', ' ', $selectedCustomerType)),
    ];

    if ($selectedRange === 'custom' && request('from_date') && request('to_date')) {
        $activeSummary[2] = 'Custom: ' . request('from_date') . ' to ' . request('to_date');
    }

    $hasActiveFilters =
        filled($currentSearch) ||
        filled($currentStatus) ||
        $selectedRange !== 'all_time' ||
        $selectedSource !== 'all' ||
        $selectedAssignedUser !== 'all' ||
        $selectedServiceType !== 'all' ||
        $selectedCustomerType !== 'all' ||
        request()->filled('from_date') ||
        request()->filled('to_date');
@endphp

<div
    id="sfInvoiceFilters"
    class="sf-invoices-panel rounded-2xl border p-4 shadow-sm"
    data-index-filter-panel
    data-date-range-control="#invoiceDateRange"
    data-custom-fields="#invoiceCustomDateFields"
>
    <form method="GET" action="{{ route('admin.invoices.index') }}">

        {{-- Compact collapsed row --}}
        <div class="flex cursor-pointer flex-col gap-3 xl:flex-row xl:items-center xl:justify-between" data-index-filter-summary>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="sf-invoice-title text-base font-extrabold tracking-tight">
                        Search & Filter Invoices
                    </h2>

                    @if($hasActiveFilters)
                        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                            Active
                        </span>
                    @endif

                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        @foreach($activeSummary as $summaryIndex => $summaryItem)
                            @php
                                $summaryTarget = [
                                    0 => '[name="q"]',
                                    1 => '[name="status"]',
                                    2 => '#invoiceDateRange',
                                    3 => '[name="lead_source"]',
                                    4 => '[name="assigned_user"]',
                                    5 => '[name="service_type"]',
                                    6 => '[name="customer_type"]',
                                ][$summaryIndex] ?? null;
                            @endphp

                            <button
                                type="button"
                                class="sf-invoice-filter-pill inline-flex cursor-pointer rounded-full border px-3 py-1 text-xs font-bold transition focus:outline-none focus:ring-2 focus:ring-orange-400/40"
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
                id="sfInvoiceFiltersToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
                data-index-filter-toggle
            >
                Show Filters
            </button>
        </div>

        {{-- Expandable body --}}
        <div id="sfInvoiceFiltersBody" class="mt-5 hidden" data-index-filter-body>

            {{-- Search --}}
            <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Search
                </label>

                <input
                    type="text"
                    name="q"
                    value="{{ $currentSearch }}"
                    placeholder="Search invoice no, client, phone, job code, amount..."
                    class="sf-invoice-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                >
            </div>

            {{-- Primary filters --}}
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                    <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Status
                    </label>

                    <select name="status" class="sf-invoice-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected($currentStatus === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                    <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Date Range
                    </label>

                    <select
                        id="invoiceDateRange"
                        name="date_range"
                        class="sf-invoice-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition"
                    >
                        <option value="all_time" @selected($selectedRange === 'all_time')>All Time</option>
                        <option value="today" @selected($selectedRange === 'today')>Today</option>
                        <option value="yesterday" @selected($selectedRange === 'yesterday')>Yesterday</option>
                        <option value="last_7_days" @selected($selectedRange === 'last_7_days')>Last 7 days</option>
                        <option value="this_month" @selected($selectedRange === 'this_month')>This month</option>
                        <option value="last_month" @selected($selectedRange === 'last_month')>Last month</option>
                        <option value="custom" @selected($selectedRange === 'custom')>Custom range</option>
                    </select>
                </div>

                <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                    <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Lead Source
                    </label>

                    <select name="lead_source" class="sf-invoice-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedSource === 'all')>All Sources</option>
                        <option value="whatsapp" @selected($selectedSource === 'whatsapp')>WhatsApp</option>
                        <option value="website" @selected($selectedSource === 'website')>Website</option>
                        <option value="meta" @selected($selectedSource === 'meta')>Meta</option>
                        <option value="google" @selected($selectedSource === 'google')>Google</option>
                        <option value="manual" @selected($selectedSource === 'manual')>Manual</option>
                    </select>
                </div>

                <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                    <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Assigned User
                    </label>

                    <select name="assigned_user" class="sf-invoice-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedAssignedUser === 'all')>All Users</option>

                        @foreach($assignedUsers as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected((string) $selectedAssignedUser === (string) $assignedUser->id)>
                                {{ $assignedUser->name ?? 'User #' . $assignedUser->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                    <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Service Type
                    </label>

                    <select name="service_type" class="sf-invoice-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
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
            </div>

            {{-- Secondary filter --}}
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="sf-invoice-soft-panel rounded-2xl border p-4">
                    <label class="sf-invoice-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Customer Type
                    </label>

                    <select name="customer_type" class="sf-invoice-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedCustomerType === 'all')>All Customers</option>
                        <option value="new" @selected($selectedCustomerType === 'new')>New Customer</option>
                        <option value="returning" @selected($selectedCustomerType === 'returning')>Returning Customer</option>
                        <option value="existing" @selected($selectedCustomerType === 'existing')>Existing Customer</option>
                        <option value="fleet" @selected($selectedCustomerType === 'fleet')>Fleet</option>
                        <option value="corporate" @selected($selectedCustomerType === 'corporate')>Corporate</option>
                    </select>
                </div>
            </div>

            {{-- Custom date range only --}}
            <div
                id="invoiceCustomDateFields"
                class="mt-4 grid grid-cols-1 gap-4 rounded-2xl border border-orange-500/20 bg-orange-500/10 p-4 md:grid-cols-2 lg:max-w-xl"
                style="{{ $selectedRange === 'custom' ? '' : 'display: none;' }}"
            >
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        From Date
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        value="{{ $invoiceFilters['from_date'] ?? request('from_date') }}"
                        class="sf-invoice-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ $invoiceFilters['to_date'] ?? request('to_date') }}"
                        class="sf-invoice-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                    >
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-800 pt-4">
                <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                    Reset
                </a>

                <button type="submit" class="sf-btn-primary">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

@include('admin.partials._index_filter_chip_script')
