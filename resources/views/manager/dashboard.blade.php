@extends('layouts.manager')

@section('title', 'Manager Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="manager-dashboard-hero d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="sf-page-title">
                Manager Dashboard
            </h1>
            <p class="sf-page-subtitle">
                Track escalations, bookings, jobs, leads, and customer conversations.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('manager.escalations'))
                <a href="{{ route('manager.escalations') }}"
                   class="sf-action-button danger">
                    View Escalations
                </a>
            @endif

            @if(Route::has('manager.bookings.index'))
                <a href="{{ route('manager.bookings.index') }}"
                   class="sf-action-button primary">
                    View Bookings
                </a>
            @endif
        </div>
    </div>


    {{-- Stat Cards --}}
    <div class="row g-4 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.escalations'))
                <a href="{{ route('manager.escalations') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Human Escalations</p>
                    <p class="sf-stat-value text-danger">
                        {{ $stats['human_escalations'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Customers waiting for manager
                    </p>
                </div>
            @if(Route::has('manager.escalations'))
                </a>
            @endif
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.bookings.index'))
                <a href="{{ route('manager.bookings.index') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Pending Bookings</p>
                    <p class="sf-stat-value text-warning">
                        {{ $stats['pending_bookings'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Need confirmation
                    </p>
                </div>
            @if(Route::has('manager.bookings.index'))
                </a>
            @endif
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.bookings.index'))
                <a href="{{ route('manager.bookings.index') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Scheduled Bookings</p>
                    <p class="sf-stat-value text-primary">
                        {{ $stats['scheduled_bookings'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Confirmed appointments
                    </p>
                </div>
            @if(Route::has('manager.bookings.index'))
                </a>
            @endif
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.jobs.index'))
                <a href="{{ route('manager.jobs.index') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Active Jobs</p>
                    <p class="sf-stat-value text-success">
                        {{ ($stats['jobs_pending'] ?? 0) + ($stats['jobs_in_progress'] ?? 0) }}
                    </p>
                    <p class="sf-stat-help">
                        Pending / in progress
                    </p>
                </div>
            @if(Route::has('manager.jobs.index'))
                </a>
            @endif
        </div>

    </div>


    {{-- Secondary Stats --}}
    <div class="row g-4 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.leads.index'))
                <a href="{{ route('manager.leads.index') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Open Leads</p>
                    <p class="sf-stat-value">
                        {{ $stats['open_leads'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Leads needing action
                    </p>
                </div>
            @if(Route::has('manager.leads.index'))
                </a>
            @endif
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.bookings.index'))
                <a href="{{ route('manager.bookings.index') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Converted Bookings</p>
                    <p class="sf-stat-value">
                        {{ $stats['converted_bookings'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Moved into jobs
                    </p>
                </div>
            @if(Route::has('manager.bookings.index'))
                </a>
            @endif
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.jobs.index'))
                <a href="{{ route('manager.jobs.index') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Completed Jobs</p>
                    <p class="sf-stat-value">
                        {{ $stats['jobs_completed'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Finished service work
                    </p>
                </div>
            @if(Route::has('manager.jobs.index'))
                </a>
            @endif
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @if(Route::has('manager.escalations'))
                <a href="{{ route('manager.escalations') }}" class="text-decoration-none">
            @endif
                <div class="sf-stat-card h-100">
                    <p class="sf-stat-label">Unread Messages</p>
                    <p class="sf-stat-value">
                        {{ $stats['unread_messages'] ?? 0 }}
                    </p>
                    <p class="sf-stat-help">
                        Customer replies pending
                    </p>
                </div>
            @if(Route::has('manager.escalations'))
                </a>
            @endif
        </div>

    </div>


    {{-- Main Work Queues --}}
    <div class="row g-4 mb-4">

        {{-- Pending Bookings --}}
        <div class="col-12 col-xl-4">
            <div class="sf-panel h-100 overflow-hidden">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Pending / Scheduled Bookings</h2>
                        <p class="sf-panel-subtitle">Latest booking actions</p>
                    </div>

                    @if(Route::has('manager.bookings.index'))
                        <a href="{{ route('manager.bookings.index') }}"
                           class="fw-bold text-primary small">
                            View all
                        </a>
                    @endif
                </div>

                <div class="divide-y">
                    @forelse($pendingBookings ?? [] as $booking)
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between gap-3">
                                <div class="min-w-0">
                                    <p class="fw-bold text-dark mb-1">
                                        {{ $booking->name ?? $booking->client?->name ?? 'Customer' }}
                                    </p>

                                    <p class="small text-muted mb-0">
                                        {{ $booking->booking_date ?? $booking->scheduled_at ?? 'No date' }}

                                        @if(!empty($booking->slot))
                                            · {{ ucfirst(str_replace('_', ' ', $booking->slot)) }}
                                        @endif
                                    </p>
                                </div>

                                <span class="badge bg-light text-dark border h-fit">
                                    {{ ucfirst(str_replace('_', ' ', $booking->status ?? 'pending')) }}
                                </span>
                            </div>

                            @if(Route::has('manager.bookings.show'))
                                <div class="mt-2">
                                    <a href="{{ route('manager.bookings.show', $booking->id) }}"
                                       class="small fw-bold text-primary">
                                        Open booking
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="sf-panel-body">
                            <p class="text-muted mb-0">
                                No pending bookings.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>


        {{-- Escalated Leads --}}
        <div class="col-12 col-xl-4">
            <div class="sf-panel h-100 overflow-hidden">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Escalated Leads</h2>
                        <p class="sf-panel-subtitle">Customers needing human takeover</p>
                    </div>

                    @if(Route::has('manager.escalations'))
                        <a href="{{ route('manager.escalations') }}"
                           class="fw-bold text-primary small">
                            View all
                        </a>
                    @endif
                </div>

                <div class="divide-y">
                    @forelse($escalatedLeads ?? [] as $lead)
                        <div class="p-3 border-bottom">
                            <p class="fw-bold text-dark mb-1">
                                {{ $lead->name ?? 'Lead #' . $lead->id }}
                            </p>

                            <p class="small text-muted mb-2">
                                {{ $lead->phone_norm ?? $lead->phone ?? 'No phone' }}
                            </p>

                            @if(Route::has('manager.conversation'))
                                <a href="{{ route('manager.conversation', $lead->id) }}"
                                   class="small fw-bold text-primary">
                                    Open conversation
                                </a>
                            @elseif(Route::has('manager.leads.show'))
                                <a href="{{ route('manager.leads.show', $lead->id) }}"
                                   class="small fw-bold text-primary">
                                    Open lead
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="sf-panel-body">
                            <p class="text-muted mb-0">
                                No escalations right now.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>


        {{-- Active Jobs --}}
        <div class="col-12 col-xl-4">
            <div class="sf-panel h-100 overflow-hidden">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Active Jobs</h2>
                        <p class="sf-panel-subtitle">Pending and in-progress jobs</p>
                    </div>

                    @if(Route::has('manager.jobs.index'))
                        <a href="{{ route('manager.jobs.index') }}"
                           class="fw-bold text-primary small">
                            View all
                        </a>
                    @endif
                </div>

                <div class="divide-y">
                    @forelse($activeJobs ?? [] as $job)
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between gap-3">
                                <div class="min-w-0">
                                    <p class="fw-bold text-dark mb-1">
                                        {{ $job->job_code ?? 'Job #' . $job->id }}
                                    </p>

                                    <p class="small text-muted mb-0">
                                        {{ $job->description ?? $job->service_type ?? 'Service job' }}
                                    </p>
                                </div>

                                <span class="badge bg-light text-dark border h-fit">
                                    {{ ucfirst(str_replace('_', ' ', $job->status ?? 'pending')) }}
                                </span>
                            </div>

                            @if(Route::has('manager.jobs.show'))
                                <div class="mt-2">
                                    <a href="{{ route('manager.jobs.show', $job->id) }}"
                                       class="small fw-bold text-primary">
                                        Open job
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="sf-panel-body">
                            <p class="text-muted mb-0">
                                No active jobs.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>


    {{-- Quick Links --}}
    <div class="sf-panel">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">
                    Manager Work Areas
                </h2>
                <p class="sf-panel-subtitle">
                    Quick access to day-to-day manager actions
                </p>
            </div>
        </div>

        <div class="sf-panel-body">
            <div class="row g-3">

                @if(Route::has('manager.escalations'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.escalations') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon">🔥</span>
                            <span class="manager-work-label">Escalations</span>
                        </a>
                    </div>
                @endif

                @if(Route::has('manager.bookings.index'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.bookings.index') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon">📅</span>
                            <span class="manager-work-label">Bookings</span>
                        </a>
                    </div>
                @endif

                @if(Route::has('manager.jobs.index'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.jobs.index') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon">🛠️</span>
                            <span class="manager-work-label">Jobs</span>
                        </a>
                    </div>
                @endif

                @if(Route::has('manager.leads.index'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.leads.index') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon">📈</span>
                            <span class="manager-work-label">Leads</span>
                        </a>
                    </div>
                @endif

                @if(Route::has('manager.opportunities.index'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.opportunities.index') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon">🎯</span>
                            <span class="manager-work-label">Opportunities</span>
                        </a>
                    </div>
                @endif

                @if(Route::has('manager.clients.index'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.clients.index') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon">👤</span>
                            <span class="manager-work-label">Clients</span>
                        </a>
                    </div>
                @endif

                @if(Route::has('manager.growth.index'))
                    <div class="col-6 col-md-4 col-xl">
                        <a href="{{ route('manager.growth.index') }}"
                           class="manager-work-card">
                            <span class="manager-work-icon"></span>
                            <span class="manager-work-label">Reports</span>
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    .h-fit {
        height: fit-content;
    }

    .min-w-0 {
        min-width: 0;
    }

    .manager-dashboard-hero {
        padding: 24px 28px;
        border: 1px solid var(--sf-border-light);
        border-radius: 22px;
        background: var(--sf-surface);
        box-shadow: var(--sf-shadow);
    }

    .manager-work-card {
        min-height: 86px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 10px;
        border: 1px solid var(--sf-border-light);
        border-radius: 14px;
        color: var(--sf-text-strong);
        background: var(--sf-surface);
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .manager-work-card:hover {
        color: var(--sf-text-strong);
        background: var(--sf-surface-soft);
        border-color: rgba(37, 99, 235, 0.35);
        transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .manager-work-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        color: #9a3412;
        background: #ffedd5;
        border: 1px solid #fed7aa;
        font-size: 0;
        font-weight: 950;
        line-height: 1;
    }

    .manager-work-icon::after {
        font-size: 11px;
        letter-spacing: 0.02em;
        content: "SF";
    }

    a[href*="escalations"] .manager-work-icon::after {
        content: "ES";
    }

    a[href*="bookings"] .manager-work-icon::after {
        content: "BK";
    }

    a[href*="jobs"] .manager-work-icon::after {
        content: "JB";
    }

    a[href*="leads"] .manager-work-icon::after {
        content: "LD";
    }

    a[href*="opportunities"] .manager-work-icon::after {
        content: "OP";
    }

    a[href*="clients"] .manager-work-icon::after {
        content: "CL";
    }

    a[href*="growth"] .manager-work-icon::after {
        content: "RP";
    }

    html[data-theme="dark"] .manager-work-icon {
        color: #fed7aa;
        background: rgba(249, 115, 22, 0.14);
        border-color: rgba(249, 115, 22, 0.24);
    }

    .manager-work-label {
        font-size: 13px;
        font-weight: 900;
    }

    @media (max-width: 640px) {
        .manager-dashboard-hero {
            padding: 20px;
        }
    }
</style>
@endpush
