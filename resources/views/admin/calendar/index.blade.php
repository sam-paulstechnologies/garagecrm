@extends('layouts.app')

@section('title', 'Garage Calendar')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Garage Schedule
            </div>

            <h1 class="sf-page-title mt-3">
                Garage Calendar
            </h1>

            <p class="sf-page-subtitle">
                View bookings, jobs, and scheduled garage activity in calendar format.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.bookings.index'))
                <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
                    Bookings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.jobs.index'))
                <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                    Jobs
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.bookings.create'))
                <a href="{{ route('admin.bookings.create') }}" class="sf-btn-primary">
                    + New Booking
                </a>
            @endif
        </div>
    </div>

    {{-- Info Note --}}
    <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-blue-300">
            Calendar view
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            This calendar loads events from the existing calendar events route. Use it to quickly review garage workload, upcoming bookings, and scheduled activity.
        </p>
    </div>

    {{-- Calendar Card --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Schedule
                </h2>

                <p class="sf-section-subtitle">
                    Click an event to open the linked booking, job, or related record if available.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                <span class="sf-badge-blue">Bookings</span>
                <span class="sf-badge-green">Jobs</span>
                <span class="sf-badge-orange">Priority</span>
            </div>
        </div>

        <div class="sf-card-body">
            <div
                id="calendar"
                data-events="{{ route('admin.calendar.events') }}"
                class="garage-calendar rounded-3xl border border-white/10 bg-slate-950/60 p-3"
            ></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .garage-calendar {
        min-height: 720px;
    }

    .fc {
        color: #e2e8f0;
    }

    .fc .fc-toolbar {
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 1.25rem;
    }

    .fc .fc-toolbar-title {
        color: #ffffff;
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -0.025em;
    }

    .fc .fc-button {
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        background: rgba(15, 23, 42, 0.9) !important;
        color: #e2e8f0 !important;
        border-radius: 0.75rem !important;
        padding: 0.45rem 0.75rem !important;
        font-size: 0.8rem !important;
        font-weight: 800 !important;
        box-shadow: none !important;
        text-transform: capitalize !important;
    }

    .fc .fc-button:hover,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: rgba(37, 99, 235, 0.9) !important;
        border-color: rgba(96, 165, 250, 0.5) !important;
        color: #ffffff !important;
    }

    .fc-theme-standard td,
    .fc-theme-standard th,
    .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .fc-col-header-cell {
        background: rgba(15, 23, 42, 0.95);
    }

    .fc-col-header-cell-cushion {
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 900;
        padding: 0.75rem 0;
        text-transform: uppercase;
    }

    .fc-daygrid-day-number {
        color: #cbd5e1;
        font-size: 0.8rem;
        font-weight: 800;
        padding: 0.5rem;
    }

    .fc-day-today {
        background: rgba(249, 115, 22, 0.08) !important;
    }

    .fc-daygrid-day {
        background: rgba(2, 6, 23, 0.18);
    }

    .fc-daygrid-day:hover {
        background: rgba(37, 99, 235, 0.08);
    }

    .fc-event {
        border: 0 !important;
        border-radius: 0.75rem !important;
        padding: 3px 6px !important;
        font-size: 0.75rem !important;
        font-weight: 800 !important;
        cursor: pointer;
    }

    .fc-daygrid-event {
        margin: 2px 4px;
    }

    .fc-event-title,
    .fc-event-time {
        color: #ffffff;
    }

    .fc-list {
        border-color: rgba(255, 255, 255, 0.08);
    }

    .fc-list-day-cushion {
        background: rgba(15, 23, 42, 0.95) !important;
        color: #ffffff !important;
    }

    .fc-list-event:hover td {
        background: rgba(37, 99, 235, 0.08) !important;
    }

    .fc-list-event-title,
    .fc-list-event-time {
        color: #e2e8f0;
    }

    .fc-scroller {
        scrollbar-width: thin;
        scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
    }

    @media (max-width: 640px) {
        .garage-calendar {
            min-height: 560px;
        }

        .fc .fc-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .fc .fc-toolbar-title {
            font-size: 1rem;
        }

        .fc .fc-button {
            padding: 0.35rem 0.55rem !important;
            font-size: 0.72rem !important;
        }
    }
</style>
@endpush