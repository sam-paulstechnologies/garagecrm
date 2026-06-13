{{-- resources/views/admin/clients/import-preview.blade.php --}}

@extends('layouts.app')

@section('title', 'Client Import Retention Preview')

@section('content')
    @php
        $summaryCards = [
            ['label' => 'Rows Uploaded', 'value' => $summary['rows_uploaded'] ?? 0, 'class' => 'text-slate-100 bg-slate-800/70 border-slate-700'],
            ['label' => 'Previewed', 'value' => $summary['rows_previewed'] ?? 0, 'class' => 'text-blue-200 bg-blue-500/10 border-blue-400/20'],
            ['label' => 'Valid', 'value' => $summary['valid_rows'] ?? 0, 'class' => 'text-emerald-200 bg-emerald-500/10 border-emerald-400/20'],
            ['label' => 'Warnings', 'value' => $summary['warning_rows'] ?? 0, 'class' => 'text-amber-200 bg-amber-500/10 border-amber-400/20'],
            ['label' => 'Invalid', 'value' => $summary['invalid_rows'] ?? 0, 'class' => 'text-rose-200 bg-rose-500/10 border-rose-400/20'],
            ['label' => 'Duplicates', 'value' => $summary['duplicates'] ?? 0, 'class' => 'text-orange-200 bg-orange-500/10 border-orange-400/20'],
            ['label' => 'Suggested Actions', 'value' => $summary['suggested_retention_actions'] ?? 0, 'class' => 'text-purple-200 bg-purple-500/10 border-purple-400/20'],
        ];

        $statusClasses = [
            'valid' => 'bg-emerald-500/10 text-emerald-700 ring-emerald-400/20 dark:text-emerald-200',
            'warning' => 'bg-amber-500/10 text-amber-700 ring-amber-400/20 dark:text-amber-200',
            'invalid' => 'bg-rose-500/10 text-rose-700 ring-rose-400/20 dark:text-rose-200',
        ];

        $reviewClasses = [
            'pending_review' => 'bg-slate-500/10 text-slate-200 ring-slate-400/20',
            'approved' => 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20',
            'rejected' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
            'skipped' => 'bg-amber-500/10 text-amber-200 ring-amber-400/20',
            'applied' => 'bg-purple-500/10 text-purple-200 ring-purple-400/20',
        ];

        $displayValue = fn ($value, $fallback = '-') => filled($value) ? $value : $fallback;

        $reviewStatusFilters = [
            'pending_review' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'skipped' => 'Skipped',
            'all' => 'All',
        ];

        $currentReviewStatus = request('review_status', 'pending_review');

        if ($currentReviewStatus === 'pending') {
            $currentReviewStatus = 'pending_review';
        }

        if (! array_key_exists($currentReviewStatus, $reviewStatusFilters)) {
            $currentReviewStatus = 'pending_review';
        }

        $allPreviewRows = collect($rows ?? []);
        $visibleRows = $currentReviewStatus === 'all'
            ? $allPreviewRows
            : $allPreviewRows->filter(fn ($row) => ($row['review_status'] ?? 'pending_review') === $currentReviewStatus)->values();

        $visibleRowsLabel = $reviewStatusFilters[$currentReviewStatus];
    @endphp

    <style>
        html[data-theme="light"] .sf-import-preview [class*="border-slate-800"],
        html[data-theme="light"] .sf-import-preview [class*="border-slate-700"] {
            border-color: #e2e8f0 !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="bg-slate-900"],
        html[data-theme="light"] .sf-import-preview [class*="bg-slate-800"] {
            background: #ffffff !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="bg-slate-950"] {
            background: #f8fafc !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-white"],
        html[data-theme="light"] .sf-import-preview [class*="text-slate-100"] {
            color: #0f172a !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-slate-200"],
        html[data-theme="light"] .sf-import-preview [class*="text-slate-300"],
        html[data-theme="light"] .sf-import-preview [class*="text-slate-400"] {
            color: #475569 !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-orange-200"],
        html[data-theme="light"] .sf-import-preview [class*="text-orange-300"],
        html[data-theme="light"] .sf-import-preview [class*="text-orange-100"] {
            color: #c2410c !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-blue-200"],
        html[data-theme="light"] .sf-import-preview [class*="text-blue-100"] {
            color: #1d4ed8 !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-emerald-200"],
        html[data-theme="light"] .sf-import-preview [class*="text-emerald-100"] {
            color: #047857 !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-rose-200"] {
            color: #be123c !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-amber-200"] {
            color: #b45309 !important;
        }

        html[data-theme="light"] .sf-import-preview [class*="text-purple-200"],
        html[data-theme="light"] .sf-import-preview [class*="text-purple-100"] {
            color: #7e22ce !important;
        }

        html[data-theme="light"] .sf-import-preview thead {
            background: #f8fafc !important;
        }

        html[data-theme="light"] .sf-import-preview tbody,
        html[data-theme="light"] .sf-import-preview table {
            color: #334155 !important;
        }

        .client-import-checkbox-box svg {
            opacity: 0;
            transform: scale(0.85);
            transition: opacity 120ms ease, transform 120ms ease;
        }

        .client-import-row-checkbox:checked + .client-import-checkbox-box {
            border-color: #f97316;
            background: #f97316;
            color: #ffffff;
        }

        .client-import-row-checkbox:checked + .client-import-checkbox-box svg {
            opacity: 1;
            transform: scale(1);
        }
    </style>

    <div class="sf-page sf-import-preview mx-auto max-w-[1500px] px-4 py-6 space-y-5">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-orange-300">
                        Preview Only
                    </p>

                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-white">
                        Client Import Retention Preview
                    </h1>

                    <p class="mt-2 max-w-4xl text-sm font-semibold leading-6 text-slate-300">
                        No clients, vehicles, retention actions, campaigns, journeys, or messages have been created yet.
                        Review validation, duplicates, and suggested retention opportunities before a future approval phase.
                        Approval only marks rows for future application. It does not create clients or send messages.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('admin.clients.import.form') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-100 transition hover:bg-slate-700"
                    >
                        Upload Another File
                    </a>

                    @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.batches.index'))
                        <a
                            href="{{ route('admin.clients.import.batches.index') }}"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-blue-400/20 bg-blue-500/10 px-4 text-sm font-bold text-blue-200 transition hover:bg-blue-500/15"
                        >
                            Recent Previews
                        </a>
                    @endif

                    <span
                        class="inline-flex h-10 cursor-not-allowed items-center justify-center rounded-xl border border-orange-400/20 bg-orange-500/10 px-4 text-sm font-extrabold text-orange-200"
                        title="Bulk approval is planned for a later phase."
                    >
                        Approval Coming in Phase 4
                    </span>
                </div>
            </div>
        </div>

        @isset($batch)
            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-400">Filename</div>
                    <div class="mt-1 break-words text-sm font-extrabold text-white">{{ $batch->original_filename }}</div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-400">Uploaded By</div>
                    <div class="mt-1 text-sm font-extrabold text-white">{{ $batch->uploadedBy?->name ?? 'Unknown user' }}</div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-400">Uploaded Date</div>
                    <div class="mt-1 text-sm font-extrabold text-white">{{ $batch->created_at?->format('d M Y H:i') }}</div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-400">Status</div>
                    <div class="mt-1 inline-flex rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-black text-emerald-200 ring-1 ring-emerald-400/20">
                        {{ \Illuminate\Support\Str::headline($batch->status) }}
                    </div>
                </div>
            </div>
        @endisset

        @if(($summary['truncated'] ?? false) === true)
            <div class="rounded-2xl border border-amber-400/20 bg-amber-500/10 p-4 text-sm font-bold text-amber-200">
                Preview limited to the first {{ $summary['limit'] ?? 200 }} non-empty rows. Persistent import batches should be added in Phase 2 for larger files.
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            @foreach($summaryCards as $card)
                <div class="rounded-2xl border p-4 {{ $card['class'] }}">
                    <div class="text-2xl font-black leading-none">
                        {{ $card['value'] }}
                    </div>

                    <div class="mt-2 text-xs font-black uppercase tracking-wide opacity-90">
                        {{ $card['label'] }}
                    </div>
                </div>
            @endforeach
        </div>

        @isset($reviewSummary)
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                @foreach([
                    'pending_review' => 'Pending Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'skipped' => 'Skipped',
                    'applied' => 'Applied',
                    'invalid' => 'Invalid',
                    'warning' => 'Warning',
                ] as $key => $label)
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4">
                        <div class="text-2xl font-black text-white">{{ $reviewSummary[$key] ?? 0 }}</div>
                        <div class="mt-2 text-xs font-black uppercase tracking-wide text-slate-400">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        @endisset

        @isset($batch)
            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-orange-200">
                            Apply Approved Rows to CRM
                        </h2>

                        <p class="mt-1 max-w-4xl text-sm font-semibold leading-6 text-orange-100">
                            Applying approved rows will create or update clients and vehicles only. It will not send WhatsApp messages,
                            create campaigns, create journeys, create message logs, or schedule retention actions.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route('admin.clients.import.batches.apply', $batch) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="dry_run">
                            <button class="inline-flex h-10 items-center justify-center rounded-xl border border-blue-400/20 bg-blue-500/10 px-4 text-sm font-extrabold text-blue-200 transition hover:bg-blue-500/15">
                                Preview Apply
                            </button>
                        </form>

                        <form action="{{ route('admin.clients.import.batches.apply', $batch) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="apply">
                            <button
                                class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                                onclick="return confirm('Apply approved rows to clients and vehicles only? No messages will be sent.');"
                            >
                                Apply Approved Rows
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endisset

        @isset($batch)
            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-emerald-200">
                            Store Imported Service History
                        </h2>

                        <p class="mt-1 max-w-4xl text-sm font-semibold leading-6 text-emerald-100">
                            This stores imported service history only. It will not create retention actions, campaigns, journeys,
                            message logs, or WhatsApp messages.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route('admin.clients.import.batches.service-history', $batch) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="dry_run">
                            <button class="inline-flex h-10 items-center justify-center rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 text-sm font-extrabold text-emerald-200 transition hover:bg-emerald-500/15">
                                Preview Service History
                            </button>
                        </form>

                        <form action="{{ route('admin.clients.import.batches.service-history', $batch) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="apply">
                            <button
                                class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-600"
                                onclick="return confirm('Create imported service history from applied rows only? No messages or retention actions will be created.');"
                            >
                                Create Service History
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endisset

        @isset($batch)
            <div class="rounded-2xl border border-purple-400/20 bg-purple-500/10 p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-purple-200">
                            Create Pending Retention Actions
                        </h2>

                        <p class="mt-1 max-w-4xl text-sm font-semibold leading-6 text-purple-100">
                            This creates pending retention actions only. No WhatsApp messages will be sent, and no campaigns,
                            journeys, message logs, or schedules will be created.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route('admin.clients.import.batches.retention-actions', $batch) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="dry_run">
                            <button class="inline-flex h-10 items-center justify-center rounded-xl border border-purple-400/20 bg-purple-500/10 px-4 text-sm font-extrabold text-purple-200 transition hover:bg-purple-500/15">
                                Preview Retention Actions
                            </button>
                        </form>

                        <form action="{{ route('admin.clients.import.batches.retention-actions', $batch) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="apply">
                            <button
                                class="inline-flex h-10 items-center justify-center rounded-xl bg-purple-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-purple-950/30 transition hover:bg-purple-600"
                                onclick="return confirm('Create pending retention actions only? No WhatsApp messages will be sent.');"
                            >
                                Create Retention Actions
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endisset

        @isset($applySummary)
            <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-sm">
                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-extrabold tracking-tight text-blue-200">
                        {{ ($applySummary['mode'] ?? 'dry_run') === 'dry_run' ? 'Dry-run Apply Summary' : 'Apply Summary' }}
                    </h2>

                    <p class="text-sm font-semibold text-blue-100">
                        This summary is for approved, non-invalid, not-yet-applied rows only.
                    </p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                    @foreach([
                        'rows_eligible' => 'Eligible',
                        'clients_to_create' => 'Clients To Create',
                        'clients_to_reuse' => 'Clients To Reuse',
                        'clients_to_update' => 'Clients To Update',
                        'vehicles_to_create' => 'Vehicles To Create',
                        'vehicles_to_reuse' => 'Vehicles To Reuse',
                        'vehicles_to_update' => 'Vehicles To Update',
                        'rows_skipped' => 'Rows Skipped',
                    ] as $key => $label)
                        <div class="rounded-xl border border-blue-400/20 bg-slate-950/30 p-3">
                            <div class="text-xl font-black text-white">{{ $applySummary[$key] ?? 0 }}</div>
                            <div class="mt-1 text-[11px] font-black uppercase tracking-wide text-blue-200">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                @if(!empty($applySummary['records']))
                    <div class="mt-4 overflow-x-auto rounded-xl border border-blue-400/20">
                        <table class="min-w-full divide-y divide-blue-400/20">
                            <thead class="bg-slate-950/30">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-blue-200">Row</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-blue-200">Client Action</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-blue-200">Vehicle Action</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-blue-200">Client ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-blue-200">Vehicle ID</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-400/20">
                                @foreach($applySummary['records'] as $record)
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-bold text-white">#{{ $record['row_number'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-blue-100">{{ \Illuminate\Support\Str::headline($record['client_action'] ?? $record['action'] ?? '-') }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-blue-100">{{ \Illuminate\Support\Str::headline($record['vehicle_action'] ?? '-') }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-blue-100">{{ $record['client_id'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-blue-100">{{ $record['vehicle_id'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endisset

        @isset($serviceHistorySummary)
            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-5 shadow-sm">
                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-extrabold tracking-tight text-emerald-200">
                        {{ ($serviceHistorySummary['mode'] ?? 'dry_run') === 'dry_run' ? 'Dry-run Service History Summary' : 'Service History Summary' }}
                    </h2>

                    <p class="text-sm font-semibold text-emerald-100">
                        Only applied rows with client matches and historical service/activity data are eligible.
                    </p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        'eligible_applied_rows' => 'Applied Rows',
                        'histories_to_create' => 'Histories To Create',
                        'histories_created' => 'Histories Created',
                        'duplicate_existing_histories' => 'Existing Duplicates',
                        'skipped_rows' => 'Skipped Rows',
                    ] as $key => $label)
                        <div class="rounded-xl border border-emerald-400/20 bg-slate-950/30 p-3">
                            <div class="text-xl font-black text-white">{{ $serviceHistorySummary[$key] ?? 0 }}</div>
                            <div class="mt-1 text-[11px] font-black uppercase tracking-wide text-emerald-200">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                @if(!empty($serviceHistorySummary['records']))
                    <div class="mt-4 overflow-x-auto rounded-xl border border-emerald-400/20">
                        <table class="min-w-full divide-y divide-emerald-400/20">
                            <thead class="bg-slate-950/30">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-emerald-200">Row</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-emerald-200">Action</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-emerald-200">Reason</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-emerald-200">History ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-emerald-200">Client ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-emerald-200">Vehicle ID</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-400/20">
                                @foreach($serviceHistorySummary['records'] as $record)
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-bold text-white">#{{ $record['row_number'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-emerald-100">{{ \Illuminate\Support\Str::headline($record['action'] ?? '-') }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-emerald-100">{{ $record['reason'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-emerald-100">{{ $record['history_id'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-emerald-100">{{ $record['client_id'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-emerald-100">{{ $record['vehicle_id'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endisset

        @isset($retentionActionSummary)
            <div class="rounded-2xl border border-purple-400/20 bg-purple-500/10 p-5 shadow-sm">
                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-extrabold tracking-tight text-purple-200">
                        {{ ($retentionActionSummary['mode'] ?? 'dry_run') === 'dry_run' ? 'Dry-run Retention Action Summary' : 'Retention Action Summary' }}
                    </h2>

                    <p class="text-sm font-semibold text-purple-100">
                        Eligible rows are applied rows with a client, actionable segment, and useful follow-up timing or immediate-review segment.
                    </p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        'eligible_rows' => 'Eligible Rows',
                        'actions_to_create' => 'Actions To Create',
                        'actions_created' => 'Actions Created',
                        'duplicate_existing_actions' => 'Existing Duplicates',
                        'unclassified_skipped' => 'Unclassified Skipped',
                        'missing_client_skipped' => 'Missing Client',
                        'skipped_rows' => 'Skipped Rows',
                    ] as $key => $label)
                        <div class="rounded-xl border border-purple-400/20 bg-slate-950/30 p-3">
                            <div class="text-xl font-black text-white">{{ $retentionActionSummary[$key] ?? 0 }}</div>
                            <div class="mt-1 text-[11px] font-black uppercase tracking-wide text-purple-200">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                @if(!empty($retentionActionSummary['records']))
                    <div class="mt-4 overflow-x-auto rounded-xl border border-purple-400/20">
                        <table class="min-w-full divide-y divide-purple-400/20">
                            <thead class="bg-slate-950/30">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Row</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Action</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Segment</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Reason</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Retention ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">History ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Client ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-black uppercase tracking-wide text-purple-200">Vehicle ID</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-purple-400/20">
                                @foreach($retentionActionSummary['records'] as $record)
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-bold text-white">#{{ $record['row_number'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ \Illuminate\Support\Str::headline($record['action'] ?? '-') }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ $record['segment_code'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ $record['reason'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ $record['retention_action_id'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ $record['vehicle_service_history_id'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ $record['client_id'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-purple-100">{{ $record['vehicle_id'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endisset

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-slate-950 dark:text-white">
                            Preview Rows
                        </h2>

                        <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                            Duplicate checks are read-only and matched inside the current company only.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 lg:items-end">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ $visibleRows->count() }} {{ $visibleRowsLabel }} {{ \Illuminate\Support\Str::plural('row', $visibleRows->count()) }} shown
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach($reviewStatusFilters as $filterKey => $filterLabel)
                                @php
                                    $isActiveFilter = $currentReviewStatus === $filterKey;
                                    $filterCount = $filterKey === 'all'
                                        ? $allPreviewRows->count()
                                        : $allPreviewRows->where('review_status', $filterKey)->count();
                                @endphp

                                <a
                                    href="{{ request()->fullUrlWithQuery(['review_status' => $filterKey]) }}"
                                    class="inline-flex h-9 items-center justify-center rounded-xl border px-3 text-xs font-extrabold transition {{ $isActiveFilter ? 'border-orange-300 bg-orange-50 text-orange-700 dark:border-orange-400/30 dark:bg-orange-500/10 dark:text-orange-200' : 'border-slate-200 bg-white text-slate-700 hover:border-orange-200 hover:text-orange-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-orange-400/30 dark:hover:text-orange-200' }}"
                                >
                                    {{ $filterLabel }}
                                    <span class="ml-2 rounded-full {{ $isActiveFilter ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-100' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }} px-2 py-0.5 text-[10px] font-black">
                                        {{ $filterCount }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @isset($batch)
                <form
                    id="import-bulk-review-form"
                    action="{{ route('admin.clients.import.batches.bulk-review', $batch) }}"
                    method="POST"
                    class="sticky top-16 z-20 border-b border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/90"
                >
                    @csrf

                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="text-sm font-extrabold text-slate-950 dark:text-white">Bulk review selected rows</div>
                            <div class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                <span id="import-selected-count">0 rows selected.</span>
                                Bulk approve skips invalid rows. Applied rows are never changed here.
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                id="select-all-import-rows"
                                class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                            >
                                Select all visible
                            </button>

                            <button
                                type="button"
                                id="clear-import-rows"
                                class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                            >
                                Clear selection
                            </button>

                            <button
                                type="button"
                                id="select-valid-import-rows"
                                class="inline-flex h-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-extrabold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/15"
                            >
                                Select valid
                            </button>

                            <button
                                type="button"
                                id="select-warning-import-rows"
                                class="inline-flex h-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-extrabold text-amber-700 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/15"
                            >
                                Select warnings
                            </button>

                            <button type="submit" name="action" value="approve" class="inline-flex h-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-extrabold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/15">
                                Approve selected
                            </button>

                            <button type="submit" name="action" value="reject" class="inline-flex h-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-extrabold text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15">
                                Reject selected
                            </button>

                            <button type="submit" name="action" value="skip" class="inline-flex h-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-extrabold text-amber-700 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/15">
                                Skip selected
                            </button>

                            <button type="submit" name="action" value="reset" class="inline-flex h-9 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-extrabold text-blue-700 transition hover:bg-blue-100 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/15">
                                Reset selected
                            </button>
                        </div>
                    </div>
                </form>
            @endisset

            <div class="space-y-4 p-4 sm:p-5">
                @forelse($visibleRows as $row)
                    @php
                        $data = $row['data'];
                        $vehicle = trim(implode(' ', array_filter([
                            $data['vehicle_make'] ?? null,
                            $data['vehicle_model'] ?? null,
                        ])));
                        $status = $row['status'] ?? 'invalid';
                        $reviewStatus = $row['review_status'] ?? 'pending_review';
                        $canEdit = $reviewStatus !== 'applied';
                        $canApprove = $canEdit && $status !== 'invalid';
                        $duplicateLabel = $row['duplicate']
                            ? \Illuminate\Support\Str::headline($row['duplicate_status'] ?? 'matched')
                            : 'No match';
                        $checkboxId = isset($row['row_id'])
                            ? 'client-import-row-checkbox-' . $row['row_id']
                            : 'client-import-row-checkbox-' . $row['row_number'];
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm transition hover:border-orange-200 hover:bg-white dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-orange-400/30 dark:hover:bg-slate-950/70">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr),auto] xl:items-start">
                            <div class="min-w-0">
                                <div class="grid gap-3 sm:grid-cols-[auto,minmax(0,1fr)] sm:items-start">
                                    @isset($batch)
                                        <div class="shrink-0 pt-1">
                                            @if($reviewStatus !== 'applied')
                                                <label
                                                    for="{{ $checkboxId }}"
                                                    class="inline-flex cursor-pointer select-none items-center gap-2"
                                                >
                                                    <input
                                                        id="{{ $checkboxId }}"
                                                        form="import-bulk-review-form"
                                                        type="checkbox"
                                                        name="row_ids[]"
                                                        value="{{ $row['row_id'] }}"
                                                        class="client-import-row-checkbox import-row-checkbox peer sr-only"
                                                        data-validation-status="{{ $status }}"
                                                        data-review-status="{{ $reviewStatus }}"
                                                        aria-label="Select row {{ $row['row_number'] }}"
                                                    >

                                                    <span
                                                        class="client-import-checkbox-box flex h-6 w-6 shrink-0 items-center justify-center rounded-md border-2 border-slate-400 bg-white text-white transition peer-focus:ring-2 peer-focus:ring-orange-500 peer-focus:ring-offset-2 dark:border-slate-500 dark:bg-slate-900 dark:peer-focus:ring-offset-slate-950"
                                                        aria-hidden="true"
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                                                            <path d="M5 10.5 8.2 13.7 15 6.8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </span>

                                                    <span
                                                        class="inline-flex min-h-9 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-extrabold text-slate-700 transition peer-checked:border-orange-300 peer-checked:bg-orange-50 peer-checked:text-orange-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:peer-checked:border-orange-400/30 dark:peer-checked:bg-orange-500/10 dark:peer-checked:text-orange-200"
                                                    >
                                                        Select
                                                    </span>
                                                </label>
                                            @else
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20">
                                                    Locked
                                                </span>
                                            @endif
                                        </div>
                                    @endisset

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20">
                                                Row #{{ $row['row_number'] }}
                                            </span>

                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $statusClasses[$status] ?? $statusClasses['invalid'] }}">
                                                {{ ucfirst($status) }}
                                            </span>

                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $row['duplicate'] ? 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-400/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20' }}">
                                                {{ $duplicateLabel }}
                                            </span>

                                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20">
                                                {{ $row['suggestion']['segment_label'] ?? 'Unclassified' }}
                                            </span>

                                            @isset($batch)
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $reviewClasses[$reviewStatus] ?? $reviewClasses['pending_review'] }}">
                                                    {{ \Illuminate\Support\Str::headline($reviewStatus) }}
                                                </span>
                                            @endisset
                                        </div>

                                        <div class="mt-3 min-w-0">
                                            <h3 class="whitespace-normal break-normal text-lg font-black leading-tight text-slate-950 [overflow-wrap:normal] dark:text-white">
                                                {{ $displayValue($data['name']) }}
                                            </h3>

                                            <div class="mt-1 break-all text-sm font-semibold leading-5 text-slate-600 sm:break-words dark:text-slate-300">
                                                {{ $displayValue($data['email'], 'Email optional') }}
                                            </div>

                                            @if(filled($data['is_vip']))
                                                <div class="mt-2 inline-flex rounded-full bg-purple-50 px-2.5 py-1 text-xs font-black text-purple-700 ring-1 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-200 dark:ring-purple-400/20">
                                                    VIP: {{ $data['is_vip'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @isset($batch)
                                <div class="flex w-full flex-wrap gap-2 xl:w-auto xl:max-w-[360px] xl:shrink-0 xl:justify-end">
                                    @if($canEdit)
                                        <form action="{{ route('admin.clients.import.rows.review', [$batch, $row['row_id']]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_status" value="approved">
                                            <button
                                                class="inline-flex h-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-extrabold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/15"
                                                @disabled(! $canApprove)
                                                title="{{ $canApprove ? 'Approve row' : 'Invalid rows cannot be approved' }}"
                                            >
                                                Approve
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.clients.import.rows.review', [$batch, $row['row_id']]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_status" value="rejected">
                                            <button class="inline-flex h-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-extrabold text-rose-700 transition hover:bg-rose-100 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15">
                                                Reject
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.clients.import.rows.review', [$batch, $row['row_id']]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_status" value="skipped">
                                            <button class="inline-flex h-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-extrabold text-amber-700 transition hover:bg-amber-100 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/15">
                                                Skip
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.clients.import.rows.review', [$batch, $row['row_id']]) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="review_status" value="pending_review">
                                            <button class="inline-flex h-9 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-extrabold text-blue-700 transition hover:bg-blue-100 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/15">
                                                Reset
                                            </button>
                                        </form>
                                    @else
                                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Applied rows are locked.</p>
                                    @endif
                                </div>
                            @endisset
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                <div class="text-[11px] font-black uppercase tracking-wide text-slate-500">Contact</div>
                                <dl class="mt-2 space-y-1 text-sm">
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Phone</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['phone']) }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">WhatsApp</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['whatsapp']) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                <div class="text-[11px] font-black uppercase tracking-wide text-slate-500">Vehicle</div>
                                <dl class="mt-2 space-y-1 text-sm">
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Model</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($vehicle, 'Vehicle missing') }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Plate</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['plate_number']) }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Year</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['vehicle_year']) }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Mileage</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['last_mileage']) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                <div class="text-[11px] font-black uppercase tracking-wide text-slate-500">Last Activity</div>
                                <dl class="mt-2 space-y-1 text-sm">
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Service</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['last_service_type']) }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Date</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['last_service_date']) }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Insurance</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['insurance_expiry_date']) }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-20 shrink-0 font-bold text-slate-500">Mulkia</dt>
                                        <dd class="min-w-0 break-words font-semibold text-slate-800 dark:text-slate-200">{{ $displayValue($data['mulkia_expiry_date']) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/70">
                                <div class="text-[11px] font-black uppercase tracking-wide text-slate-500">Duplicate Check</div>
                                <div class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    @if($row['duplicate'])
                                        <div class="rounded-xl border border-orange-200 bg-orange-50 p-3 text-xs font-bold text-orange-700 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200">
                                            <div class="mb-1 inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-orange-700 ring-1 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-400/20">
                                                {{ $duplicateLabel }}
                                            </div>
                                            <div>Client #{{ $row['duplicate']['id'] }}</div>
                                            <div class="mt-1 break-words">{{ $row['duplicate']['name'] }}</div>
                                        </div>
                                    @else
                                        <span class="text-slate-500 dark:text-slate-400">No match</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3 lg:grid-cols-3">
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-400/20 dark:bg-blue-500/10">
                                <div class="text-[11px] font-black uppercase tracking-wide text-blue-700 dark:text-blue-200">Suggested Retention</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20">
                                        {{ $row['suggestion']['segment_label'] ?? 'Unclassified' }}
                                    </span>

                                    @foreach(($row['suggestion']['secondary_segments'] ?? []) as $secondary)
                                        <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-black text-purple-700 ring-1 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-200 dark:ring-purple-400/20">
                                            {{ $secondary['segment_label'] }}
                                        </span>
                                    @endforeach
                                </div>
                                <div class="mt-2 text-sm font-bold text-blue-800 dark:text-blue-100">
                                    Follow-up: {{ $displayValue($row['suggestion']['follow_up_date'] ?? null) }}
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-3 lg:col-span-2 dark:border-slate-800 dark:bg-slate-900/70">
                                <div class="text-[11px] font-black uppercase tracking-wide text-slate-500">Suggested Message</div>
                                <p class="mt-2 break-words rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm font-semibold leading-6 text-slate-800 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-200">
                                    {{ $row['suggestion']['message'] ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="rounded-xl border {{ $row['errors'] ? 'border-rose-200 bg-rose-50 dark:border-rose-400/20 dark:bg-rose-500/10' : ($row['warnings'] ? 'border-amber-200 bg-amber-50 dark:border-amber-400/20 dark:bg-amber-500/10' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-400/20 dark:bg-emerald-500/10') }} p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-[11px] font-black uppercase tracking-wide {{ $row['errors'] ? 'text-rose-700 dark:text-rose-200' : ($row['warnings'] ? 'text-amber-700 dark:text-amber-200' : 'text-emerald-700 dark:text-emerald-200') }}">
                                        Validation
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $statusClasses[$status] ?? $statusClasses['invalid'] }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>

                                @if($row['errors'])
                                    <ul class="mt-3 space-y-1 text-xs font-bold text-rose-700 dark:text-rose-200">
                                        @foreach($row['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if($row['warnings'])
                                    <ul class="mt-3 space-y-1 text-xs font-bold text-amber-700 dark:text-amber-200">
                                        @foreach($row['warnings'] as $warning)
                                            <li>{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if(! $row['errors'] && ! $row['warnings'])
                                    <p class="mt-2 text-xs font-bold text-emerald-700 dark:text-emerald-200">
                                        No row validation issues detected.
                                    </p>
                                @endif

                                @if($row['duplicate'] && isset($batch) && $canApprove)
                                    <p class="mt-2 text-xs font-bold text-orange-700 dark:text-orange-200">
                                        Duplicate row can be approved, but future apply may reuse/update the matched client.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-10 text-center text-sm font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-300">
                        No {{ $visibleRowsLabel }} rows are currently shown for this import batch.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @isset($batch)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const bulkForm = document.getElementById('import-bulk-review-form');
                const selectedCount = document.getElementById('import-selected-count');
                const selectAll = document.getElementById('select-all-import-rows');
                const clearSelection = document.getElementById('clear-import-rows');
                const selectValid = document.getElementById('select-valid-import-rows');
                const selectWarnings = document.getElementById('select-warning-import-rows');
                const checkboxes = Array.from(document.querySelectorAll('.client-import-row-checkbox'));

                if (!bulkForm || checkboxes.length === 0) {
                    return;
                }

                const updateSelectedCount = function () {
                    const count = checkboxes.filter((checkbox) => checkbox.checked).length;

                    if (selectedCount) {
                        selectedCount.textContent = count === 1 ? '1 row selected.' : `${count} rows selected.`;
                    }
                };

                const setChecked = function (predicate) {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = Boolean(predicate(checkbox));
                    });

                    updateSelectedCount();
                };

                if (selectAll) {
                    selectAll.addEventListener('click', function (event) {
                        event.preventDefault();
                        setChecked(() => true);
                    });
                }

                if (clearSelection) {
                    clearSelection.addEventListener('click', function (event) {
                        event.preventDefault();
                        setChecked(() => false);
                    });
                }

                if (selectValid) {
                    selectValid.addEventListener('click', function (event) {
                        event.preventDefault();
                        setChecked((checkbox) => checkbox.dataset.validationStatus === 'valid');
                    });
                }

                if (selectWarnings) {
                    selectWarnings.addEventListener('click', function (event) {
                        event.preventDefault();
                        setChecked((checkbox) => checkbox.dataset.validationStatus === 'warning');
                    });
                }

                checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', updateSelectedCount);
                });

                bulkForm.addEventListener('submit', function (event) {
                    if (!checkboxes.some((checkbox) => checkbox.checked)) {
                        event.preventDefault();
                        alert('Select at least one row before running a bulk action.');
                    }
                });

                updateSelectedCount();
            });
        </script>
    @endisset
@endsection
