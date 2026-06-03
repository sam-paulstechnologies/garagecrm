{{-- resources/views/admin/opportunities/index-partials/_filters.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $stage = $stage ?? request('stage', '');
    $priority = $priority ?? request('priority', '');
    $bucket = $bucket ?? request('bucket', '');

    $opportunityFilters = $opportunityFilters ?? [];

    $selectedRange = $opportunityFilters['date_range'] ?? request('date_range', 'all_time');
    $selectedSource = $opportunityFilters['lead_source'] ?? request('lead_source', 'all');
    $selectedAssignedUser = $opportunityFilters['assigned_user'] ?? request('assigned_user', 'all');
    $selectedServiceType = $opportunityFilters['service_type'] ?? request('service_type', 'all');
    $selectedCustomerType = $opportunityFilters['customer_type'] ?? request('customer_type', 'all');

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

    $stageLabels = [
        '' => 'All Stages',
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'manager_confirmation_pending' => 'Manager Confirmation Pending',
        'appointment' => 'Appointment Planned',
        'closed_won' => 'Booking Confirmed',
        'closed_lost' => 'Closed Lost',
    ];

    $priorityLabels = [
        '' => 'All Priorities',
        'urgent' => 'Urgent',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];

    $assignedUserLabel = 'All Users';

    if ($selectedAssignedUser !== 'all') {
        $matchedUser = $assignedUsers->firstWhere('id', (int) $selectedAssignedUser);
        $assignedUserLabel = $matchedUser->name ?? 'User #' . $selectedAssignedUser;
    }

    $activeSummary = [
        $q ? 'Search: ' . $q : 'No Search',
        $stageLabels[$stage] ?? ucwords(str_replace('_', ' ', $stage ?: 'all stages')),
        $priorityLabels[$priority] ?? ucwords(str_replace('_', ' ', $priority ?: 'all priorities')),
        $rangeLabels[$selectedRange] ?? ucfirst(str_replace('_', ' ', $selectedRange)),
        $sourceLabels[$selectedSource] ?? ucfirst(str_replace('_', ' ', $selectedSource)),
        $assignedUserLabel,
        $serviceLabels[$selectedServiceType] ?? ucfirst(str_replace('_', ' ', $selectedServiceType)),
        $customerLabels[$selectedCustomerType] ?? ucfirst(str_replace('_', ' ', $selectedCustomerType)),
    ];

    if ($selectedRange === 'custom' && request('from_date') && request('to_date')) {
        $activeSummary[3] = 'Custom: ' . request('from_date') . ' to ' . request('to_date');
    }

    $hasActiveFilters =
        filled($q) ||
        filled($stage) ||
        filled($priority) ||
        filled($bucket) ||
        $selectedRange !== 'all_time' ||
        $selectedSource !== 'all' ||
        $selectedAssignedUser !== 'all' ||
        $selectedServiceType !== 'all' ||
        $selectedCustomerType !== 'all' ||
        request()->filled('from_date') ||
        request()->filled('to_date');
@endphp

<div id="sfOpportunityFilters" class="sf-opportunity-panel rounded-2xl border p-4 shadow-sm">
    <form method="GET" action="{{ route('admin.opportunities.index') }}">

        @if($bucket)
            <input type="hidden" name="bucket" value="{{ $bucket }}">
        @endif

        {{-- Compact collapsed row --}}
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="sf-opportunity-title text-base font-extrabold tracking-tight">
                        Search & Filter Opportunities
                    </h2>

                    @if($hasActiveFilters)
                        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                            Active
                        </span>
                    @endif

                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        @foreach($activeSummary as $summaryItem)
                            <span class="sf-opportunity-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                                {{ $summaryItem }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <button
                type="button"
                id="sfOpportunityFiltersToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
            >
                Show Filters
            </button>
        </div>

        {{-- Expandable body --}}
        <div id="sfOpportunityFiltersBody" class="mt-5 hidden">

            {{-- Search --}}
            <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                <label for="opportunity-search" class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Search
                </label>

                <input
                    id="opportunity-search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search title, client, vehicle, source..."
                    class="sf-opportunity-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                >
            </div>

            {{-- Primary filters --}}
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Stage
                    </label>

                    <select name="stage" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="">All stages</option>
                        @foreach(['new', 'attempting_contact', 'manager_confirmation_pending', 'appointment', 'closed_won', 'closed_lost'] as $stageOption)
                            <option value="{{ $stageOption }}" @selected($stage === $stageOption)>
                                {{ $stageLabel($stageOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Priority
                    </label>

                    <select name="priority" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="">All priorities</option>
                        @foreach(['urgent', 'high', 'medium', 'low'] as $priorityOption)
                            <option value="{{ $priorityOption }}" @selected($priority === $priorityOption)>
                                {{ ucfirst($priorityOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Date Range
                    </label>

                    <select
                        id="opportunityDateRange"
                        name="date_range"
                        class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition"
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

                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Lead Source
                    </label>

                    <select name="lead_source" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedSource === 'all')>All Sources</option>
                        <option value="whatsapp" @selected($selectedSource === 'whatsapp')>WhatsApp</option>
                        <option value="website" @selected($selectedSource === 'website')>Website</option>
                        <option value="meta" @selected($selectedSource === 'meta')>Meta</option>
                        <option value="google" @selected($selectedSource === 'google')>Google</option>
                        <option value="manual" @selected($selectedSource === 'manual')>Manual</option>
                    </select>
                </div>

                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Assigned User
                    </label>

                    <select name="assigned_user" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedAssignedUser === 'all')>All Users</option>

                        @foreach($assignedUsers as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected((string) $selectedAssignedUser === (string) $assignedUser->id)>
                                {{ $assignedUser->name ?? 'User #' . $assignedUser->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Secondary filters --}}
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Service Type
                    </label>

                    <select name="service_type" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
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

                <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                    <label class="sf-opportunity-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Customer Type
                    </label>

                    <select name="customer_type" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
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
                id="opportunityCustomDateFields"
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
                        value="{{ $opportunityFilters['from_date'] ?? request('from_date') }}"
                        class="sf-opportunity-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        To Date
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="{{ $opportunityFilters['to_date'] ?? request('to_date') }}"
                        class="sf-opportunity-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                    >
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-800 pt-4">
                <a href="{{ $clearUrl }}" class="sf-btn-secondary">
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
        var body = document.getElementById('sfOpportunityFiltersBody');
        var toggle = document.getElementById('sfOpportunityFiltersToggle');
        var dateRange = document.getElementById('opportunityDateRange');
        var customFields = document.getElementById('opportunityCustomDateFields');

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