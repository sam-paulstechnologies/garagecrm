{{-- resources/views/admin/dashboard/partials/_dashboard_filters.blade.php --}}

@php
    $selectedRange = request('date_range', 'this_month');
    $selectedSource = request('lead_source', 'all');
    $selectedAssignedUser = request('assigned_user', 'all');
    $selectedServiceType = request('service_type', 'all');
    $selectedCustomerType = request('customer_type', 'all');

    $assignedUsers = collect($assignedUsers ?? $users ?? $teamMembers ?? []);

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
    ];

    $assignedUserLabel = 'All Users';

    if ($selectedAssignedUser !== 'all') {
        $matchedUser = $assignedUsers->firstWhere('id', (int) $selectedAssignedUser);
        $assignedUserLabel = $matchedUser->name ?? 'User #' . $selectedAssignedUser;
    }

    $activeSummary = [
        $rangeLabels[$selectedRange] ?? ucfirst(str_replace('_', ' ', $selectedRange)),
        $sourceLabels[$selectedSource] ?? ucfirst(str_replace('_', ' ', $selectedSource)),
        $assignedUserLabel,
        $serviceLabels[$selectedServiceType] ?? ucfirst(str_replace('_', ' ', $selectedServiceType)),
        $customerLabels[$selectedCustomerType] ?? ucfirst(str_replace('_', ' ', $selectedCustomerType)),
    ];

    if ($selectedRange === 'custom' && request('from_date') && request('to_date')) {
        $activeSummary[0] = 'Custom: ' . request('from_date') . ' to ' . request('to_date');
    }

    $hasActiveFilters =
        $selectedRange !== 'this_month' ||
        $selectedSource !== 'all' ||
        $selectedAssignedUser !== 'all' ||
        $selectedServiceType !== 'all' ||
        $selectedCustomerType !== 'all' ||
        request()->filled('from_date') ||
        request()->filled('to_date');

    $serverDefaultCollapsed = 'true';
@endphp

<style>
    .sf-dashboard-filter-panel {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-dashboard-filter-title {
        color: #ffffff;
    }

    .sf-dashboard-filter-muted {
        color: #cbd5e1;
    }

    .sf-dashboard-filter-box {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-dashboard-filter-label {
        color: #94a3b8;
    }

    .sf-dashboard-filter-control {
        border-color: rgba(255, 255, 255, 0.14);
        background: rgba(15, 23, 42, 0.85);
        color: #ffffff;
    }

    .sf-dashboard-filter-summary-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-dashboard-filter-secondary-btn {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sf-dashboard-filter-secondary-btn:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    html[data-theme="light"] .sf-dashboard-filter-panel {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06) !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-box {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-control {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-summary-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-secondary-btn {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-dashboard-filter-secondary-btn:hover {
        background: #f8fafc !important;
    }
</style>

<div
    id="sfDashboardFilters"
    class="sf-dashboard-filter-panel rounded-2xl border p-4 shadow-sm"
    data-default-collapsed="{{ $serverDefaultCollapsed }}"
>
    <form method="GET" action="{{ url()->current() }}">

        {{-- Compact Header / Collapsed View --}}
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="sf-dashboard-filter-title text-base font-extrabold tracking-tight">
                        Filter Dashboard
                    </h2>

                    @if($hasActiveFilters)
                        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                            Active
                        </span>
                    @endif

                    <div id="sfDashboardFiltersSummary" class="flex min-w-0 flex-wrap items-center gap-2">
                        @foreach($activeSummary as $summaryItem)
                            <span class="sf-dashboard-filter-summary-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                                {{ $summaryItem }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button
                    type="button"
                    id="sfDashboardFiltersToggle"
                    class="sf-dashboard-filter-secondary-btn inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                    aria-expanded="false"
                >
                    Show Filters
                </button>
            </div>
        </div>

        {{-- Expandable Body --}}
        <div id="sfDashboardFiltersBody" class="mt-5 hidden">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

                {{-- Date Range --}}
                <div class="sf-dashboard-filter-box rounded-2xl border p-4">
                    <label class="sf-dashboard-filter-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Date Range
                    </label>

                    <select
                        id="dashboardDateRange"
                        name="date_range"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
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

                {{-- Lead Source --}}
                <div class="sf-dashboard-filter-box rounded-2xl border p-4">
                    <label class="sf-dashboard-filter-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Lead Source
                    </label>

                    <select
                        name="lead_source"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
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

                {{-- Assigned User --}}
                <div class="sf-dashboard-filter-box rounded-2xl border p-4">
                    <label class="sf-dashboard-filter-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Assigned User
                    </label>

                    <select
                        name="assigned_user"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
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
                <div class="sf-dashboard-filter-box rounded-2xl border p-4">
                    <label class="sf-dashboard-filter-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Service Type
                    </label>

                    <select
                        name="service_type"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
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
                <div class="sf-dashboard-filter-box rounded-2xl border p-4">
                    <label class="sf-dashboard-filter-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Customer Type
                    </label>

                    <select
                        name="customer_type"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
                        <option value="all" @selected($selectedCustomerType === 'all')>All Customers</option>
                        <option value="new" @selected($selectedCustomerType === 'new')>New Customer</option>
                        <option value="returning" @selected($selectedCustomerType === 'returning')>Returning Customer</option>
                    </select>
                </div>
            </div>

            {{-- Custom Date Range --}}
            <div
                id="dashboardCustomDateFields"
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
                        value="{{ request('from_date') }}"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ request('to_date') }}"
                        class="sf-dashboard-filter-control h-11 w-full rounded-xl border px-3 text-sm font-bold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    >
                </div>
            </div>

            {{-- Expanded Actions --}}
            <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-white/10 pt-4">
                <a
                    href="{{ url()->current() }}"
                    class="sf-dashboard-filter-secondary-btn inline-flex h-11 items-center justify-center rounded-xl border px-5 text-sm font-bold transition"
                >
                    Reset Filters
                </a>

                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-6 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('sfDashboardFilters');

        if (!root) {
            return;
        }

        var body = document.getElementById('sfDashboardFiltersBody');
        var toggle = document.getElementById('sfDashboardFiltersToggle');
        var dateRange = document.getElementById('dashboardDateRange');
        var customFields = document.getElementById('dashboardCustomDateFields');

        var storageKey = 'sayaraforce_dashboard_filters_collapsed';
        var defaultCollapsed = root.getAttribute('data-default-collapsed') === 'true';

        function getInitialCollapsed() {
            try {
                var saved = localStorage.getItem(storageKey);

                if (saved === 'true') {
                    return true;
                }

                if (saved === 'false') {
                    return false;
                }
            } catch (e) {}

            return defaultCollapsed;
        }

        function applyCollapsedState(collapsed) {
            if (!body || !toggle) {
                return;
            }

            if (collapsed) {
                body.classList.add('hidden');
                toggle.textContent = 'Show Filters';
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                body.classList.remove('hidden');
                toggle.textContent = 'Hide Filters';
                toggle.setAttribute('aria-expanded', 'true');
            }

            try {
                localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            } catch (e) {}
        }

        function syncCustomDateFields() {
            if (!dateRange || !customFields) {
                return;
            }

            if (dateRange.value === 'custom') {
                customFields.style.display = '';
            } else {
                customFields.style.display = 'none';
            }
        }

        var collapsed = getInitialCollapsed();
        applyCollapsedState(collapsed);
        syncCustomDateFields();

        if (toggle) {
            toggle.addEventListener('click', function () {
                collapsed = !collapsed;
                applyCollapsedState(collapsed);
            });
        }

        if (dateRange) {
            dateRange.addEventListener('change', function () {
                syncCustomDateFields();

                if (dateRange.value === 'custom') {
                    collapsed = false;
                    applyCollapsedState(false);
                }
            });
        }
    });
</script>
