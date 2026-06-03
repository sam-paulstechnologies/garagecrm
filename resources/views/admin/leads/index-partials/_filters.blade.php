{{-- resources/views/admin/leads/index-partials/_filters.blade.php --}}

@php
    $pageMode = $pageMode ?? 'open';
    $bucket = $bucket ?? '';
    $q = $q ?? request('q');

    $leadFilters = $leadFilters ?? [];

    $selectedRange = $leadFilters['date_range'] ?? request('date_range', 'all_time');
    $selectedSource = $leadFilters['lead_source'] ?? request('lead_source', 'all');
    $selectedAssignedUser = $leadFilters['assigned_user'] ?? request('assigned_user', 'all');
    $selectedServiceType = $leadFilters['service_type'] ?? request('service_type', 'all');
    $selectedCustomerType = $leadFilters['customer_type'] ?? request('customer_type', 'all');

    $assignedUsers = collect($assignedUsers ?? []);

    $filterRoute = match ($pageMode) {
        'qualified' => route('admin.leads.qualified'),
        'disqualified' => route('admin.leads.disqualified'),
        default => route('admin.leads.index'),
    };

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
        'tiktok' => 'TikTok',
        'manual' => 'Manual',
        'imported_historic' => 'Imported Historic',
        'imported_recent' => 'Imported Recent',
        'walk-in' => 'Walk-in',
        'referral' => 'Referral',
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
        $q ? 'Search: ' . $q : 'No Search',
        $rangeLabels[$selectedRange] ?? ucfirst(str_replace('_', ' ', $selectedRange)),
        $sourceLabels[$selectedSource] ?? ucfirst(str_replace('_', ' ', $selectedSource)),
        $assignedUserLabel,
        $serviceLabels[$selectedServiceType] ?? ucfirst(str_replace('_', ' ', $selectedServiceType)),
        $customerLabels[$selectedCustomerType] ?? ucfirst(str_replace('_', ' ', $selectedCustomerType)),
    ];

    if ($selectedRange === 'custom' && request('from_date') && request('to_date')) {
        $activeSummary[1] = 'Custom: ' . request('from_date') . ' to ' . request('to_date');
    }

    $hasActiveFilters =
        filled($q) ||
        filled($bucket) ||
        $selectedRange !== 'all_time' ||
        $selectedSource !== 'all' ||
        $selectedAssignedUser !== 'all' ||
        $selectedServiceType !== 'all' ||
        $selectedCustomerType !== 'all' ||
        request()->filled('from_date') ||
        request()->filled('to_date');
@endphp

<div id="sfLeadFilters" class="sf-leads-panel rounded-2xl border p-4 shadow-sm">
    <form method="GET" action="{{ $filterRoute }}">

        @if($pageMode === 'open' && ! blank($bucket))
            <input type="hidden" name="bucket" value="{{ $bucket }}">
        @endif

        {{-- Compact collapsed row --}}
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="sf-leads-title text-base font-extrabold tracking-tight">
                        Search & Filter Leads
                    </h2>

                    @if($hasActiveFilters)
                        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                            Active
                        </span>
                    @endif

                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        @foreach($activeSummary as $summaryItem)
                            <span class="sf-leads-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                                {{ $summaryItem }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <button
                type="button"
                id="sfLeadFiltersToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
            >
                Show Filters
            </button>
        </div>

        {{-- Expandable body --}}
        <div id="sfLeadFiltersBody" class="mt-5 hidden">

            {{-- Search --}}
            <div class="sf-leads-soft-panel rounded-2xl border p-4">
                <label for="lead-search" class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Search
                </label>

                <input
                    id="lead-search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search name, phone, email, source, category, vehicle, campaign..."
                    class="sf-leads-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                >
            </div>

            {{-- Filters --}}
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="sf-leads-soft-panel rounded-2xl border p-4">
                    <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Date Range
                    </label>

                    <select
                        id="leadDateRange"
                        name="date_range"
                        class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition"
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

                <div class="sf-leads-soft-panel rounded-2xl border p-4">
                    <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Lead Source
                    </label>

                    <select name="lead_source" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedSource === 'all')>All Sources</option>
                        <option value="whatsapp" @selected($selectedSource === 'whatsapp')>WhatsApp</option>
                        <option value="website" @selected($selectedSource === 'website')>Website</option>
                        <option value="meta" @selected($selectedSource === 'meta')>Meta</option>
                        <option value="google" @selected($selectedSource === 'google')>Google</option>
                        <option value="tiktok" @selected($selectedSource === 'tiktok')>TikTok</option>
                        <option value="manual" @selected($selectedSource === 'manual')>Manual</option>
                        <option value="imported_historic" @selected($selectedSource === 'imported_historic')>Imported Historic</option>
                        <option value="imported_recent" @selected($selectedSource === 'imported_recent')>Imported Recent</option>
                        <option value="walk-in" @selected($selectedSource === 'walk-in')>Walk-in</option>
                        <option value="referral" @selected($selectedSource === 'referral')>Referral</option>
                    </select>
                </div>

                <div class="sf-leads-soft-panel rounded-2xl border p-4">
                    <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Assigned User
                    </label>

                    <select name="assigned_user" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedAssignedUser === 'all')>All Users</option>

                        @foreach($assignedUsers as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected((string) $selectedAssignedUser === (string) $assignedUser->id)>
                                {{ $assignedUser->name ?? 'User #' . $assignedUser->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-leads-soft-panel rounded-2xl border p-4">
                    <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Service Type
                    </label>

                    <select name="service_type" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
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

                <div class="sf-leads-soft-panel rounded-2xl border p-4">
                    <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Customer Type
                    </label>

                    <select name="customer_type" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
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
                id="leadCustomDateFields"
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
                        value="{{ $leadFilters['from_date'] ?? request('from_date') }}"
                        class="sf-leads-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ $leadFilters['to_date'] ?? request('to_date') }}"
                        class="sf-leads-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                    >
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-800 pt-4">
                <a href="{{ $filterRoute }}" class="sf-btn-secondary">
                    Reset
                </a>

                <button type="submit" class="sf-btn-primary">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.getElementById('sfLeadFiltersBody');
        var toggle = document.getElementById('sfLeadFiltersToggle');
        var dateRange = document.getElementById('leadDateRange');
        var customFields = document.getElementById('leadCustomDateFields');

        if (!body || !toggle) {
            return;
        }

        var collapsed = true;

        function applyState() {
            if (collapsed) {
                body.classList.add('hidden');
                toggle.textContent = 'Show Filters';
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                body.classList.remove('hidden');
                toggle.textContent = 'Hide Filters';
                toggle.setAttribute('aria-expanded', 'true');
            }
        }

        function syncCustomDateFields() {
            if (!dateRange || !customFields) {
                return;
            }

            customFields.style.display = dateRange.value === 'custom' ? '' : 'none';
        }

        toggle.addEventListener('click', function () {
            collapsed = !collapsed;
            applyState();
        });

        if (dateRange) {
            dateRange.addEventListener('change', function () {
                syncCustomDateFields();

                if (dateRange.value === 'custom') {
                    collapsed = false;
                    applyState();
                }
            });
        }

        applyState();
        syncCustomDateFields();
    });
</script>
