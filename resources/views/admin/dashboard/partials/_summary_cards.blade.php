{{-- resources/views/admin/dashboard/partials/_summary_cards.blade.php --}}

@php
    $toCount = function ($value, $default = 0) {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->count();
        }

        if ($value instanceof \Illuminate\Pagination\AbstractPaginator) {
            return $value->total();
        }

        if (is_array($value)) {
            return count($value);
        }

        return is_numeric($value) ? (int) $value : (int) $default;
    };

    $toMoney = function ($value, $default = 0) {
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

    $leadCount = $toCount($leadCount ?? $leadsCount ?? $totalLeads ?? $leads ?? $recentLeads ?? 0);
    $opportunityCount = $toCount($opportunityCount ?? $opportunitiesCount ?? $totalOpportunities ?? $opportunities ?? $recentOpportunities ?? 0);
    $bookingCount = $toCount($bookingCount ?? $bookingsCount ?? $totalBookings ?? $bookings ?? $recentBookings ?? 0);
    $jobCount = $toCount($jobCount ?? $jobsCount ?? $totalJobs ?? $jobs ?? 0);
    $unpaidInvoiceCount = $toCount($unpaidInvoiceCount ?? $unpaidInvoicesCount ?? $totalUnpaidInvoices ?? $unpaidInvoices ?? 0);

    $monthlyRevenueRaw = $monthlyRevenue
        ?? $revenueThisMonth
        ?? $paidThisMonth
        ?? $paidInvoicesThisMonth
        ?? 0;

    $monthlyRevenueValue = $toMoney($monthlyRevenueRaw);
    $dashboardPeriodLabel = $dashboardPeriodLabel ?? 'This Month';
    $dashboardContextLabel = $dashboardContextLabel ?? $dashboardPeriodLabel;
    $dashboardLeadFilters = collect($dashboardFilters ?? request()->only([
        'date_range',
        'from_date',
        'to_date',
        'lead_source',
        'assigned_user',
        'service_type',
        'customer_type',
    ]))->filter(fn ($value) => filled($value) && $value !== 'all')->all();

    $moduleTone = [
        'blue' => [
            'accent' => 'text-blue-400',
            'iconBg' => 'bg-blue-500/15 ring-blue-400/20',
            'iconText' => 'text-blue-300',
            'border' => 'border-blue-500/25',
            'hover' => 'hover:border-blue-400/50',
        ],
        'purple' => [
            'accent' => 'text-purple-400',
            'iconBg' => 'bg-purple-500/15 ring-purple-400/20',
            'iconText' => 'text-purple-300',
            'border' => 'border-purple-500/25',
            'hover' => 'hover:border-purple-400/50',
        ],
        'indigo' => [
            'accent' => 'text-indigo-400',
            'iconBg' => 'bg-indigo-500/15 ring-indigo-400/20',
            'iconText' => 'text-indigo-300',
            'border' => 'border-indigo-500/25',
            'hover' => 'hover:border-indigo-400/50',
        ],
        'emerald' => [
            'accent' => 'text-emerald-400',
            'iconBg' => 'bg-emerald-500/15 ring-emerald-400/20',
            'iconText' => 'text-emerald-300',
            'border' => 'border-emerald-500/25',
            'hover' => 'hover:border-emerald-400/50',
        ],
        'rose' => [
            'accent' => 'text-rose-400',
            'iconBg' => 'bg-rose-500/15 ring-rose-400/20',
            'iconText' => 'text-rose-300',
            'border' => 'border-rose-500/25',
            'hover' => 'hover:border-rose-400/50',
        ],
        'orange' => [
            'accent' => 'text-orange-400',
            'iconBg' => 'bg-orange-500/15 ring-orange-400/20',
            'iconText' => 'text-orange-300',
            'border' => 'border-orange-500/25',
            'hover' => 'hover:border-orange-400/50',
        ],
    ];

    $cards = [
        [
            'label' => 'Leads',
            'value' => $leadCount,
            'subtext' => $dashboardContextLabel,
            'route' => 'admin.leads.index',
            'tone' => 'blue',
            'iconPath' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
            'params' => $dashboardLeadFilters ?? [],
            'wide' => false,
        ],
        [
            'label' => 'Opportunities',
            'value' => $opportunityCount,
            'subtext' => $dashboardContextLabel,
            'route' => 'admin.opportunities.index',
            'tone' => 'purple',
            'iconPath' => 'M12 2v4M12 18v4M2 12h4M18 12h4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M19.07 4.93l-2.83 2.83M7.76 16.24l-2.83 2.83M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
            'params' => [],
            'wide' => false,
        ],
        [
            'label' => 'Bookings',
            'value' => $bookingCount,
            'subtext' => $dashboardContextLabel,
            'route' => 'admin.bookings.index',
            'tone' => 'indigo',
            'iconPath' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01',
            'params' => [],
            'wide' => false,
        ],
        [
            'label' => 'Jobs',
            'value' => $jobCount,
            'subtext' => $dashboardContextLabel,
            'route' => 'admin.jobs.index',
            'tone' => 'emerald',
            'iconPath' => 'M14.7 6.3a4 4 0 0 0-5 5L3 18v3h3l6.7-6.7a4 4 0 0 0 5-5l-2.4 2.4-2.8-2.8 2.2-2.6Z',
            'params' => [],
            'wide' => false,
        ],
        [
            'label' => 'Unpaid Invoices',
            'value' => $unpaidInvoiceCount,
            'subtext' => 'payments to follow up',
            'route' => 'admin.invoices.index',
            'tone' => 'rose',
            'iconPath' => 'M7 3h10a2 2 0 0 1 2 2v16l-3-2-2 2-2-2-2 2-2-2 3 2V5a2 2 0 0 1 2-2M9 8h6M9 12h6M9 16h4',
            'params' => [],
            'wide' => true,
        ],
        [
            'label' => 'Monthly Revenue',
            'value' => 'AED ' . number_format($monthlyRevenueValue, 2),
            'subtext' => 'paid invoices / ' . $dashboardContextLabel,
            'route' => 'admin.invoices.index',
            'tone' => 'orange',
            'iconDirhamMark' => true,
            'params' => [],
            'wide' => true,
        ],
    ];

    $topCards = collect($cards)->where('wide', false);
    $wideCards = collect($cards)->where('wide', true);
@endphp

<style>
    .sf-dashboard-kpi-card {
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-dashboard-kpi-label {
        color: #cbd5e1;
    }

    .sf-dashboard-kpi-subtext,
    .sf-dashboard-kpi-link {
        color: #94a3b8;
    }

    html[data-theme="light"] .sf-dashboard-kpi-card {
        background: #ffffff !important;
        border-color: #d9e1ec !important;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06) !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-subtext,
    html[data-theme="light"] .sf-dashboard-kpi-link {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-blue .sf-dashboard-kpi-value,
    html[data-theme="light"] .sf-dashboard-kpi-blue .sf-dashboard-kpi-icon {
        color: #2563eb !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-purple .sf-dashboard-kpi-value,
    html[data-theme="light"] .sf-dashboard-kpi-purple .sf-dashboard-kpi-icon {
        color: #7c3aed !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-indigo .sf-dashboard-kpi-value,
    html[data-theme="light"] .sf-dashboard-kpi-indigo .sf-dashboard-kpi-icon {
        color: #4f46e5 !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-emerald .sf-dashboard-kpi-value,
    html[data-theme="light"] .sf-dashboard-kpi-emerald .sf-dashboard-kpi-icon {
        color: #059669 !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-rose .sf-dashboard-kpi-value,
    html[data-theme="light"] .sf-dashboard-kpi-rose .sf-dashboard-kpi-icon {
        color: #e11d48 !important;
    }

    html[data-theme="light"] .sf-dashboard-kpi-orange .sf-dashboard-kpi-value,
    html[data-theme="light"] .sf-dashboard-kpi-orange .sf-dashboard-kpi-icon {
        color: #ea580c !important;
    }
</style>

@php
    $renderCard = function (array $card) use ($moduleTone) {
        $tone = $card['tone'] ?? 'blue';
        $colors = $moduleTone[$tone] ?? $moduleTone['blue'];
        $hasRoute = \Illuminate\Support\Facades\Route::has($card['route']);
        $params = $card['params'] ?? [];
@endphp
        <div class="sf-dashboard-kpi-card sf-dashboard-kpi-{{ $tone }} rounded-2xl border {{ $colors['border'] }} p-4 shadow-sm transition {{ $colors['hover'] }}">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="sf-dashboard-kpi-label text-sm font-semibold">
                        {{ $card['label'] }}
                    </p>

                    <p class="sf-dashboard-kpi-value mt-1.5 truncate text-2xl font-extrabold {{ $colors['accent'] }}">
                        {{ $card['value'] }}
                    </p>

                    <p class="sf-dashboard-kpi-subtext mt-1 text-xs font-medium">
                        {{ $card['subtext'] }}
                    </p>
                </div>

                <div class="sf-dashboard-kpi-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ring-1 {{ $colors['iconBg'] }} {{ $colors['iconText'] }}">
                    @if (! empty($card['iconDirhamMark']))
                        <svg viewBox="0 0 32 32" aria-label="UAE Dirham" role="img" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 7.5h8.3c5 0 8.7 3.6 8.7 8.5s-3.7 8.5-8.7 8.5H8" />
                            <path d="M12 7.5v17" />
                            <path d="M5 12h21" />
                            <path d="M5 20h21" />
                        </svg>
                    @else
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $card['iconPath'] }}"></path>
                        </svg>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                @if ($hasRoute)
                    <a href="{{ route($card['route'], $params) }}" class="sf-dashboard-kpi-link text-xs font-bold text-orange-400 transition hover:text-orange-300">
                        View details
                    </a>
                @else
                    <span class="sf-dashboard-kpi-link text-xs font-semibold opacity-60">
                        View details
                    </span>
                @endif
            </div>
        </div>
@php
    };
@endphp

<div class="space-y-3">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($topCards as $card)
            @php($renderCard($card))
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        @foreach ($wideCards as $card)
            @php($renderCard($card))
        @endforeach
    </div>
</div>
