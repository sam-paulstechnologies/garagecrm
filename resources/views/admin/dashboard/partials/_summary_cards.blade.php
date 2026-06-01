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

    $topCards = [
        [
            'label' => 'Leads',
            'value' => $leadCount,
            'subtext' => 'this month',
            'route' => 'admin.leads.index',
            'accent' => 'text-blue-400',
            'iconBg' => 'bg-blue-500/10',
            'iconText' => 'text-blue-300',
            'icon' => '👥',
        ],
        [
            'label' => 'Opportunities',
            'value' => $opportunityCount,
            'subtext' => 'this month',
            'route' => 'admin.opportunities.index',
            'accent' => 'text-orange-400',
            'iconBg' => 'bg-orange-500/10',
            'iconText' => 'text-orange-300',
            'icon' => '🎯',
        ],
        [
            'label' => 'Bookings',
            'value' => $bookingCount,
            'subtext' => 'this month',
            'route' => 'admin.bookings.index',
            'accent' => 'text-blue-400',
            'iconBg' => 'bg-sky-500/10',
            'iconText' => 'text-sky-300',
            'icon' => '📅',
        ],
        [
            'label' => 'Jobs',
            'value' => $jobCount,
            'subtext' => 'this month',
            'route' => 'admin.jobs.index',
            'accent' => 'text-emerald-400',
            'iconBg' => 'bg-emerald-500/10',
            'iconText' => 'text-emerald-300',
            'icon' => '🛠',
        ],
    ];

    $wideCards = [
        [
            'label' => 'Unpaid Invoices',
            'value' => $unpaidInvoiceCount,
            'subtext' => 'payments to follow up',
            'route' => 'admin.invoices.index',
            'accent' => 'text-red-400',
            'iconBg' => 'bg-red-500/10',
            'iconText' => 'text-red-300',
            'icon' => '🧾',
        ],
        [
            'label' => 'Monthly Revenue',
            'value' => 'AED ' . number_format($monthlyRevenueValue, 2),
            'subtext' => 'paid invoices',
            'route' => 'admin.invoices.index',
            'accent' => 'text-orange-400',
            'iconBg' => 'bg-orange-500/10',
            'iconText' => 'text-orange-300',
            'icon' => '💰',
        ],
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
@endphp

<div class="space-y-4">
    {{-- Top 4 cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($topCards as $card)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($card['route']);
            @endphp

            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm transition hover:border-slate-700">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-400">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-2 text-2xl font-extrabold {{ $card['accent'] }}">
                            {{ $card['value'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $card['subtext'] }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $card['iconBg'] }} {{ $card['iconText'] }}">
                        <span class="text-base">{{ $card['icon'] }}</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between">
                    @if ($hasRoute)
                        <a href="{{ route($card['route'], $card['route'] === 'admin.leads.index' ? $dashboardLeadFilters : []) }}" class="text-xs font-semibold text-slate-300 transition hover:text-orange-300">
                            View details
                        </a>
                    @else
                        <span class="text-xs font-semibold text-slate-600">
                            View details
                        </span>
                    @endif

                    <span class="text-xs text-slate-600">→</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Second row: 50% + 50% --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($wideCards as $card)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($card['route']);
            @endphp

            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm transition hover:border-slate-700">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-400">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-2 text-2xl font-extrabold {{ $card['accent'] }}">
                            {{ $card['value'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $card['subtext'] }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $card['iconBg'] }} {{ $card['iconText'] }}">
                        <span class="text-base">{{ $card['icon'] }}</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between">
                    @if ($hasRoute)
                        <a href="{{ route($card['route']) }}" class="text-xs font-semibold text-slate-300 transition hover:text-orange-300">
                            View details
                        </a>
                    @else
                        <span class="text-xs font-semibold text-slate-600">
                            View details
                        </span>
                    @endif

                    <span class="text-xs text-slate-600">→</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
