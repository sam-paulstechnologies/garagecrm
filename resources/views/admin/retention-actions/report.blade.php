{{-- resources/views/admin/retention-actions/report.blade.php --}}

@extends('layouts.app')

@section('title', 'Retention Report')

@section('content')
    @php
        $queryWithoutPage = request()->except('page');

        $summaryCards = [
            ['key' => 'total', 'label' => 'Total Retention Actions', 'class' => 'border-slate-200 bg-slate-50 text-slate-800 dark:border-slate-400/20 dark:bg-slate-500/10 dark:text-slate-100'],
            ['key' => 'pending_review', 'label' => 'Pending Review', 'class' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200'],
            ['key' => 'approved', 'label' => 'Approved', 'class' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200'],
            ['key' => 'scheduled', 'label' => 'Scheduled', 'class' => 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-400/20 dark:bg-indigo-500/10 dark:text-indigo-200'],
            ['key' => 'sent', 'label' => 'Sent', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'],
            ['key' => 'skipped', 'label' => 'Skipped', 'class' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-400/20 dark:bg-slate-500/10 dark:text-slate-200'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'class' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200'],
            ['key' => 'due_today', 'label' => 'Due Today', 'class' => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200'],
            ['key' => 'overdue', 'label' => 'Overdue', 'class' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200'],
            ['key' => 'template_pending', 'label' => 'Template Pending', 'class' => 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-400/20 dark:bg-yellow-500/10 dark:text-yellow-200'],
            ['key' => 'missing_template', 'label' => 'Missing Template', 'class' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200'],
        ];

        $statusBadgeClasses = [
            'pending_review' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
            'approved' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20',
            'scheduled' => 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-200 dark:ring-indigo-400/20',
            'sent' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
            'skipped' => 'bg-slate-100 text-slate-700 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20',
            'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
        ];

        $readinessBadgeClasses = [
            'ready' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
            'template_pending' => 'bg-yellow-50 text-yellow-800 ring-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-200 dark:ring-yellow-400/20',
            'warning_missing_template' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
            'warning_missing_vehicle' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
            'blocked_no_phone' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
            'blocked_opted_out' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
            'template_rejected' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
            'needs_review' => 'bg-slate-100 text-slate-700 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20',
        ];
    @endphp

    <div class="sf-page mx-auto max-w-[1500px] space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">
                        Retention Reporting
                    </p>

                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                        Retention Actions Report
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                        Reporting-only view for retention opportunity, review status, template readiness, and upcoming follow-ups. No messages are sent from this page.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <a
                        href="{{ route('admin.retention-actions.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        Review Actions
                    </a>

                    @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.batches.index'))
                        <a
                            href="{{ route('admin.clients.import.batches.index') }}"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-orange-200 bg-orange-50 px-4 text-sm font-extrabold text-orange-700 transition hover:bg-orange-100 hover:text-orange-800 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200 dark:hover:bg-orange-500/15 dark:hover:text-orange-100"
                        >
                            Import Batches
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.retention-actions.report') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80" data-index-filter-panel>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-black text-slate-950 dark:text-white">Filters</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Search retention actions by status, segment, batch, and date.</p>
                </div>
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" data-index-filter-toggle aria-expanded="false">
                    Show Filters
                </button>
            </div>

            <div class="mt-4 hidden grid gap-3 lg:grid-cols-12" data-index-filter-body>
                <label class="lg:col-span-3">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Search</span>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Client, phone, segment, message"
                        class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                    >
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Status</span>
                    <select name="status" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">All statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Segment</span>
                    <select name="segment" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">All segments</option>
                        @foreach($segments as $value => $label)
                            <option value="{{ $value }}" @selected(request('segment') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Source Batch</span>
                    <select name="source_batch" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">All batches</option>
                        @foreach($sourceBatches as $batch)
                            <option value="{{ $batch->id }}" @selected((string) request('source_batch') === (string) $batch->id)>
                                #{{ $batch->id }} {{ $batch->original_filename }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-1">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">From</span>
                    <input type="date" name="from" value="{{ request('from') }}" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>

                <label class="lg:col-span-1">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">To</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>

                <div class="flex items-end gap-2 lg:col-span-1">
                    <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-black text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600">
                        Filter
                    </button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('admin.retention-actions.report', array_merge($queryWithoutPage, ['due_soon' => 1, 'overdue' => null])) }}" class="inline-flex h-8 items-center rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/15">
                    Due Soon
                </a>
                <a href="{{ route('admin.retention-actions.report', array_merge($queryWithoutPage, ['overdue' => 1, 'due_soon' => null])) }}" class="inline-flex h-8 items-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15">
                    Overdue
                </a>
                <a href="{{ route('admin.retention-actions.report') }}" class="inline-flex h-8 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    Clear
                </a>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            @foreach($summaryCards as $card)
                <div class="rounded-2xl border p-4 {{ $card['class'] }}">
                    <div class="text-xs font-black uppercase tracking-wide">{{ $card['label'] }}</div>
                    <div class="mt-2 text-2xl font-black">{{ $summary[$card['key']] ?? 0 }}</div>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-bold text-blue-800 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-100">
            Reply, booking, and revenue conversion reporting will become available after WhatsApp dispatch is enabled.
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-950 dark:text-white">Segment Breakdown</h2>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Actions by retention segment and review status.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead>
                            <tr class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-wide text-slate-600 dark:bg-slate-950/60 dark:text-slate-400">
                                <th class="px-2 py-2">Segment</th>
                                <th class="px-2 py-2 text-right">Total</th>
                                <th class="px-2 py-2 text-right">Pending</th>
                                <th class="px-2 py-2 text-right">Approved</th>
                                <th class="px-2 py-2 text-right">Scheduled</th>
                                <th class="px-2 py-2 text-right">Sent</th>
                                <th class="px-2 py-2 text-right">Skipped</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($segmentBreakdown as $row)
                                <tr class="align-top hover:bg-slate-50 dark:hover:bg-slate-950/40">
                                    <td class="px-2 py-3">
                                        <div class="font-black text-slate-950 dark:text-white">{{ $row->segment_label ?: \Illuminate\Support\Str::headline($row->segment_code) }}</div>
                                        <div class="break-all text-xs font-semibold text-slate-500">{{ $row->segment_code }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right font-black text-slate-800 dark:text-slate-100">{{ $row->total }}</td>
                                    <td class="px-2 py-3 text-right font-bold text-amber-700 dark:text-amber-200">{{ $row->pending_review }}</td>
                                    <td class="px-2 py-3 text-right font-bold text-blue-700 dark:text-blue-200">{{ $row->approved }}</td>
                                    <td class="px-2 py-3 text-right font-bold text-indigo-700 dark:text-indigo-200">{{ $row->scheduled }}</td>
                                    <td class="px-2 py-3 text-right font-bold text-emerald-700 dark:text-emerald-200">{{ $row->sent }}</td>
                                    <td class="px-2 py-3 text-right font-bold text-slate-700 dark:text-slate-300">{{ (int) $row->skipped + (int) $row->cancelled }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-sm font-bold text-slate-500 dark:text-slate-400">No segment data matches the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-950 dark:text-white">Upcoming Follow-ups</h2>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Sorted by scheduled date first, then suggested follow-up date.</p>
                </div>

                <div class="space-y-3">
                    @forelse($upcomingFollowUps as $action)
                        @php
                            $vehicleName = trim(($action->vehicle?->make?->name ?? '') . ' ' . ($action->vehicle?->model?->name ?? ''));
                            $dueDate = $action->scheduled_at ?: $action->suggested_follow_up_date;
                            $preview = $action->template_preview ?? [];
                        @endphp
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="font-black text-slate-950 dark:text-white">{{ $action->client?->name ?: 'Unknown Client' }}</div>
                                    <div class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">
                                        {{ $vehicleName !== '' ? $vehicleName : 'Vehicle not linked' }} &middot; {{ $action->segment_label ?: \Illuminate\Support\Str::headline($action->segment_code) }}
                                    </div>
                                    <div class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-500">
                                        {{ $dueDate ? $dueDate->format('d M Y') : 'No follow-up date' }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $statusBadgeClasses[$action->status] ?? 'bg-slate-100 text-slate-700 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20' }}">
                                        {{ $statuses[$action->status] ?? \Illuminate\Support\Str::headline($action->status) }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $readinessBadgeClasses[$preview['readiness'] ?? 'needs_review'] ?? 'bg-slate-100 text-slate-700 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20' }}">
                                        {{ $preview['readiness_label'] ?? 'Needs Review' }}
                                    </span>
                                    <a href="{{ route('admin.retention-actions.index', ['q' => $action->client?->name]) }}" class="text-xs font-black text-orange-700 hover:text-orange-800 dark:text-orange-200 dark:hover:text-orange-100">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-400">
                            No upcoming follow-ups match the current filters.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Import Batch Contribution</h2>
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Retention actions created from client import batches.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:bg-slate-950/60 dark:text-slate-400">
                            <th class="px-3 py-2">Batch</th>
                            <th class="px-3 py-2 text-right">Actions Created</th>
                            <th class="px-3 py-2 text-right">Approved</th>
                            <th class="px-3 py-2 text-right">Scheduled</th>
                            <th class="px-3 py-2 text-right">Sent</th>
                            <th class="px-3 py-2 text-right">Skipped</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($batchContribution as $batch)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/40">
                                <td class="px-3 py-3">
                                    <div class="font-black text-slate-950 dark:text-white">Batch #{{ $batch->id }}</div>
                                    <div class="text-xs font-semibold text-slate-500">{{ $batch->original_filename ?: 'Imported file' }}</div>
                                </td>
                                <td class="px-3 py-3 text-right font-black text-slate-800 dark:text-slate-100">{{ $batch->actions_created }}</td>
                                <td class="px-3 py-3 text-right font-bold text-blue-700 dark:text-blue-200">{{ $batch->approved }}</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-700 dark:text-indigo-200">{{ $batch->scheduled }}</td>
                                <td class="px-3 py-3 text-right font-bold text-emerald-700 dark:text-emerald-200">{{ $batch->sent }}</td>
                                <td class="px-3 py-3 text-right font-bold text-slate-700 dark:text-slate-300">{{ $batch->skipped }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-sm font-bold text-slate-500 dark:text-slate-400">No import batch contribution is available for the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @include('admin.partials._index_filter_chip_script')
@endsection
