@php
use Illuminate\Support\Str;

/**
 * ------------------------------------------------------------
 * Communications – Client scoped
 * ------------------------------------------------------------
 */

$communications = \App\Models\Shared\Communication::where('company_id', company_id())
    ->where('client_id', $client->id)
    ->orderByDesc('communication_date')
    ->orderByDesc('id')
    ->limit(10)
    ->get();

$typeBadge = function ($type) {
    $type = strtolower((string) $type);

    return match ($type) {
        'call', 'phone' => 'sf-badge-blue',
        'email' => 'sf-badge-orange',
        'whatsapp' => 'sf-badge-green',
        'sms' => 'sf-badge-slate',
        default => 'sf-badge-slate',
    };
};
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Communications
            </h2>

            <p class="sf-section-subtitle">
                Recent calls, emails, WhatsApp updates, and follow-up notes for this client.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(Route::has('admin.communications.create'))
                <a href="{{ route('admin.communications.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                    Add Communication
                </a>
            @endif

            @if(Route::has('admin.communications.index'))
                <a href="{{ route('admin.communications.index', ['client_id' => $client->id]) }}" class="sf-btn-secondary">
                    Open Log
                </a>
            @endif
        </div>
    </div>

    {{-- List --}}
    @if($communications->isEmpty())
        <div class="sf-empty">
            No communications yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach($communications as $comm)
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        {{-- Main --}}
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="{{ $typeBadge($comm->communication_type) }}">
                                    {{ ucfirst($comm->communication_type ?? 'Communication') }}
                                </span>

                                @if($comm->follow_up_required)
                                    <span class="sf-badge-yellow">
                                        Follow-up required
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2 text-sm font-medium leading-6 text-slate-300">
                                {{ Str::limit($comm->content ?? '—', 180) }}
                            </div>

                            <div class="mt-2 text-xs font-medium text-slate-500">
                                {{ optional($comm->communication_date)->format('d M Y H:i') ?? 'No date' }}
                            </div>
                        </div>

                        {{-- Meta --}}
                        <div class="shrink-0 text-left sm:text-right">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Logged
                            </div>

                            <div class="mt-1 text-sm font-extrabold text-white">
                                #{{ $comm->id }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>