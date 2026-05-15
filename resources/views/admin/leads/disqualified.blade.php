@extends('layouts.app')

@section('title', 'Disqualified Leads')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Archive
            </div>

            <h1 class="sf-page-title mt-3">
                Disqualified / Closed Lost Leads
            </h1>

            <p class="sf-page-subtitle">
                Review leads that were marked as disqualified, closed lost, invalid, or no longer active.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                ← Back to Leads
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Disqualified Leads
            </div>

            <div class="sf-stat-value text-red-300">
                {{ method_exists($leads, 'total') ? $leads->total() : $leads->count() }}
            </div>

            <div class="sf-stat-note">
                Leads removed from active follow-up
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Review Purpose
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Learning Loop
            </div>

            <div class="sf-stat-note">
                Understand why leads were lost
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Possible Action
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Reopen if needed
            </div>

            <div class="sf-stat-note">
                Open lead profile to review history
            </div>
        </div>
    </div>

    {{-- Leads Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th class="w-[6%]">#</th>
                        <th class="w-[24%]">Lead</th>
                        <th class="w-[18%]">Contact</th>
                        <th class="w-[14%]">Source</th>
                        <th class="w-[24%]">Reason / Notes</th>
                        <th class="w-[10%]">Closed On</th>
                        <th class="w-[4%] text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($leads as $index => $lead)
                        <tr>
                            {{-- Number --}}
                            <td class="text-slate-500">
                                {{ ($leads->firstItem() ?? 1) + $index }}
                            </td>

                            {{-- Lead --}}
                            <td>
                                <div class="font-extrabold text-white">
                                    {{ $lead->name ?? 'Unnamed Lead' }}
                                </div>

                                <div class="mt-1">
                                    <span class="sf-badge-red">
                                        {{ ucfirst(str_replace('_', ' ', $lead->status ?? 'Disqualified')) }}
                                    </span>
                                </div>
                            </td>

                            {{-- Contact --}}
                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $lead->phone ?? $lead->phone_norm ?? 'No phone' }}
                                </div>

                                <div class="mt-1 truncate text-xs font-medium text-slate-500">
                                    {{ $lead->email ?? 'No email' }}
                                </div>
                            </td>

                            {{-- Source --}}
                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ ucfirst($lead->source ?? '—') }}
                                </div>

                                @if($lead->leadSource ?? false)
                                    <div class="mt-1 truncate text-xs text-slate-500">
                                        {{ $lead->leadSource->name }}
                                    </div>
                                @endif
                            </td>

                            {{-- Reason / Notes --}}
                            <td>
                                <div class="text-sm font-medium leading-6 text-slate-300">
                                    {{ \Illuminate\Support\Str::limit($lead->notes ?? '—', 80) }}
                                </div>
                            </td>

                            {{-- Disqualified On --}}
                            <td class="whitespace-nowrap">
                                <div class="font-bold text-slate-300">
                                    {{ $lead->updated_at?->format('d M Y') ?? '—' }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ $lead->updated_at?->format('h:i A') ?? '' }}
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="text-right">
                                <a href="{{ route('admin.leads.show', $lead) }}" class="sf-link">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="sf-empty">
                                    No disqualified leads found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="text-slate-300">
        {{ $leads->links() }}
    </div>

</div>
@endsection