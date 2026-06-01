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
@endphp

<div class="sf-leads-panel rounded-2xl border p-5 shadow-sm">
    <form method="GET" action="{{ $filterRoute }}" class="space-y-5">
        <div>
            <h2 class="sf-leads-title text-base font-extrabold tracking-tight">Search & Filter Leads</h2>
            <p class="sf-leads-muted mt-1 text-xs font-medium">
                Dashboard filters are carried into this page when present.
            </p>
        </div>

        @if($pageMode === 'open' && ! blank($bucket))
            <input type="hidden" name="bucket" value="{{ $bucket }}">
        @endif

        <div>
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

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">Date Range</label>
                <select name="date_range" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                    <option value="all_time" @selected($selectedRange === 'all_time')>All Time</option>
                    <option value="today" @selected($selectedRange === 'today')>Today</option>
                    <option value="yesterday" @selected($selectedRange === 'yesterday')>Yesterday</option>
                    <option value="last_7_days" @selected($selectedRange === 'last_7_days')>Last 7 days</option>
                    <option value="this_month" @selected($selectedRange === 'this_month')>This month</option>
                    <option value="last_month" @selected($selectedRange === 'last_month')>Last month</option>
                    <option value="custom" @selected($selectedRange === 'custom')>Custom range</option>
                </select>
            </div>

            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">Lead Source</label>
                <select name="lead_source" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                    <option value="all" @selected($selectedSource === 'all')>All Sources</option>
                    <option value="whatsapp" @selected($selectedSource === 'whatsapp')>WhatsApp</option>
                    <option value="website" @selected($selectedSource === 'website')>Website</option>
                    <option value="meta" @selected($selectedSource === 'meta')>Meta</option>
                    <option value="google" @selected($selectedSource === 'google')>Google</option>
                    <option value="manual" @selected($selectedSource === 'manual')>Manual</option>
                </select>
            </div>

            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">Assigned User</label>
                <select name="assigned_user" class="sf-leads-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                    <option value="all" @selected($selectedAssignedUser === 'all')>All Users</option>
                    @foreach($assignedUsers as $assignedUser)
                        <option value="{{ $assignedUser->id }}" @selected((string) $selectedAssignedUser === (string) $assignedUser->id)>
                            {{ $assignedUser->name ?? 'User #' . $assignedUser->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">Service Type</label>
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

            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">Customer Type</label>
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

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">From Date</label>
                <input type="date" name="from_date" value="{{ $leadFilters['from_date'] ?? request('from_date') }}" class="sf-leads-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition">
            </div>

            <div>
                <label class="sf-leads-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">To Date</label>
                <input type="date" name="to_date" value="{{ $leadFilters['to_date'] ?? request('to_date') }}" class="sf-leads-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition">
            </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-slate-800 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="sf-leads-muted text-xs font-semibold">
                Filters preserve search, bucket, and pagination query strings.
            </p>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $filterRoute }}" class="sf-btn-secondary">Reset</a>
                <button type="submit" class="sf-btn-primary">Apply Filters</button>
            </div>
        </div>
    </form>
</div>
