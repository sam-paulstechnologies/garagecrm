{{-- resources/views/admin/retention-actions/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Retention Actions')

@section('content')
    @php
        $statusClasses = [
            'pending_review' => 'bg-amber-500/10 text-amber-200 ring-amber-400/20',
            'approved' => 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20',
            'skipped' => 'bg-slate-500/10 text-slate-200 ring-slate-400/20',
            'cancelled' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
            'scheduled' => 'bg-indigo-500/10 text-indigo-200 ring-indigo-400/20',
            'sent' => 'bg-blue-500/10 text-blue-200 ring-blue-400/20',
        ];

        $summaryCards = [
            ['key' => 'pending_review', 'label' => 'Pending Review', 'class' => 'text-amber-200 bg-amber-500/10 border-amber-400/20'],
            ['key' => 'approved', 'label' => 'Approved', 'class' => 'text-emerald-200 bg-emerald-500/10 border-emerald-400/20'],
            ['key' => 'skipped', 'label' => 'Skipped', 'class' => 'text-slate-200 bg-slate-500/10 border-slate-400/20'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'class' => 'text-rose-200 bg-rose-500/10 border-rose-400/20'],
            ['key' => 'scheduled', 'label' => 'Scheduled', 'class' => 'text-indigo-200 bg-indigo-500/10 border-indigo-400/20'],
            ['key' => 'sent', 'label' => 'Sent', 'class' => 'text-blue-200 bg-blue-500/10 border-blue-400/20'],
        ];

        $queryWithoutPage = request()->except('page');
    @endphp

    <div class="sf-page mx-auto max-w-[1500px] px-4 py-6 space-y-5 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-orange-300">
                        Client Import Retention
                    </p>

                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-white">
                        Retention Actions
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm font-semibold text-slate-300">
                        Review suggested follow-ups before sending messages. Phase 5 only changes review statuses and editable suggestions.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                @if(\Illuminate\Support\Facades\Route::has('admin.retention-actions.report'))
                    <a
                        href="{{ route('admin.retention-actions.report') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-blue-400/20 bg-blue-500/10 px-4 text-sm font-extrabold text-blue-200 transition hover:bg-blue-500/15 hover:text-blue-100"
                    >
                        Retention Report
                    </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.batches.index'))
                    <a
                        href="{{ route('admin.clients.import.batches.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-orange-400/20 bg-orange-500/10 px-4 text-sm font-extrabold text-orange-200 transition hover:bg-orange-500/15 hover:text-orange-100"
                    >
                        Import Batches
                    </a>
                @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @foreach($summaryCards as $card)
                <a
                    href="{{ route('admin.retention-actions.index', array_merge($queryWithoutPage, ['status' => $card['key']])) }}"
                    class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:bg-slate-900 {{ $card['class'] }}"
                >
                    <div class="text-xs font-black uppercase tracking-wide">{{ $card['label'] }}</div>
                    <div class="mt-2 text-2xl font-black">{{ $summary[$card['key']] ?? 0 }}</div>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.retention-actions.index') }}" class="rounded-2xl border border-slate-800 bg-slate-900/75 p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-12">
                <label class="lg:col-span-3">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">Search</span>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Client, phone, segment, message"
                        class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white placeholder:text-slate-500 focus:border-orange-400 focus:ring-orange-400"
                    >
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">Status</span>
                    <select name="status" class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">
                        <option value="">All statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">Segment</span>
                    <select name="segment" class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">
                        <option value="">All segments</option>
                        @foreach($segments as $value => $label)
                            <option value="{{ $value }}" @selected(request('segment') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">Readiness</span>
                    <select name="readiness" class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">
                        <option value="">All readiness</option>
                        @foreach($readinessOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('readiness') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">Source</span>
                    <select name="source_type" class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">
                        <option value="">All sources</option>
                        @foreach($sourceTypes as $sourceType)
                            <option value="{{ $sourceType }}" @selected(request('source_type') === $sourceType)>{{ \Illuminate\Support\Str::headline($sourceType) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="lg:col-span-1">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">From</span>
                    <input type="date" name="from" value="{{ request('from') }}" class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">
                </label>

                <label class="lg:col-span-1">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-400">To</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="h-10 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">
                </label>

                <div class="flex items-end gap-2 lg:col-span-1">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-orange-500 px-3 text-xs font-black text-white transition hover:bg-orange-600">
                        Filter
                    </button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <label class="inline-flex h-8 items-center gap-2 rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-bold text-slate-200">
                    <input type="checkbox" name="due_soon" value="1" @checked(request()->boolean('due_soon')) class="rounded border-slate-600 bg-slate-900 text-orange-500">
                    Due next 7 days
                </label>

                <label class="inline-flex h-8 items-center gap-2 rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-bold text-slate-200">
                    <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue')) class="rounded border-slate-600 bg-slate-900 text-orange-500">
                    Overdue
                </label>

                <a href="{{ route('admin.retention-actions.index') }}" class="inline-flex h-8 items-center rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-black text-slate-200 transition hover:bg-slate-800">
                    Clear
                </a>
            </div>
        </form>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/75 shadow-sm">
            <div class="border-b border-slate-800 p-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="text-sm font-black text-white">Bulk controls</div>
                        <div class="mt-1 text-xs font-semibold text-slate-400">
                            Scheduled and sent actions are locked for review edits. Schedule drafts do not send WhatsApp messages.
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <form id="retention-bulk-form" method="POST" action="{{ route('admin.retention-actions.bulk') }}" class="flex flex-wrap items-center gap-2">
                            @csrf

                            <select name="bulk_action" class="h-9 rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-bold text-white focus:border-orange-400 focus:ring-orange-400">
                                <option value="approved">Approve selected</option>
                                <option value="skipped">Skip selected</option>
                                <option value="cancelled">Cancel selected</option>
                                <option value="pending_review">Reset selected</option>
                            </select>

                            <button
                                type="submit"
                                class="inline-flex h-9 items-center justify-center rounded-xl bg-orange-500 px-3 text-xs font-black text-white transition hover:bg-orange-600"
                                onclick="return confirm('Update selected retention actions? This will not send messages.');"
                            >
                                Apply Bulk Action
                            </button>
                        </form>

                        <form
                            id="retention-bulk-schedule-form"
                            method="POST"
                            action="{{ route('admin.retention-actions.bulk-schedule-draft') }}"
                            class="flex flex-wrap items-center gap-2 rounded-2xl border border-purple-400/20 bg-purple-500/10 p-2"
                        >
                            @csrf

                            <input
                                type="datetime-local"
                                name="scheduled_at"
                                value="{{ now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i') }}"
                                min="{{ now()->format('Y-m-d\TH:i') }}"
                                class="h-9 rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-bold text-white focus:border-purple-400 focus:ring-purple-400"
                            >

                            <button
                                type="submit"
                                class="inline-flex h-9 items-center justify-center rounded-xl bg-purple-500 px-3 text-xs font-black text-white transition hover:bg-purple-600"
                                onclick="return confirm('Create schedule drafts for selected ready approved actions? No WhatsApp messages will be sent.');"
                            >
                                Schedule Selected
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-950/70">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input id="retention-select-all" type="checkbox" class="rounded border-slate-600 bg-slate-900 text-orange-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Client</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Vehicle</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Segment</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Follow-up</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Template Readiness</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Message / Edit</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-300">Created</th>
                            <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-300">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800">
                        @forelse($retentionActions as $action)
                            @php
                                $isLocked = in_array($action->status, ['scheduled', 'sent'], true);
                                $vehicleLabel = $action->vehicle?->label
                                    ?: trim(($action->vehicle?->make?->name ?? '') . ' ' . ($action->vehicle?->model?->name ?? ''));
                                $statusLabel = $statuses[$action->status] ?? \Illuminate\Support\Str::headline($action->status);
                                $segmentLabel = $action->segment_label ?: ($segments[$action->segment_code] ?? \Illuminate\Support\Str::headline($action->segment_code));
                                $templatePreview = $action->template_preview ?? [];
                                $canScheduleDraft = $action->status === 'approved' && (($templatePreview['readiness'] ?? null) === 'ready');
                                $scheduleDefault = ($action->suggested_follow_up_date ?: now()->addDay())
                                    ->copy()
                                    ->setTime(9, 0)
                                    ->format('Y-m-d\TH:i');
                            @endphp

                            <tr class="align-top transition hover:bg-slate-950/40">
                                <td class="px-4 py-4">
                                    @unless($isLocked)
                                        <input
                                            type="checkbox"
                                            name="retention_action_ids[]"
                                            value="{{ $action->id }}"
                                            form="retention-bulk-form"
                                            class="retention-row-checkbox rounded border-slate-600 bg-slate-900 text-orange-500"
                                        >
                                    @else
                                        <span class="text-xs font-bold text-slate-500">Locked</span>
                                    @endunless
                                </td>

                                <td class="px-4 py-4">
                                    <div class="max-w-[210px]">
                                        @if($action->client && \Illuminate\Support\Facades\Route::has('admin.clients.show'))
                                            <a href="{{ route('admin.clients.show', $action->client_id) }}" class="break-words text-sm font-black text-white hover:text-orange-200">
                                                {{ $action->client->name }}
                                            </a>
                                        @else
                                            <div class="break-words text-sm font-black text-white">Client #{{ $action->client_id }}</div>
                                        @endif

                                        <div class="mt-1 break-words text-xs font-semibold text-slate-400">
                                            {{ $action->client?->phone ?: $action->client?->whatsapp ?: $action->client?->email ?: 'No contact on record' }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="max-w-[180px] break-words text-sm font-bold text-slate-200">
                                        {{ $vehicleLabel ?: 'No linked vehicle' }}
                                    </div>
                                    @if($action->vehicle?->plate_number)
                                        <div class="mt-1 text-xs font-semibold text-slate-400">
                                            Plate {{ $action->vehicle->plate_number }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="max-w-[180px] break-words text-sm font-black text-purple-200">
                                        {{ $segmentLabel }}
                                    </div>
                                    <div class="mt-1 text-xs font-semibold text-slate-500">
                                        {{ $action->segment_code }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="whitespace-nowrap text-sm font-bold text-slate-200">
                                        {{ $action->suggested_follow_up_date?->format('d M Y') ?? 'No date' }}
                                    </div>
                                    @if($action->suggested_follow_up_date)
                                        <div class="mt-1 text-xs font-semibold {{ $action->suggested_follow_up_date->isPast() ? 'text-rose-200' : 'text-slate-400' }}">
                                            {{ $action->suggested_follow_up_date->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $statusClasses[$action->status] ?? 'bg-slate-500/10 text-slate-200 ring-slate-400/20' }}">
                                        {{ $statusLabel }}
                                    </span>

                                    @if($action->scheduled_at)
                                        <div class="mt-2 text-xs font-bold text-purple-200">
                                            Drafted for {{ $action->scheduled_at->format('d M Y, h:i A') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="min-w-[260px] max-w-[360px] space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $templatePreview['readiness_class'] ?? 'bg-slate-500/10 text-slate-200 ring-slate-400/20' }}">
                                                {{ $templatePreview['readiness_label'] ?? 'Needs Review' }}
                                            </span>

                                            @if(!empty($templatePreview['template_key']))
                                                <span class="rounded-full bg-purple-500/10 px-2.5 py-1 text-xs font-black text-purple-200 ring-1 ring-purple-400/20">
                                                    {{ $templatePreview['template_key'] }}
                                                </span>
                                            @else
                                                <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-black text-amber-200 ring-1 ring-amber-400/20">
                                                    No template key
                                                </span>
                                            @endif

                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $templatePreview['template_status_class'] ?? 'bg-slate-500/10 text-slate-200 ring-slate-400/20' }}">
                                                {{ $templatePreview['template_status_label'] ?? 'Missing Template' }}
                                            </span>
                                        </div>

                                        <div class="text-xs font-semibold leading-5 text-slate-300">
                                            {{ $templatePreview['template_label'] ?? 'Unmapped segment' }}
                                            @if(!empty($templatePreview['mapped_template_name']))
                                                <span class="text-slate-500">via</span>
                                                <span class="font-black text-emerald-200">{{ $templatePreview['mapped_template_name'] }}</span>
                                            @elseif(!empty($templatePreview['mapping_event_key']))
                                                <span class="text-slate-500">via</span>
                                                <span class="font-black text-emerald-200">{{ $templatePreview['mapping_event_key'] }}</span>
                                            @endif
                                        </div>

                                        @if(($templatePreview['readiness'] ?? null) !== 'ready')
                                            <div class="rounded-xl border border-amber-400/20 bg-amber-500/10 px-3 py-2 text-xs font-bold leading-5 text-amber-200">
                                                Fallback preview is not send-ready until an approved WhatsApp template is mapped.
                                            </div>
                                        @endif

                                        @if($action->status === 'scheduled' && (($templatePreview['readiness'] ?? null) !== 'ready'))
                                            <div class="rounded-xl border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs font-bold leading-5 text-rose-200">
                                                Scheduled draft warning: this action is no longer template-ready. Unschedule or map an approved template before dispatch.
                                            </div>
                                        @endif

                                        @if(!empty($templatePreview['fallback_message']))
                                            <details class="rounded-xl border border-slate-800 bg-slate-950/70 p-3">
                                                <summary class="cursor-pointer text-xs font-black text-orange-200">
                                                    Preview Message
                                                </summary>

                                                <div class="mt-2 text-xs font-semibold leading-5 text-slate-200">
                                                    {{ $templatePreview['final_message_preview'] ?? ($templatePreview['template_preview'] ?: $templatePreview['fallback_message']) }}
                                                </div>

                                                @if(!empty($templatePreview['variables']))
                                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                                        @foreach($templatePreview['variables'] as $key => $value)
                                                            @if(filled($value))
                                                                <span class="rounded-full bg-slate-800 px-2 py-1 text-[11px] font-bold text-slate-200">
                                                                    {{ $key }}: {{ \Illuminate\Support\Str::limit($value, 32) }}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if(!empty($templatePreview['warnings']))
                                                    <div class="mt-3 space-y-1">
                                                        @foreach($templatePreview['warnings'] as $warning)
                                                            <div class="text-[11px] font-bold text-amber-200">- {{ $warning }}</div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </details>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <form id="retention-update-{{ $action->id }}" method="POST" action="{{ route('admin.retention-actions.update', $action) }}" class="space-y-2">
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="date"
                                            name="suggested_follow_up_date"
                                            value="{{ $action->suggested_follow_up_date?->toDateString() }}"
                                            @disabled($isLocked)
                                            class="h-9 w-full min-w-[170px] rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-bold text-white disabled:opacity-60 focus:border-orange-400 focus:ring-orange-400"
                                        >

                                        <textarea
                                            name="suggested_message"
                                            rows="3"
                                            maxlength="2000"
                                            @disabled($isLocked)
                                            class="w-full min-w-[260px] rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-semibold leading-5 text-slate-100 disabled:opacity-60 focus:border-orange-400 focus:ring-orange-400"
                                        >{{ $action->suggested_message }}</textarea>
                                    </form>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-bold text-slate-200">
                                        {{ \Illuminate\Support\Str::headline($action->source_type) }}
                                    </div>

                                    @if($action->source_type === 'client_import_row' && $action->importRow && \Illuminate\Support\Facades\Route::has('admin.clients.import.batches.show'))
                                        <a
                                            href="{{ route('admin.clients.import.batches.show', $action->importRow->batch_id) }}#row-{{ $action->importRow->id }}"
                                            class="mt-1 inline-flex text-xs font-black text-orange-200 hover:text-orange-100"
                                        >
                                            Import row #{{ $action->importRow->row_number }}
                                        </a>
                                    @elseif($action->source_id)
                                        <div class="mt-1 text-xs font-semibold text-slate-400">
                                            Source #{{ $action->source_id }}
                                        </div>
                                    @endif

                                    @if($action->vehicleServiceHistory)
                                        <div class="mt-1 text-xs font-semibold text-emerald-200">
                                            Service history #{{ $action->vehicle_service_history_id }}
                                        </div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-slate-300">
                                    {{ $action->created_at?->format('d M Y') }}
                                    <div class="mt-1 text-xs text-slate-500">{{ $action->created_at?->diffForHumans() }}</div>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    @if($isLocked)
                                        @if($action->status === 'scheduled')
                                            <form method="POST" action="{{ route('admin.retention-actions.unschedule-draft', $action) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-8 items-center rounded-xl bg-purple-500/10 px-3 text-xs font-black text-purple-200 ring-1 ring-purple-400/20 transition hover:bg-purple-500/15"
                                                    onclick="return confirm('Remove this schedule draft and return it to approved? No messages will be sent.');"
                                                >
                                                    Unschedule
                                                </button>
                                            </form>
                                        @else
                                            <div class="text-xs font-bold text-slate-500">
                                                Read-only
                                            </div>
                                        @endif
                                    @else
                                        <div class="flex min-w-[260px] flex-wrap justify-end gap-2">
                                            <button
                                                type="submit"
                                                form="retention-update-{{ $action->id }}"
                                                class="inline-flex h-8 items-center rounded-xl border border-slate-700 bg-slate-950 px-3 text-xs font-black text-slate-200 transition hover:bg-slate-800"
                                            >
                                                Save
                                            </button>

                                            @foreach(['approved' => 'Approve', 'skipped' => 'Skip', 'cancelled' => 'Cancel', 'pending_review' => 'Reset'] as $target => $label)
                                                @if($target !== $action->status)
                                                    <form method="POST" action="{{ route('admin.retention-actions.update', $action) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $target }}">
                                                        <input type="hidden" name="suggested_follow_up_date" value="{{ $action->suggested_follow_up_date?->toDateString() }}">
                                                        <input type="hidden" name="suggested_message" value="{{ $action->suggested_message }}">
                                                        <button type="submit" class="inline-flex h-8 items-center rounded-xl bg-orange-500/10 px-3 text-xs font-black text-orange-200 ring-1 ring-orange-400/20 transition hover:bg-orange-500/15">
                                                    {{ $label }}
                                                </button>
                                            </form>
                                        @endif
                                    @endforeach

                                            @if($canScheduleDraft)
                                                <form method="POST" action="{{ route('admin.retention-actions.schedule-draft', $action) }}" class="flex flex-wrap justify-end gap-2 rounded-xl border border-purple-400/20 bg-purple-500/10 p-2">
                                                    @csrf

                                                    <input
                                                        type="datetime-local"
                                                        name="scheduled_at"
                                                        value="{{ $scheduleDefault }}"
                                                        min="{{ now()->format('Y-m-d\TH:i') }}"
                                                        class="h-8 w-[170px] rounded-xl border border-slate-700 bg-slate-950 px-2 text-xs font-bold text-white focus:border-purple-400 focus:ring-purple-400"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-8 items-center rounded-xl bg-purple-500 px-3 text-xs font-black text-white transition hover:bg-purple-600"
                                                        onclick="return confirm('Create a schedule draft only? No WhatsApp message will be sent.');"
                                                    >
                                                        Schedule Draft
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-10 text-center text-sm font-bold text-slate-300">
                                    No retention actions match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($retentionActions->hasPages())
                <div class="border-t border-slate-800 p-4">
                    {{ $retentionActions->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('retention-select-all');
            const checkboxes = Array.from(document.querySelectorAll('.retention-row-checkbox'));
            const bulkScheduleForm = document.getElementById('retention-bulk-schedule-form');

            if (!selectAll) {
                return;
            }

            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });

            if (bulkScheduleForm) {
                bulkScheduleForm.addEventListener('submit', () => {
                    bulkScheduleForm
                        .querySelectorAll('input[name="retention_action_ids[]"]')
                        .forEach((input) => input.remove());

                    checkboxes
                        .filter((checkbox) => checkbox.checked)
                        .forEach((checkbox) => {
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'retention_action_ids[]';
                            hidden.value = checkbox.value;
                            bulkScheduleForm.appendChild(hidden);
                        });
                });
            }
        });
    </script>
@endsection
