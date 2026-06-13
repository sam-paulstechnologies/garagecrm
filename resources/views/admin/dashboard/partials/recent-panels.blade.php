{{-- resources/views/admin/dashboard/partials/recent-panels.blade.php --}}

@php
    use Carbon\Carbon;

    $recentLeads = collect($recentLeads ?? $latestLeads ?? [])->take(5);
    $recentBookings = collect($recentBookings ?? $latestBookings ?? [])->take(5);
    $recentOpportunities = collect($recentOpportunities ?? $latestOpportunities ?? [])->take(5);

    $humanize = function ($value) {
        return str((string) $value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    };

    $recentMeta = function ($item, string $label, string $field) use ($humanize) {
        $parts = [];
        $state = $item->{$field} ?? null;

        if (filled($state)) {
            $parts[] = $label . ': ' . $humanize($state);
        }

        $createdAt = $item->created_at ?? null;

        if ($createdAt) {
            $created = $createdAt instanceof Carbon ? $createdAt : Carbon::parse($createdAt);
            $parts[] = $created->format('d M Y');
            $parts[] = $created->diffForHumans();
        }

        return implode(' | ', $parts);
    };

    $panels = [
        [
            'title' => 'Recent Leads',
            'items' => $recentLeads,
            'route' => 'admin.leads.index',
            'detailRoute' => 'admin.leads.show',
            'empty' => 'No recent leads yet.',
            'primary' => fn ($item) => $item->name ?? $item->full_name ?? $item->customer_name ?? $item->lead_name ?? 'Lead',
            'secondary' => fn ($item) => $recentMeta($item, 'Lead Status', 'status'),
        ],
        [
            'title' => 'Recent Opportunities',
            'items' => $recentOpportunities,
            'route' => 'admin.opportunities.index',
            'detailRoute' => 'admin.opportunities.show',
            'empty' => 'No recent opportunities yet.',
            'primary' => fn ($item) => $item->title ?? $item->name ?? $item->opportunity_name ?? 'Opportunity',
            'secondary' => fn ($item) => $recentMeta($item, 'Opportunity Stage', 'stage'),
        ],
        [
            'title' => 'Recent Bookings',
            'items' => $recentBookings,
            'route' => 'admin.bookings.index',
            'detailRoute' => 'admin.bookings.show',
            'empty' => 'No recent bookings yet.',
            'primary' => fn ($item) => $item->name ?? $item->customer_name ?? $item->title ?? 'Booking',
            'secondary' => fn ($item) => $recentMeta($item, 'Booking Status', 'status'),
        ],
    ];
@endphp

<div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
    @foreach ($panels as $panel)
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-white">
                        {{ $panel['title'] }}
                    </h2>
                    <p class="mt-1 text-xs text-slate-400">
                        Latest 5 records
                    </p>
                </div>

                @if (\Illuminate\Support\Facades\Route::has($panel['route']))
                    <a href="{{ route($panel['route']) }}" class="text-xs font-black text-orange-400 transition hover:text-orange-300">
                        View All
                    </a>
                @endif
            </div>

            <div class="space-y-2.5">
                @forelse ($panel['items'] as $item)
                    @php
                        $primary = $panel['primary']($item);
                        $secondary = $panel['secondary']($item);
                        $hasDetailRoute = !empty($item->id) && \Illuminate\Support\Facades\Route::has($panel['detailRoute']);
                    @endphp

                    @if ($hasDetailRoute)
                        <a
                            href="{{ route($panel['detailRoute'], $item->id) }}"
                            class="block rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5 transition hover:border-orange-400/50 hover:bg-slate-900"
                        >
                            <p class="truncate text-sm font-bold text-white">
                                {{ $primary }}
                            </p>

                            @if ($secondary)
                                <p class="mt-1 truncate text-xs font-semibold text-slate-400">
                                    {{ $secondary }}
                                </p>
                            @endif
                        </a>
                    @else
                        <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5">
                            <p class="truncate text-sm font-bold text-white">
                                {{ $primary }}
                            </p>

                            @if ($secondary)
                                <p class="mt-1 truncate text-xs font-semibold text-slate-400">
                                    {{ $secondary }}
                                </p>
                            @endif
                        </div>
                    @endif
                @empty
                    <div class="rounded-xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-6 text-center">
                        <p class="text-sm font-medium text-slate-500">
                            {{ $panel['empty'] }}
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
