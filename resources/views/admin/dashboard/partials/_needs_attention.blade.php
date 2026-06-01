{{-- resources/views/admin/dashboard/partials/_needs_attention.blade.php --}}

@php
    $pendingBookingsCount = (int) ($pendingBookingsCount ?? $pendingBookings ?? data_get($smartKPIs ?? [], 'pending_bookings', 0));
    $openJobsCount = (int) ($openJobsCount ?? $openJobs ?? data_get($smartKPIs ?? [], 'open_jobs', 0));
    $unpaidInvoiceCount = (int) ($unpaidInvoiceCount ?? $unpaidInvoices ?? data_get($smartKPIs ?? [], 'unpaid_invoices', 0));
    $whatsappFailedCount = (int) ($whatsappFailedCount ?? data_get($smartKPIs ?? [], 'whatsapp_failed', data_get($waDashboard ?? [], 'kpis.failed_7d', 0)));
    $followUpsDueCount = (int) ($followUpsDueCount ?? $followUpsDue ?? data_get($smartKPIs ?? [], 'followups_due', 0));
    $replies7dCount = (int) ($replies7dCount ?? data_get($waDashboard ?? [], 'kpis.replies_7d', 0));

    $unpaidAmount = (float) ($unpaidAmount ?? data_get($revenueSummary ?? [], 'pending_amount', 0));

    $attentionTotal =
        $pendingBookingsCount +
        $openJobsCount +
        $unpaidInvoiceCount +
        $whatsappFailedCount +
        $followUpsDueCount +
        $replies7dCount;

    $items = [
        [
            'label' => 'Pending Bookings',
            'value' => $pendingBookingsCount,
            'subtext' => 'Confirm, reschedule, or reject',
            'route' => 'admin.bookings.index',
            'tone' => 'blue',
        ],
        [
            'label' => 'Open Jobs',
            'value' => $openJobsCount,
            'subtext' => 'Jobs not yet completed',
            'route' => 'admin.jobs.index',
            'tone' => 'slate',
        ],
        [
            'label' => 'Unpaid Invoices',
            'value' => $unpaidInvoiceCount,
            'subtext' => 'AED ' . number_format($unpaidAmount, 2) . ' pending',
            'route' => 'admin.invoices.index',
            'tone' => 'orange',
        ],
        [
            'label' => 'WhatsApp Failed',
            'value' => $whatsappFailedCount,
            'subtext' => 'Failed messages in last 7 days',
            'route' => 'admin.inbox.index',
            'tone' => 'red',
        ],
        [
            'label' => 'Follow-ups Due',
            'value' => $followUpsDueCount,
            'subtext' => 'Due this week',
            'route' => 'admin.communication-logs.index',
            'tone' => 'green',
        ],
        [
            'label' => 'Replies 7d',
            'value' => $replies7dCount,
            'subtext' => 'Customer WhatsApp replies',
            'route' => 'admin.inbox.index',
            'tone' => 'slate',
        ],
    ];
@endphp

<style>
    html[data-theme="light"] .sf-attention-card {
        background: #ffffff !important;
        border-color: #d9e1ec !important;
    }

    html[data-theme="light"] .sf-attention-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-attention-subtitle {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-attention-value {
        color: #020617 !important;
    }

    html[data-theme="light"] .sf-attention-muted {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-tone-blue {
        color: #2563eb !important;
    }

    html[data-theme="light"] .sf-tone-orange {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-tone-red {
        color: #dc2626 !important;
    }

    html[data-theme="light"] .sf-tone-green {
        color: #059669 !important;
    }

    html[data-theme="light"] .sf-tone-slate {
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-attention-alert-pill {
        background: #fff7ed !important;
        border-color: #fed7aa !important;
        color: #ea580c !important;
    }
</style>

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="sf-attention-title text-base font-bold text-white">
                Needs Attention
            </h2>

            <p class="sf-attention-subtitle mt-1 text-xs text-slate-400">
                Action items that need review from the garage team.
            </p>
        </div>

        <span class="sf-attention-alert-pill rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-bold text-orange-300">
            {{ $attentionTotal }} alerts
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($items as $item)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($item['route']);

                $toneClass = match ($item['tone']) {
                    'blue' => 'sf-tone-blue text-blue-300',
                    'orange' => 'sf-tone-orange text-orange-300',
                    'red' => 'sf-tone-red text-red-300',
                    'green' => 'sf-tone-green text-emerald-300',
                    default => 'sf-tone-slate text-slate-200',
                };

                $cardTint = match ($item['tone']) {
                    'blue' => 'bg-blue-500/10 border-blue-400/20',
                    'orange' => 'bg-orange-500/10 border-orange-400/20',
                    'red' => 'bg-red-500/10 border-red-400/20',
                    'green' => 'bg-emerald-500/10 border-emerald-400/20',
                    default => 'bg-slate-800/50 border-slate-700',
                };
            @endphp

            @if ($hasRoute)
                <a
                    href="{{ route($item['route']) }}"
                    class="sf-attention-card group rounded-2xl border {{ $cardTint }} p-4 transition hover:-translate-y-0.5 hover:border-orange-400/40 hover:shadow-lg"
                >
                    <p class="text-sm font-extrabold {{ $toneClass }}">
                        {{ $item['label'] }}
                    </p>

                    <p class="sf-attention-value mt-3 text-3xl font-black text-white">
                        {{ $item['value'] }}
                    </p>

                    <p class="sf-attention-muted mt-2 text-xs font-semibold leading-5 text-slate-400">
                        {{ $item['subtext'] }}
                    </p>
                </a>
            @else
                <div class="sf-attention-card rounded-2xl border {{ $cardTint }} p-4">
                    <p class="text-sm font-extrabold {{ $toneClass }}">
                        {{ $item['label'] }}
                    </p>

                    <p class="sf-attention-value mt-3 text-3xl font-black text-white">
                        {{ $item['value'] }}
                    </p>

                    <p class="sf-attention-muted mt-2 text-xs font-semibold leading-5 text-slate-400">
                        {{ $item['subtext'] }}
                    </p>
                </div>
            @endif
        @endforeach
    </div>
</div>