@extends('layouts.manager')

@section('title', 'Manager Leads')

@section('content')
@php
    use App\Models\Client\Lead;
    use App\Services\PhoneNumberService;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $phoneService = app(PhoneNumberService::class);
    $leadStatuses = $leadStatuses ?? Lead::STATUSES;
    $statusLabels = $statusLabels ?? [
        Lead::STATUS_NEW => 'New',
        Lead::STATUS_ATTEMPTING => 'Attempting Contact',
        Lead::STATUS_HOLD => 'Contact On Hold',
        Lead::STATUS_QUALIFIED => 'Qualified',
        Lead::STATUS_DISQUALIFIED => 'Disqualified',
    ];

    $selectedStatus = $status ?? request('status', '');
    $selectedSource = $source ?? request('source', '');
    $selectedAssigned = $assignedUser ?? request('assigned_user', 'all');
    $selectedService = $serviceType ?? request('service_type', 'all');
    $selectedBucket = $bucket ?? request('bucket', '');
    $selectedSearch = $q ?? request('q', '');

    $statusLabel = fn ($value) => $statusLabels[$value] ?? Str::headline((string) $value);

    $leadName = function ($lead) {
        return $lead->name
            ?? $lead->full_name
            ?? $lead->customer_name
            ?? $lead->client_name
            ?? 'Lead #' . $lead->id;
    };

    $leadPhone = function ($lead) {
        return $lead->phone
            ?? $lead->phone_norm
            ?? $lead->mobile
            ?? $lead->phone_number
            ?? $lead->whatsapp_number
            ?? null;
    };

    $leadVehicle = function ($lead) {
        $make = $lead->vehicle_make ?? $lead->other_make ?? null;
        $model = $lead->vehicle_model ?? $lead->other_model ?? null;
        $year = $lead->vehicle_year ?? null;
        $plate = $lead->plate_number ?? null;
        $vehicle = trim(collect([$year, $make, $model])->filter()->implode(' '));

        if ($plate) {
            $vehicle = trim($vehicle . ' / ' . $plate);
        }

        return $vehicle ?: null;
    };

    $assignedValue = function ($lead) {
        return $lead->assigned_to
            ?? $lead->assigned_to_id
            ?? $lead->assigned_user_id
            ?? $lead->manager_id
            ?? $lead->user_id
            ?? null;
    };

    $badgeClass = function ($status) {
        return match ((string) $status) {
            Lead::STATUS_NEW => 'is-blue',
            Lead::STATUS_ATTEMPTING => 'is-yellow',
            Lead::STATUS_HOLD => 'is-orange',
            Lead::STATUS_QUALIFIED => 'is-green',
            Lead::STATUS_DISQUALIFIED => 'is-red',
            default => 'is-muted',
        };
    };

    $priorityClass = function ($value) {
        return match (strtolower((string) $value)) {
            'urgent', 'hot' => 'is-red',
            'high' => 'is-orange',
            'medium', 'warm' => 'is-yellow',
            'low', 'cold' => 'is-muted',
            default => 'is-muted',
        };
    };

    $statCards = [
        ['key' => '', 'title' => 'Open Leads', 'count' => $leadCounts['open'] ?? 0, 'note' => 'Manager-actionable'],
        ['key' => Lead::STATUS_NEW, 'title' => 'New', 'count' => $leadCounts[Lead::STATUS_NEW] ?? 0, 'note' => 'Fresh leads'],
        ['key' => Lead::STATUS_ATTEMPTING, 'title' => 'Attempting Contact', 'count' => $leadCounts[Lead::STATUS_ATTEMPTING] ?? 0, 'note' => 'Reach-out active'],
        ['key' => Lead::STATUS_HOLD, 'title' => 'Contact On Hold', 'count' => $leadCounts[Lead::STATUS_HOLD] ?? 0, 'note' => 'Waiting / callback'],
        ['key' => Lead::STATUS_QUALIFIED, 'title' => 'Qualified', 'count' => $leadCounts[Lead::STATUS_QUALIFIED] ?? 0, 'note' => 'Opportunity ready'],
        ['key' => Lead::STATUS_DISQUALIFIED, 'title' => 'Disqualified', 'count' => $leadCounts[Lead::STATUS_DISQUALIFIED] ?? 0, 'note' => 'Closed lead'],
    ];

    $bucketCards = [
        ['key' => Lead::STATUS_NEW, 'title' => 'New Leads', 'count' => $bucketCounts[Lead::STATUS_NEW] ?? 0, 'note' => 'Needs first action'],
        ['key' => Lead::STATUS_ATTEMPTING, 'title' => 'Contact Attempts', 'count' => $bucketCounts[Lead::STATUS_ATTEMPTING] ?? 0, 'note' => 'In progress'],
        ['key' => Lead::STATUS_HOLD, 'title' => 'On Hold', 'count' => $bucketCounts[Lead::STATUS_HOLD] ?? 0, 'note' => 'Needs follow-up context'],
        ['key' => 'followup_due', 'title' => 'Follow-up Due', 'count' => $bucketCounts['followup_due'] ?? 0, 'note' => 'Due or overdue'],
        ['key' => Lead::STATUS_QUALIFIED, 'title' => 'Qualified', 'count' => $bucketCounts[Lead::STATUS_QUALIFIED] ?? 0, 'note' => 'Opportunity created'],
        ['key' => Lead::STATUS_DISQUALIFIED, 'title' => 'Disqualified', 'count' => $bucketCounts[Lead::STATUS_DISQUALIFIED] ?? 0, 'note' => 'Reason captured'],
    ];

    $activeFilterLabels = [
        $selectedSearch ? 'Search: ' . $selectedSearch : 'No Search',
        $selectedStatus ? $statusLabel($selectedStatus) : 'All Statuses',
        $selectedSource ?: 'All Sources',
        $selectedAssigned !== 'all' ? 'Assigned user selected' : 'All Users',
        $selectedService !== 'all' ? Str::headline($selectedService) : 'All Services',
    ];
@endphp

<div class="manager-leads-page">
    <div class="manager-index-sticky">
        <section class="manager-index-hero">
            <div>
                <p class="manager-eyebrow">Manager Action Queue</p>
                <h1 class="sf-page-title mb-2">Leads</h1>
                <p class="sf-page-subtitle mb-0">
                    Manage open leads, follow-ups, assignments, and qualification from one compact queue.
                </p>
            </div>

            <div class="manager-hero-actions">
                @if(Route::has('manager.dashboard'))
                    <a href="{{ route('manager.dashboard') }}" class="sf-action-button light">
                        Back to Dashboard
                    </a>
                @endif
            </div>
        </section>

        @if(session('success'))
            <div class="alert alert-success manager-alert">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger manager-alert">
                <strong>Please check the lead action.</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <details class="manager-toggle-panel">
            <summary>
                <span>
                    <strong>Search & Filter Leads</strong>
                    <span class="manager-filter-summary">
                        @foreach($activeFilterLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </span>
                </span>
                <span class="manager-toggle-button">Show Filters</span>
            </summary>

            <form method="GET" action="{{ route('manager.leads.index') }}" class="manager-filter-grid">
                @if($selectedBucket)
                    <input type="hidden" name="bucket" value="{{ $selectedBucket }}">
                @endif

                <label>
                    <span>Search</span>
                    <input type="text" name="q" value="{{ $selectedSearch }}" placeholder="Name, phone, source, vehicle, notes">
                </label>

                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="">All Statuses</option>
                        @foreach($leadStatuses as $item)
                            <option value="{{ $item }}" @selected($selectedStatus === $item)>
                                {{ $statusLabel($item) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Source</span>
                    <select name="source">
                        <option value="">All Sources</option>
                        @foreach(($sources ?? collect()) as $item)
                            <option value="{{ $item }}" @selected($selectedSource === $item)>
                                {{ Str::headline($item) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Assigned User</span>
                    <select name="assigned_user">
                        <option value="all" @selected($selectedAssigned === 'all')>All Users</option>
                        @foreach(($managers ?? collect()) as $manager)
                            <option value="{{ $manager->id }}" @selected((string) $selectedAssigned === (string) $manager->id)>
                                {{ $manager->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Service Type</span>
                    <select name="service_type">
                        <option value="all" @selected($selectedService === 'all')>All Services</option>
                        @foreach(($serviceTypes ?? collect()) as $item)
                            <option value="{{ $item }}" @selected($selectedService === $item)>
                                {{ Str::headline($item) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="manager-filter-actions">
                    <button type="submit" class="sf-action-button orange">Search</button>
                    <a href="{{ route('manager.leads.index') }}" class="sf-action-button light">Reset</a>
                </div>
            </form>
        </details>

        <details class="manager-toggle-panel">
            <summary>
                <span>
                    <strong>Lead Buckets</strong>
                    <span class="manager-filter-summary">
                        <span>Buckets: {{ collect($bucketCards)->sum('count') }}</span>
                        <span>{{ $selectedBucket ? 'Selected: ' . Str::headline($selectedBucket) : 'No bucket selected' }}</span>
                    </span>
                </span>
                <span class="manager-toggle-button">Show Buckets</span>
            </summary>

            <div class="manager-bucket-grid">
                @foreach($bucketCards as $card)
                    <a href="{{ route('manager.leads.index', array_filter([
                        'bucket' => $card['key'],
                        'q' => $selectedSearch,
                        'source' => $selectedSource,
                        'assigned_user' => $selectedAssigned !== 'all' ? $selectedAssigned : null,
                        'service_type' => $selectedService !== 'all' ? $selectedService : null,
                    ], fn ($value) => filled($value))) }}"
                       class="manager-bucket-card {{ $selectedBucket === $card['key'] ? 'is-active' : '' }}">
                        <span>{{ $card['title'] }}</span>
                        <strong>{{ number_format($card['count']) }}</strong>
                        <em>{{ $card['note'] }}</em>
                    </a>
                @endforeach
            </div>
        </details>

        <div class="manager-stat-grid">
            @foreach($statCards as $card)
                <a href="{{ $card['key'] ? route('manager.leads.index', ['status' => $card['key']]) : route('manager.leads.index') }}"
                   class="manager-stat-card {{ $selectedStatus === $card['key'] || (! $selectedStatus && $card['key'] === '') ? 'is-active' : '' }}">
                    <span>{{ $card['title'] }}</span>
                    <strong>{{ number_format($card['count']) }}</strong>
                    <em>{{ $card['note'] }}</em>
                </a>
            @endforeach
        </div>
    </div>

    <section class="manager-table-panel">
        <div class="manager-table-heading">
            <div>
                <h2>Lead Queue</h2>
                <p>
                    {{ method_exists($leads, 'total') ? number_format($leads->total()) : number_format($leads->count()) }}
                    lead(s) found
                </p>
            </div>
        </div>

        @if($leads->count())
            <table class="manager-leads-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Request</th>
                        <th>Vehicle</th>
                        <th>Score / Priority</th>
                        <th>Follow-up</th>
                        <th>WhatsApp / Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                        @php
                            $phoneRaw = $leadPhone($lead);
                            $phoneDisplay = $phoneRaw ? $phoneService->formatForDisplay($phoneRaw) : null;
                            $telUrl = $phoneRaw ? $phoneService->buildTelUrl($phoneRaw) : null;
                            $whatsappKey = $phoneRaw ? $phoneService->buildWhatsappLookupKey($phoneRaw) : null;
                            $inboxUrl = $whatsappKey && Route::has('manager.inbox.index')
                                ? route('manager.inbox.index', array_filter(['search' => $whatsappKey]))
                                : null;
                            $currentStatus = in_array((string) $lead->status, $leadStatuses, true)
                                ? (string) $lead->status
                                : Lead::normalizeStatus((string) $lead->status);
                            $vehicleText = $leadVehicle($lead);
                            $requestText = $lead->service_type ?? $lead->service_category ?? 'Not set';
                            $score = $lead->score ?? null;
                            $priority = $lead->lead_priority ?? null;
                            $temperature = $lead->lead_temperature ?? null;
                            $followUpDate = $lead->follow_up_date ?? null;
                            $followUpAt = $lead->follow_up_at ?? null;
                            $notes = $lead->manager_notes ?? $lead->internal_notes ?? $lead->notes ?? null;
                        @endphp

                        <tr>
                            <td data-label="Lead">
                                <div class="lead-primary">{{ $leadName($lead) }}</div>
                                <div class="lead-subline">
                                    @if($telUrl)
                                        <a href="{{ $telUrl }}" class="manager-phone-link">{{ $phoneDisplay }}</a>
                                    @elseif($phoneDisplay)
                                        <span>{{ $phoneDisplay }}</span>
                                    @else
                                        <span>No phone</span>
                                    @endif
                                </div>
                                <div class="lead-muted">Lead ID: #{{ $lead->id }}</div>
                            </td>

                            <td data-label="Request">
                                <span class="manager-badge is-muted">{{ Str::headline($requestText) }}</span>
                                <div class="lead-muted mt-2">{{ $lead->source ? Str::headline($lead->source) : 'No source' }}</div>
                                @if($lead->campaign_name)
                                    <div class="lead-muted">{{ Str::limit($lead->campaign_name, 42) }}</div>
                                @endif
                                @if($notes)
                                    <div class="lead-note" title="{{ $notes }}">{{ Str::limit($notes, 76) }}</div>
                                @endif
                            </td>

                            <td data-label="Vehicle">
                                <div class="lead-value">{{ $vehicleText ?: 'No vehicle' }}</div>
                                @if($lead->retention_tag)
                                    <div class="lead-muted">{{ Str::headline($lead->retention_tag) }}</div>
                                @endif
                            </td>

                            <td data-label="Score / Priority">
                                @if($score !== null)
                                    <span class="manager-badge is-blue">{{ $score }}/100</span>
                                @endif
                                @if($temperature)
                                    <span class="manager-badge {{ $priorityClass($temperature) }}">{{ Str::headline($temperature) }}</span>
                                @endif
                                @if($priority)
                                    <span class="manager-badge {{ $priorityClass($priority) }}">{{ Str::headline($priority) }}</span>
                                @endif
                                @if($score === null && ! $temperature && ! $priority)
                                    <span class="lead-muted">Not set</span>
                                @endif
                            </td>

                            <td data-label="Follow-up">
                                @if($followUpAt)
                                    <div class="lead-value">{{ \Carbon\Carbon::parse($followUpAt)->format('d M Y, h:i A') }}</div>
                                @elseif($followUpDate)
                                    <div class="lead-value">{{ \Carbon\Carbon::parse($followUpDate)->format('d M Y') }}</div>
                                @else
                                    <span class="lead-muted">Not set</span>
                                @endif
                                @if((bool) ($lead->follow_up_required ?? false))
                                    <div class="lead-muted">Follow-up required</div>
                                @endif
                            </td>

                            <td data-label="WhatsApp / Status">
                                @if($inboxUrl)
                                    <a href="{{ $inboxUrl }}" class="manager-badge is-green">WhatsApp Inbox</a>
                                @else
                                    <span class="manager-badge is-muted">No WhatsApp</span>
                                @endif
                                <div class="mt-2">
                                    <span class="manager-badge {{ $badgeClass($currentStatus) }}">
                                        {{ $statusLabel($currentStatus) }}
                                    </span>
                                </div>
                                @if($lead->status_sub_status)
                                    <div class="lead-muted mt-1">{{ Str::headline($lead->status_sub_status) }}</div>
                                @endif
                            </td>

                            <td data-label="Actions">
                                <details class="lead-manage-panel">
                                    <summary>Manage</summary>

                                    <div class="lead-action-grid">
                                        @if(Route::has('manager.leads.status'))
                                            <form method="POST" action="{{ route('manager.leads.status', $lead) }}">
                                                @csrf
                                                @method('PATCH')

                                                <strong>Status</strong>
                                                <select name="status" required>
                                                    @foreach($leadStatuses as $item)
                                                        <option value="{{ $item }}" @selected($currentStatus === $item)>
                                                            {{ $statusLabel($item) }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <select name="status_sub_status">
                                                    <option value="">Sub-status when required</option>
                                                    <optgroup label="Contact On Hold">
                                                        @foreach(($contactOnHoldSubStatuses ?? []) as $value => $label)
                                                            <option value="{{ $value }}" @selected($lead->status_sub_status === $value)>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Disqualified">
                                                        @foreach(($disqualifiedSubStatuses ?? []) as $value => $label)
                                                            <option value="{{ $value }}" @selected($lead->status_sub_status === $value)>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                </select>

                                                <input type="datetime-local"
                                                       name="follow_up_at"
                                                       value="{{ $lead->follow_up_at ? \Carbon\Carbon::parse($lead->follow_up_at)->format('Y-m-d\\TH:i') : '' }}">

                                                <textarea name="status_reason" rows="2" placeholder="Reason / note when required">{{ $lead->status_reason }}</textarea>
                                                <button type="submit" class="sf-action-button orange">Update Status</button>
                                            </form>
                                        @endif

                                        @if(Route::has('manager.leads.follow-up'))
                                            <form method="POST" action="{{ route('manager.leads.follow-up', $lead) }}">
                                                @csrf
                                                @method('PATCH')

                                                <strong>Follow-up</strong>
                                                <input type="date"
                                                       name="follow_up_date"
                                                       value="{{ $lead->follow_up_date ? \Carbon\Carbon::parse($lead->follow_up_date)->format('Y-m-d') : '' }}">
                                                <label class="inline-check">
                                                    <input type="checkbox" name="follow_up_required" value="1" @checked((bool) ($lead->follow_up_required ?? false))>
                                                    Required
                                                </label>
                                                <textarea name="notes" rows="2" placeholder="Add manager note"></textarea>
                                                <button type="submit" class="sf-action-button light">Save Follow-up</button>
                                            </form>
                                        @endif

                                        @if(Route::has('manager.leads.assign') && ($managers ?? collect())->count())
                                            <form method="POST" action="{{ route('manager.leads.assign', $lead) }}">
                                                @csrf
                                                @method('PATCH')

                                                <strong>Assignment</strong>
                                                <select name="assigned_to" required>
                                                    <option value="">Select user</option>
                                                    @foreach($managers as $manager)
                                                        <option value="{{ $manager->id }}" @selected((string) $assignedValue($lead) === (string) $manager->id)>
                                                            {{ $manager->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="sf-action-button light">Assign</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="manager-empty-state">
                <h3>No leads found</h3>
                <p>Manager-actionable leads will appear here when they match the selected filters.</p>
            </div>
        @endif

        @if(method_exists($leads, 'links'))
            <div class="manager-pagination">
                {{ $leads->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@push('styles')
<style>
    .manager-leads-page {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .manager-index-sticky {
        position: sticky;
        top: 76px;
        z-index: 25;
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding-bottom: 4px;
        background: linear-gradient(180deg, var(--sf-bg) 86%, rgba(0, 0, 0, 0));
    }

    .manager-index-hero,
    .manager-toggle-panel,
    .manager-table-panel {
        border: 1px solid var(--sf-border-light);
        border-radius: 22px;
        background: var(--sf-surface);
        box-shadow: var(--sf-soft-shadow);
    }

    .manager-index-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        padding: 28px;
    }

    .manager-eyebrow {
        display: inline-flex;
        width: max-content;
        margin: 0 0 12px;
        border-radius: 999px;
        padding: 7px 12px;
        background: var(--sf-orange-soft);
        color: var(--sf-orange);
        border: 1px solid rgba(249, 115, 22, 0.20);
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .manager-hero-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .manager-alert {
        margin: 0;
        border-radius: 16px;
        font-weight: 750;
    }

    .manager-toggle-panel {
        padding: 18px 20px;
    }

    .manager-toggle-panel summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        cursor: pointer;
        list-style: none;
        color: var(--sf-text-strong);
    }

    .manager-toggle-panel summary::-webkit-details-marker {
        display: none;
    }

    .manager-filter-summary {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-left: 12px;
        vertical-align: middle;
    }

    .manager-filter-summary span,
    .manager-toggle-button {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        border-radius: 999px;
        border: 1px solid var(--sf-border-light);
        padding: 5px 10px;
        background: var(--sf-surface-soft);
        color: var(--sf-muted-strong);
        font-size: 12px;
        font-weight: 850;
    }

    .manager-toggle-button {
        min-height: 40px;
        padding: 8px 14px;
        color: var(--sf-text-strong);
        background: var(--sf-surface);
    }

    .manager-toggle-panel[open] .manager-toggle-button {
        color: #431407;
        background: #fed7aa;
        border-color: #fdba74;
    }

    .manager-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .manager-filter-grid label,
    .lead-action-grid form {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .manager-filter-grid label > span,
    .lead-action-grid strong {
        color: var(--sf-muted-strong);
        font-size: 11px;
        font-weight: 950;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .manager-filter-grid input,
    .manager-filter-grid select,
    .lead-action-grid input,
    .lead-action-grid select,
    .lead-action-grid textarea {
        width: 100%;
        min-height: 42px;
        border: 1px solid var(--sf-border-light);
        border-radius: 12px;
        padding: 9px 11px;
        background: var(--sf-input-bg);
        color: var(--sf-input-text);
        font-size: 13px;
        font-weight: 750;
    }

    .lead-action-grid textarea {
        min-height: 64px;
        resize: vertical;
    }

    .manager-filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .manager-bucket-grid,
    .manager-stat-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .manager-stat-grid {
        margin-top: 0;
    }

    .manager-bucket-card,
    .manager-stat-card {
        display: flex;
        min-height: 132px;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid var(--sf-border-light);
        border-radius: 20px;
        padding: 18px;
        background: var(--sf-surface);
        color: var(--sf-text);
        text-decoration: none;
        box-shadow: var(--sf-soft-shadow);
        transition: border-color 0.18s ease, transform 0.18s ease, background 0.18s ease;
    }

    .manager-bucket-card:hover,
    .manager-stat-card:hover,
    .manager-bucket-card.is-active,
    .manager-stat-card.is-active {
        transform: translateY(-1px);
        border-color: rgba(249, 115, 22, 0.42);
        background: var(--sf-orange-soft);
    }

    .manager-bucket-card span,
    .manager-stat-card span {
        color: var(--sf-muted-strong);
        font-size: 13px;
        font-weight: 900;
    }

    .manager-bucket-card strong,
    .manager-stat-card strong {
        color: var(--sf-text-strong);
        font-size: 32px;
        font-weight: 950;
        letter-spacing: -0.04em;
    }

    .manager-bucket-card em,
    .manager-stat-card em {
        color: var(--sf-muted);
        font-size: 12px;
        font-style: normal;
        font-weight: 750;
    }

    .manager-table-panel {
        overflow: hidden;
    }

    .manager-table-heading {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--sf-border-light);
        background: var(--sf-surface);
    }

    .manager-table-heading h2 {
        margin: 0;
        color: var(--sf-text-strong);
        font-size: 18px;
        font-weight: 950;
    }

    .manager-table-heading p {
        margin: 4px 0 0;
        color: var(--sf-muted);
        font-size: 13px;
        font-weight: 750;
    }

    .manager-leads-table {
        width: 100%;
        border-collapse: collapse;
    }

    .manager-leads-table th {
        padding: 14px 18px;
        background: var(--sf-surface-soft);
        color: var(--sf-muted-strong);
        font-size: 11px;
        font-weight: 950;
        letter-spacing: 0.05em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .manager-leads-table td {
        padding: 18px;
        border-top: 1px solid var(--sf-border-light);
        color: var(--sf-text);
        vertical-align: top;
    }

    .manager-leads-table tbody tr:hover td {
        background: var(--sf-row-hover);
    }

    .lead-primary,
    .lead-value {
        color: var(--sf-text-strong);
        font-weight: 950;
    }

    .lead-primary {
        font-size: 14px;
    }

    .lead-subline,
    .lead-muted,
    .lead-note {
        color: var(--sf-muted);
        font-size: 12px;
        font-weight: 750;
    }

    .lead-subline {
        margin-top: 5px;
    }

    .lead-note {
        max-width: 260px;
        margin-top: 10px;
        color: var(--sf-muted-strong);
    }

    .manager-phone-link {
        color: var(--sf-orange);
        font-weight: 950;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .manager-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin: 0 4px 4px 0;
        border-radius: 999px;
        border: 1px solid transparent;
        padding: 7px 10px;
        font-size: 11px;
        font-weight: 950;
        line-height: 1;
        white-space: nowrap;
    }

    .manager-badge.is-blue { color: #1d4ed8; background: #dbeafe; border-color: #93c5fd; }
    .manager-badge.is-yellow { color: #854d0e; background: #fef3c7; border-color: #facc15; }
    .manager-badge.is-orange { color: #9a3412; background: #ffedd5; border-color: #fdba74; }
    .manager-badge.is-green { color: #166534; background: #dcfce7; border-color: #86efac; }
    .manager-badge.is-red { color: #991b1b; background: #fee2e2; border-color: #fca5a5; }
    .manager-badge.is-muted { color: var(--sf-muted-strong); background: var(--sf-surface-soft); border-color: var(--sf-border-light); }

    .lead-manage-panel {
        min-width: 250px;
    }

    .lead-manage-panel summary {
        display: inline-flex;
        align-items: center;
        min-height: 38px;
        cursor: pointer;
        list-style: none;
        border: 1px solid rgba(249, 115, 22, 0.30);
        border-radius: 12px;
        padding: 8px 14px;
        background: var(--sf-orange-soft);
        color: var(--sf-orange);
        font-size: 12px;
        font-weight: 950;
    }

    .lead-manage-panel summary::-webkit-details-marker {
        display: none;
    }

    .lead-action-grid {
        display: grid;
        min-width: min(720px, calc(100vw - 72px));
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-top: 14px;
        border: 1px solid var(--sf-border-light);
        border-radius: 18px;
        padding: 16px;
        background: var(--sf-surface-soft);
        box-shadow: var(--sf-soft-shadow);
    }

    .inline-check {
        display: inline-flex !important;
        flex-direction: row !important;
        align-items: center;
        gap: 8px !important;
        color: var(--sf-text);
        font-size: 13px;
        font-weight: 800;
    }

    .inline-check input {
        width: auto;
        min-height: auto;
    }

    .manager-empty-state {
        padding: 64px 20px;
        text-align: center;
    }

    .manager-empty-state h3 {
        margin: 0;
        color: var(--sf-text-strong);
        font-size: 20px;
        font-weight: 950;
    }

    .manager-empty-state p {
        margin: 8px 0 0;
        color: var(--sf-muted);
        font-weight: 750;
    }

    .manager-pagination {
        padding: 18px 24px;
        border-top: 1px solid var(--sf-border-light);
    }

    @media (max-width: 1280px) {
        .manager-bucket-grid,
        .manager-stat-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .manager-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 980px) {
        .manager-index-sticky {
            position: static;
        }

        .manager-index-hero,
        .manager-toggle-panel summary {
            flex-direction: column;
            align-items: flex-start;
        }

        .manager-hero-actions {
            justify-content: flex-start;
        }

        .manager-filter-summary {
            margin: 10px 0 0;
        }

        .manager-leads-table,
        .manager-leads-table thead,
        .manager-leads-table tbody,
        .manager-leads-table tr,
        .manager-leads-table td {
            display: block;
            width: 100%;
        }

        .manager-leads-table thead {
            display: none;
        }

        .manager-leads-table tr {
            border-top: 1px solid var(--sf-border-light);
            padding: 14px;
        }

        .manager-leads-table td {
            display: grid;
            grid-template-columns: 132px minmax(0, 1fr);
            gap: 12px;
            border-top: 0;
            padding: 10px 4px;
        }

        .manager-leads-table td::before {
            content: attr(data-label);
            color: var(--sf-muted-strong);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .lead-action-grid {
            min-width: 0;
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .manager-index-hero,
        .manager-toggle-panel,
        .manager-table-heading {
            padding: 18px;
        }

        .manager-filter-grid,
        .manager-bucket-grid,
        .manager-stat-grid {
            grid-template-columns: 1fr;
        }

        .manager-leads-table td {
            grid-template-columns: 1fr;
        }

        .manager-filter-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .manager-filter-actions .sf-action-button {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush
