{{-- resources/views/admin/dashboard/partials/_lead_flow.blade.php --}}

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

    $leadCount = $toCount($leadCount ?? $leadsCount ?? $totalLeads ?? $leads ?? $recentLeads ?? 0);
    $opportunityCount = $toCount($opportunityCount ?? $opportunitiesCount ?? $totalOpportunities ?? $opportunities ?? $recentOpportunities ?? 0);
    $bookingCount = $toCount($bookingCount ?? $bookingsCount ?? $totalBookings ?? $bookings ?? $recentBookings ?? 0);
    $jobCount = $toCount($jobCount ?? $jobsCount ?? $totalJobs ?? $jobs ?? 0);
    $invoiceCount = $toCount($invoiceCount ?? $invoicesCount ?? $totalInvoices ?? $invoices ?? 0);

    $conversionRate = function ($current, $previous) {
        if ((int) $previous <= 0) {
            return 0;
        }

        return round(((int) $current / (int) $previous) * 100);
    };

    $flowItems = [
        [
            'label' => 'Leads',
            'count' => $leadCount,
            'route' => 'admin.leads.index',
            'width' => 78,
            'bg' => 'bg-sky-400',
            'dot' => 'bg-sky-400',
            'displayRate' => '100%',
        ],
        [
            'label' => 'Opportunities',
            'count' => $opportunityCount,
            'route' => 'admin.opportunities.index',
            'width' => 68,
            'bg' => 'bg-blue-500',
            'dot' => 'bg-blue-500',
            'displayRate' => $conversionRate($opportunityCount, $leadCount) . '%',
        ],
        [
            'label' => 'Bookings',
            'count' => $bookingCount,
            'route' => 'admin.bookings.index',
            'width' => 58,
            'bg' => 'bg-indigo-500',
            'dot' => 'bg-indigo-500',
            'displayRate' => $conversionRate($bookingCount, $opportunityCount) . '%',
        ],
        [
            'label' => 'Jobs',
            'count' => $jobCount,
            'route' => 'admin.jobs.index',
            'width' => 48,
            'bg' => 'bg-violet-500',
            'dot' => 'bg-violet-500',
            'displayRate' => $conversionRate($jobCount, $bookingCount) . '%',
        ],
        [
            'label' => 'Invoices',
            'count' => $invoiceCount,
            'route' => 'admin.invoices.index',
            'width' => 40,
            'bg' => 'bg-emerald-500',
            'dot' => 'bg-emerald-500',
            'displayRate' => $conversionRate($invoiceCount, $jobCount) . '%',
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

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-bold text-white">
                Lead Flow Funnel
            </h2>

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-semibold text-slate-300">
                @foreach ($flowItems as $item)
                    <div class="inline-flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $item['dot'] }}"></span>
                        <span>{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <span class="rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1 text-xs font-bold text-blue-300">
            Funnel
        </span>
    </div>

    <div class="rounded-3xl border border-slate-800 bg-slate-950/60 p-5">
        <div class="mx-auto w-full max-w-3xl py-3">
            @foreach ($flowItems as $index => $item)
                @php
                    $hasRoute = \Illuminate\Support\Facades\Route::has($item['route']);

                    /*
                    |--------------------------------------------------------------------------
                    | Funnel Shape
                    |--------------------------------------------------------------------------
                    | Lower inset keeps the bottom layers readable.
                    */
                    $topInset = $index * 4.2;
                    $bottomInset = $topInset + 4.2;

                    $clipPath = "polygon({$topInset}% 0%, " . (100 - $topInset) . "% 0%, " . (100 - $bottomInset) . "% 100%, {$bottomInset}% 100%)";
                @endphp

                <div class="mx-auto -mb-[1px]" style="width: {{ $item['width'] }}%;">
                    @if ($hasRoute)
                        <a href="{{ route($item['route'], $item['route'] === 'admin.leads.index' ? $dashboardLeadFilters : []) }}" class="group block" title="{{ $item['label'] }}">
                            <div
                                class="relative flex h-[60px] items-center justify-center overflow-hidden {{ $item['bg'] }} px-5 transition duration-200 group-hover:brightness-110"
                                style="clip-path: {{ $clipPath }};"
                            >
                                <div class="relative z-10 flex items-center justify-center gap-3 text-center">
                                    <span class="min-w-[46px] rounded-full bg-white/20 px-4 py-1.5 text-sm font-black text-white ring-1 ring-white/20">
                                        {{ $item['count'] }}
                                    </span>

                                    <span class="min-w-[62px] rounded-full bg-slate-950/20 px-4 py-1.5 text-sm font-black text-white ring-1 ring-white/20">
                                        {{ $item['displayRate'] }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @else
                        <div
                            class="relative flex h-[60px] items-center justify-center overflow-hidden {{ $item['bg'] }} px-5"
                            style="clip-path: {{ $clipPath }};"
                            title="{{ $item['label'] }}"
                        >
                            <div class="relative z-10 flex items-center justify-center gap-3 text-center">
                                <span class="min-w-[46px] rounded-full bg-white/20 px-4 py-1.5 text-sm font-black text-white ring-1 ring-white/20">
                                    {{ $item['count'] }}
                                </span>

                                <span class="min-w-[62px] rounded-full bg-slate-950/20 px-4 py-1.5 text-sm font-black text-white ring-1 ring-white/20">
                                    {{ $item['displayRate'] }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="mt-3 text-center text-[11px] font-semibold text-slate-500">
            First value is count. Second value is conversion from previous stage.
        </p>
    </div>
</div>
