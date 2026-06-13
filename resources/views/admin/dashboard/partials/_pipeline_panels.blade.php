{{-- resources/views/admin/dashboard/partials/_pipeline_panels.blade.php --}}

@php
    $toNumber = function ($value, $default = 0) {
        if ($value instanceof \Illuminate\Support\Collection) {
            return (float) $value->sum(function ($item) {
                if (is_array($item)) {
                    return $item['amount']
                        ?? $item['total']
                        ?? $item['paid_amount']
                        ?? $item['invoice_amount']
                        ?? $item['grand_total']
                        ?? 0;
                }

                if (is_object($item)) {
                    return $item->amount
                        ?? $item->total
                        ?? $item->paid_amount
                        ?? $item->invoice_amount
                        ?? $item->grand_total
                        ?? 0;
                }

                return is_numeric($item) ? $item : 0;
            });
        }

        if (is_array($value)) {
            return (float) collect($value)->sum(function ($item) {
                if (is_array($item)) {
                    return $item['amount']
                        ?? $item['total']
                        ?? $item['paid_amount']
                        ?? $item['invoice_amount']
                        ?? $item['grand_total']
                        ?? 0;
                }

                if (is_object($item)) {
                    return $item->amount
                        ?? $item->total
                        ?? $item->paid_amount
                        ?? $item->invoice_amount
                        ?? $item->grand_total
                        ?? 0;
                }

                return is_numeric($item) ? $item : 0;
            });
        }

        return is_numeric($value) ? (float) $value : (float) $default;
    };

    $normalizeLabel = function ($label) {
        return str((string) $label)
            ->replace('_', ' ')
            ->title()
            ->toString();
    };

    $formatCount = function ($count) {
        if ($count instanceof \Illuminate\Support\Collection) {
            return $count->count();
        }

        if (is_array($count)) {
            return count($count);
        }

        return is_numeric($count) ? (int) $count : 0;
    };

    $leadPipeline = $leadPipeline ?? [
        'New' => $newLeadsCount ?? 0,
        'Attempting Contact' => $attemptingContactLeadsCount ?? 0,
        'Contact on Hold' => $contactOnHoldLeadsCount ?? 0,
        'Qualified' => $qualifiedLeadsCount ?? 0,
        'Disqualified' => $disqualifiedLeadsCount ?? 0,
    ];

    $opportunityPipeline = $opportunityPipeline ?? [
        'New' => $newOpportunitiesCount ?? 0,
        'Attempting Contact' => $attemptingContactOpportunitiesCount ?? 0,
        'Appointment' => $appointmentOpportunitiesCount ?? 0,
        'Offer' => $offerOpportunitiesCount ?? 0,
        'Closed Won' => $closedWonOpportunitiesCount ?? 0,
        'Closed Lost' => $closedLostOpportunitiesCount ?? 0,
    ];

    $dashboardLeadFilters = collect($dashboardFilters ?? request()->only([
        'date_range',
        'from_date',
        'to_date',
        'lead_source',
        'assigned_user',
        'service_type',
        'customer_type',
    ]))->filter(fn ($value) => filled($value) && $value !== 'all')->all();

    $leadRoute = \Illuminate\Support\Facades\Route::has('admin.leads.index')
        ? route('admin.leads.index', $dashboardLeadFilters)
        : '#';

    $opportunityRoute = \Illuminate\Support\Facades\Route::has('admin.opportunities.index')
        ? route('admin.opportunities.index')
        : '#';

    $paidThisMonthRaw = $paidThisMonth ?? $monthlyRevenue ?? $revenueThisMonth ?? 0;
    $pendingAmountRaw = $pendingAmount ?? $unpaidAmount ?? $pendingInvoiceAmount ?? 0;
    $averageInvoiceRaw = $averageInvoice ?? $avgInvoiceAmount ?? 0;

    $paidThisMonthValue = $toNumber($paidThisMonthRaw);
    $pendingAmountValue = $toNumber($pendingAmountRaw);
    $averageInvoiceValue = $toNumber($averageInvoiceRaw);
    $dashboardPeriodLabel = $dashboardPeriodLabel ?? 'This Month';

    $unpaidInvoiceCount = $unpaidInvoiceCount ?? $unpaidInvoicesCount ?? 0;

    if ($unpaidInvoiceCount instanceof \Illuminate\Support\Collection) {
        $unpaidInvoiceCount = $unpaidInvoiceCount->count();
    }

    if (is_array($unpaidInvoiceCount)) {
        $unpaidInvoiceCount = count($unpaidInvoiceCount);
    }
@endphp

<div class="grid grid-cols-1 gap-3 xl:grid-cols-3">

    {{-- Lead Pipeline --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold tracking-tight text-white">
                    Lead Pipeline
                </h2>

                <p class="mt-1 text-xs font-medium text-slate-400">
                    Lead status breakdown
                </p>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('admin.leads.index'))
                <a
                    href="{{ $leadRoute }}"
                    class="text-xs font-black text-orange-400 transition hover:text-orange-300"
                >
                    View
                </a>
            @endif
        </div>

        <div class="space-y-2.5">
            @forelse ($leadPipeline as $status => $count)
                @php
                    $count = $formatCount($count);
                @endphp

                <a
                    href="{{ $leadRoute }}"
                    class="block rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5 transition hover:border-orange-400/40 hover:bg-slate-950"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-white">
                                {{ $normalizeLabel($status) }}
                            </p>
                        </div>

                        <span class="sf-tone-blue inline-flex min-w-8 items-center justify-center rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-black text-blue-300">
                            {{ $count }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-4 text-center">
                    <p class="text-sm font-semibold text-slate-500">
                        No lead pipeline data
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Opportunity Pipeline --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold tracking-tight text-white">
                    Opportunity Pipeline
                </h2>

                <p class="mt-1 text-xs font-medium text-slate-400">
                    Opportunity stage breakdown
                </p>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.index'))
                <a
                    href="{{ $opportunityRoute }}"
                    class="text-xs font-black text-orange-400 transition hover:text-orange-300"
                >
                    View
                </a>
            @endif
        </div>

        <div class="space-y-2.5">
            @forelse ($opportunityPipeline as $stage => $count)
                @php
                    $count = $formatCount($count);
                @endphp

                <a
                    href="{{ $opportunityRoute }}"
                    class="block rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5 transition hover:border-orange-400/40 hover:bg-slate-950"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-white">
                                {{ $normalizeLabel($stage) }}
                            </p>
                        </div>

                        <span class="sf-tone-orange inline-flex min-w-8 items-center justify-center rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-black text-orange-400">
                            {{ $count }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-4 text-center">
                    <p class="text-sm font-semibold text-slate-500">
                        No opportunity pipeline data
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Revenue Snapshot --}}
    <div class="rounded-2xl border border-blue-400/30 bg-gradient-to-br from-blue-700 via-blue-600 to-orange-500 p-4 shadow-sm">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold tracking-tight text-white">
                    Revenue Snapshot
                </h2>

                <p class="mt-1 text-xs font-medium text-blue-100/80">
                    Invoice and payment summary
                </p>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('admin.invoices.index'))
                <a
                    href="{{ route('admin.invoices.index') }}"
                    class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white transition hover:bg-white/20"
                >
                    View
                </a>
            @endif
        </div>

        <div class="space-y-2.5">
            <div class="flex items-center justify-between rounded-xl bg-white/10 px-3 py-2.5">
                <span class="text-sm font-extrabold text-white/90">
                    Paid - {{ $dashboardPeriodLabel }}
                </span>

                <span class="text-sm font-black text-white">
                    AED {{ number_format($paidThisMonthValue, 2) }}
                </span>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-white/10 px-3 py-2.5">
                <span class="text-sm font-extrabold text-white/90">
                    Pending amount
                </span>

                <span class="text-sm font-black text-white">
                    AED {{ number_format($pendingAmountValue, 2) }}
                </span>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-white/10 px-3 py-2.5">
                <span class="text-sm font-extrabold text-white/90">
                    Unpaid invoices
                </span>

                <span class="text-sm font-black text-white">
                    {{ $unpaidInvoiceCount }}
                </span>
            </div>

            <div class="flex items-center justify-between rounded-xl bg-white/10 px-3 py-2.5">
                <span class="text-sm font-extrabold text-white/90">
                    Average invoice
                </span>

                <span class="text-sm font-black text-white">
                    AED {{ number_format($averageInvoiceValue, 2) }}
                </span>
            </div>
        </div>
    </div>

</div>
