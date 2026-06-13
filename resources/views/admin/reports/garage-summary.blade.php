{{-- resources/views/admin/reports/garage-summary.blade.php --}}

@extends('layouts.app')

@section('title', 'Garage Summary Report')

@section('content')
    @php
        $sections = $summary['sections'];
        $template = $summary['template'];

        $metricCards = [
            ['label' => 'Bookings Today', 'value' => $sections['operations']['bookings_today'] ?? 0, 'class' => 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-400/20 dark:bg-indigo-500/10 dark:text-indigo-200'],
            ['label' => 'Bookings Tomorrow', 'value' => $sections['operations']['bookings_tomorrow'] ?? 0, 'class' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200'],
            ['label' => 'New Leads', 'value' => $sections['leads']['new_leads'] ?? 0, 'class' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-400/20 dark:bg-sky-500/10 dark:text-sky-200'],
            ['label' => 'Open Opportunities', 'value' => $sections['opportunities']['open_opportunities'] ?? 0, 'class' => 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-400/20 dark:bg-purple-500/10 dark:text-purple-200'],
            ['label' => 'Jobs In Progress', 'value' => $sections['jobs']['in_progress'] ?? 0, 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'],
            ['label' => 'Unpaid Invoices', 'value' => $sections['invoices']['unpaid'] ?? 0, 'class' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200'],
            ['label' => 'Paid Revenue', 'value' => 'AED ' . number_format((float) ($sections['invoices']['revenue_paid'] ?? 0), 2), 'class' => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200'],
            ['label' => 'Retention Due', 'value' => $sections['retention']['upcoming_follow_ups'] ?? 0, 'class' => 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-400/20 dark:bg-yellow-500/10 dark:text-yellow-200'],
        ];

        $statusLabel = fn ($value) => \Illuminate\Support\Str::headline((string) $value);
    @endphp

    <div class="sf-page mx-auto max-w-[1500px] px-4 py-6 space-y-5 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">
                        Garage Reporting
                    </p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                        Garage Summary Report
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                        Operational, revenue, retention, and WhatsApp summary preview for {{ $summary['period_label'] }}. Preview only; no WhatsApp message is sent from this page.
                    </p>
                </div>

                <a
                    href="{{ route('admin.retention-actions.report') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 text-sm font-extrabold text-blue-700 transition hover:bg-blue-100 hover:text-blue-800 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/15 dark:hover:text-blue-100"
                >
                    Retention Report
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports.garage-summary') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80" data-index-filter-panel>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-black text-slate-950 dark:text-white">Filters</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Choose the summary period and anchor date.</p>
                </div>
                <button type="button" class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" data-index-filter-toggle aria-expanded="false">
                    Show Filters
                </button>
            </div>

            <div class="mt-4 hidden grid gap-3 md:grid-cols-12" data-index-filter-body>
                <label class="md:col-span-3">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Period</span>
                    <select name="period" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach($periodOptions as $value => $label)
                            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="md:col-span-3">
                    <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Anchor Date</span>
                    <input
                        type="date"
                        name="date"
                        value="{{ $anchorDate->toDateString() }}"
                        class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-orange-400 focus:ring-orange-400 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </label>

                <div class="flex items-end md:col-span-2">
                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-black text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600">
                        Preview
                    </button>
                </div>

                <div class="flex items-end md:col-span-4">
                    <div class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-bold text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200">
                        WhatsApp summary template preview only. No schedule, queue, campaign, journey, API call, or send is created.
                    </div>
                </div>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
            @foreach($metricCards as $card)
                <div class="rounded-2xl border p-4 {{ $card['class'] }}">
                    <div class="text-xs font-black uppercase tracking-wide">{{ $card['label'] }}</div>
                    <div class="mt-2 text-2xl font-black">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Daily Operations</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($sections['operations'] as $label => $value)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-500">{{ $statusLabel($label) }}</div>
                            <div class="mt-1 text-xl font-black text-slate-950 dark:text-white">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Leads & Opportunities</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">New Leads</div>
                        <div class="mt-1 text-xl font-black text-sky-700 dark:text-sky-200">{{ $sections['leads']['new_leads'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Open Opportunities</div>
                        <div class="mt-1 text-xl font-black text-purple-700 dark:text-purple-200">{{ $sections['opportunities']['open_opportunities'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Closed Won</div>
                        <div class="mt-1 text-xl font-black text-emerald-700 dark:text-emerald-200">{{ $sections['opportunities']['closed_won'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Closed Lost</div>
                        <div class="mt-1 text-xl font-black text-rose-700 dark:text-rose-200">{{ $sections['opportunities']['closed_lost'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-500">Lead Statuses</div>
                        <div class="space-y-2">
                            @forelse(($sections['leads']['by_status'] ?? []) as $status => $count)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950/60">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $statusLabel($status) }}</span>
                                    <span class="font-black text-sky-700 dark:text-sky-200">{{ $count }}</span>
                                </div>
                            @empty
                                <div class="text-sm font-bold text-slate-500 dark:text-slate-500">No lead status data yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-500">Opportunity Stages</div>
                        <div class="space-y-2">
                            @forelse(($sections['opportunities']['by_stage'] ?? []) as $stage => $count)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950/60">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $statusLabel($stage) }}</span>
                                    <span class="font-black text-purple-700 dark:text-purple-200">{{ $count }}</span>
                                </div>
                            @empty
                                <div class="text-sm font-bold text-slate-500">No opportunity stage data yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Jobs & Service</h2>
                <div class="mt-4 space-y-2">
                    @foreach(['pending', 'in_progress', 'completed'] as $key)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950/60">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $statusLabel($key) }}</span>
                            <span class="font-black text-emerald-700 dark:text-emerald-200">{{ $sections['jobs'][$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs font-semibold text-slate-500 dark:text-slate-500">Overdue job reporting needs a dedicated job due date before it can be reliable.</p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Invoice & Revenue</h2>
                <div class="mt-4 space-y-2">
                    @foreach(['created', 'paid', 'unpaid', 'overdue'] as $key)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950/60">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $statusLabel($key) }}</span>
                            <span class="font-black text-orange-700 dark:text-orange-200">{{ $sections['invoices'][$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                    <div class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-sm dark:border-orange-400/20 dark:bg-orange-500/10">
                        <div class="font-bold text-orange-700 dark:text-orange-200">Revenue Paid</div>
                        <div class="mt-1 text-xl font-black text-orange-800 dark:text-orange-100">AED {{ number_format((float) ($sections['invoices']['revenue_paid'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Retention</h2>
                <div class="mt-4 space-y-2">
                    @foreach(['pending_review', 'approved', 'scheduled', 'sent', 'skipped', 'overdue', 'upcoming_follow_ups'] as $key)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950/60">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $statusLabel($key) }}</span>
                            <span class="font-black text-yellow-700 dark:text-yellow-200">{{ $sections['retention'][$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">WhatsApp / Inbox Health</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach(['messages_sent', 'failed_messages', 'inbound_replies', 'unread_conversations'] as $key)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                            <div class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $statusLabel($key) }}</div>
                            <div class="mt-1 text-xl font-black text-blue-700 dark:text-blue-200">{{ $sections['whatsapp'][$key] ?? 'N/A' }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm dark:border-orange-400/20 dark:bg-orange-500/10">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-orange-900 dark:text-orange-100">WhatsApp Template Preview</h2>
                        <p class="mt-1 text-sm font-semibold text-orange-700 dark:text-orange-200/80">{{ $template['key'] }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-orange-200 bg-white/70 px-3 py-1 text-xs font-black text-orange-700 dark:border-orange-300/30 dark:bg-orange-400/10 dark:text-orange-100">
                            {{ $template['local_template_exists'] ? 'Local Exists' : 'Missing Local Template' }}
                        </span>
                        <span class="rounded-full border border-orange-200 bg-white/70 px-3 py-1 text-xs font-black text-orange-700 dark:border-orange-300/30 dark:bg-orange-400/10 dark:text-orange-100">
                            {{ $template['mapped'] ? 'Mapped' : 'Not Mapped' }}
                        </span>
                        <span class="rounded-full border border-orange-200 bg-white/70 px-3 py-1 text-xs font-black text-orange-700 dark:border-orange-300/30 dark:bg-orange-400/10 dark:text-orange-100">
                            {{ $template['active_or_approved'] ? 'Approved/Active' : 'Not Send-Ready' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-orange-200 bg-white p-4 dark:border-orange-300/20 dark:bg-slate-950/70">
                    <div class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-200/70">Preview Message</div>
                    <p class="mt-2 text-sm font-bold leading-relaxed text-slate-800 dark:text-orange-50">{{ $template['preview'] }}</p>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                    <div class="rounded-xl border border-orange-200 bg-white/70 p-3 text-xs font-bold text-orange-700 dark:border-orange-300/20 dark:bg-orange-400/10 dark:text-orange-100">
                        Event key<br><span class="font-black">{{ $template['event_key'] }}</span>
                    </div>
                    <div class="rounded-xl border border-orange-200 bg-white/70 p-3 text-xs font-bold text-orange-700 dark:border-orange-300/20 dark:bg-orange-400/10 dark:text-orange-100">
                        Local status<br><span class="font-black">{{ $template['local_template_status'] ?: 'Missing' }}</span>
                    </div>
                    <div class="rounded-xl border border-orange-200 bg-white/70 p-3 text-xs font-bold text-orange-700 dark:border-orange-300/20 dark:bg-orange-400/10 dark:text-orange-100">
                        Meta<br><span class="font-black">Manual creation required</span>
                    </div>
                </div>
            </section>
        </div>

        @if(!empty($summary['notes']))
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Reliability Notes</h2>
                <ul class="mt-3 space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                    @foreach($summary['notes'] as $note)
                        <li class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-800 dark:bg-slate-950/60">{{ $note }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    @include('admin.partials._index_filter_chip_script')
@endsection
