{{-- resources/views/admin/clients/partials/opportunities.blade.php --}}

@php
    // Latest 3 opportunities with make/model snapshot
    $opps = method_exists($client, 'opportunities')
        ? $client->opportunities()->with('vehicleMake:id,name', 'vehicleModel:id,name')->latest()->take(3)->get()
        : collect();

    $stageBadge = function ($stage) {
        $stage = strtolower((string) $stage);

        return match ($stage) {
            'new' => 'sf-badge-blue',
            'attempting_contact' => 'sf-badge-yellow',
            'appointment' => 'sf-badge-orange',
            'offer' => 'sf-badge-blue',
            'closed_won', 'won' => 'sf-badge-green',
            'closed_lost', 'lost' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Opportunities
            </h3>

            <p class="sf-section-subtitle">
                Sales opportunities and possible bookings linked to this client.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.index'))
                <a href="{{ route('admin.opportunities.index', ['client_id' => $client->id]) }}" class="sf-btn-secondary">
                    View All
                </a>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.create'))
                <a href="{{ route('admin.opportunities.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                    + Add Opportunity
                </a>
            @endif
        </div>
    </div>

    {{-- Opportunities --}}
    @forelse ($opps as $opp)
        @php
            $stage = ucfirst(str_replace('_', ' ', $opp->stage ?? 'new'));
            $mk = optional($opp->vehicleMake)->name;
            $md = optional($opp->vehicleModel)->name;
            $veh = trim(($mk ? $mk : '') . ' ' . ($md ? $md : ''));

            $value = $opp->value
                ?? $opp->estimated_value
                ?? $opp->amount
                ?? null;
        @endphp

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.show'))
                            <a href="{{ route('admin.opportunities.show', $opp->id) }}"
                               class="font-extrabold text-white hover:text-orange-300 hover:underline">
                                {{ $opp->title ?? 'Untitled Opportunity' }}
                            </a>
                        @else
                            <span class="font-extrabold text-white">
                                {{ $opp->title ?? 'Untitled Opportunity' }}
                            </span>
                        @endif

                        <span class="{{ $stageBadge($opp->stage ?? 'new') }}">
                            {{ $stage }}
                        </span>
                    </div>

                    <div class="mt-2 text-sm font-medium text-slate-400">
                        @if($veh)
                            🚗 {{ $veh }}
                        @else
                            No vehicle linked
                        @endif
                    </div>

                    <div class="mt-1 text-xs font-medium text-slate-500">
                        @if(!is_null($value))
                            Value: AED {{ number_format((float) $value, 2) }}
                            ·
                        @endif

                        {{ optional($opp->created_at)->format('d M Y') ?? 'No date' }}
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.show'))
                    <a href="{{ route('admin.opportunities.show', $opp->id) }}" class="sf-link shrink-0">
                        View
                    </a>
                @endif

            </div>
        </div>
    @empty
        <div class="sf-empty">
            No opportunities yet.
        </div>
    @endforelse

</div>