{{-- resources/views/admin/jobs/completed-partials/_filters.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $bucket = $bucket ?? request('bucket', '');

    $jobFilters = $jobFilters ?? [];

    $selectedRange = $jobFilters['date_range'] ?? request('date_range', 'all_time');
    $selectedSource = $jobFilters['lead_source'] ?? request('lead_source', 'all');
    $selectedAssignedUser = $jobFilters['assigned_user'] ?? request('assigned_user', 'all');
    $selectedServiceType = $jobFilters['service_type'] ?? request('service_type', 'all');
    $selectedCustomerType = $jobFilters['customer_type'] ?? request('customer_type', 'all');

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
        'oil' => 'Oil Service',
        'battery' => 'Battery Service',
        'tyres' => 'Tyre Service',
        'ac' => 'AC Service',
        'brakes' => 'Brake Service',
        'wash' => 'Car Wash / Detailing',
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

<div
    id="sfCompletedJobFilters"
    class="sf-jobs-panel rounded-2xl border p-4 shadow-sm"
    data-index-filter-panel
    data-date-range-control="#completedJobDateRange"
    data-custom-fields="#completedJobCustomDateFields"
>
    <form method="GET" action="{{ route('admin.jobs.completed') }}">
        @if($bucket)
            <input type="hidden" name="bucket" value="{{ $bucket }}">
        @endif

        <div class="flex cursor-pointer flex-col gap-3 xl:flex-row xl:items-center xl:justify-between" data-index-filter-summary>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="sf-job-title text-base font-extrabold tracking-tight">
                        Search & Filter Completed Jobs
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
                                    1 => '#completedJobDateRange',
                                    2 => '[name="lead_source"]',
                                    3 => '[name="assigned_user"]',
                                    4 => '[name="service_type"]',
                                    5 => '[name="customer_type"]',
                                ][$summaryIndex] ?? null;
                            @endphp

                            <button
                                type="button"
                                class="sf-job-filter-pill inline-flex cursor-pointer rounded-full border px-3 py-1 text-xs font-bold transition focus:outline-none focus:ring-2 focus:ring-orange-400/40"
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
                id="sfCompletedJobFiltersToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
                data-index-filter-toggle
            >
                Show Filters
            </button>
        </div>

        <div id="sfCompletedJobFiltersBody" class="mt-5 hidden" data-index-filter-body>
            <div class="sf-job-soft-panel rounded-2xl border p-4">
                <label class="sf-job-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Search
                </label>

                <input
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search job code, client, phone, service..."
                    class="sf-job-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition"
                >
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="sf-job-soft-panel rounded-2xl border p-4">
                    <label class="sf-job-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Date Range
                    </label>
                    <select id="completedJobDateRange" name="date_range" class="sf-job-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        @foreach($rangeLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedRange === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-job-soft-panel rounded-2xl border p-4">
                    <label class="sf-job-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Lead Source
                    </label>
                    <select name="lead_source" class="sf-job-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        @foreach($sourceLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedSource === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-job-soft-panel rounded-2xl border p-4">
                    <label class="sf-job-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Assigned User
                    </label>
                    <select name="assigned_user" class="sf-job-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        <option value="all" @selected($selectedAssignedUser === 'all')>All Users</option>
                        @foreach($assignedUsers as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected((string) $selectedAssignedUser === (string) $assignedUser->id)>
                                {{ $assignedUser->name ?? 'User #' . $assignedUser->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sf-job-soft-panel rounded-2xl border p-4">
                    <label class="sf-job-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Service Type
                    </label>
                    <select name="service_type" class="sf-job-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        @foreach($serviceLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedServiceType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="sf-job-soft-panel rounded-2xl border p-4">
                    <label class="sf-job-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                        Customer Type
                    </label>
                    <select name="customer_type" class="sf-job-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                        @foreach($customerLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCustomerType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div
                id="completedJobCustomDateFields"
                class="mt-4 grid grid-cols-1 gap-4 rounded-2xl border border-orange-500/20 bg-orange-500/10 p-4 md:grid-cols-2 lg:max-w-xl"
                style="{{ $selectedRange === 'custom' ? '' : 'display: none;' }}"
            >
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        From Date
                    </label>
                    <input type="date" name="from_date" value="{{ $jobFilters['from_date'] ?? request('from_date') }}" class="sf-job-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition">
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        To Date
                    </label>
                    <input type="date" name="to_date" value="{{ $jobFilters['to_date'] ?? request('to_date') }}" class="sf-job-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition">
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-800 pt-4">
                <a href="{{ route('admin.jobs.completed') }}" class="sf-btn-secondary">
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
