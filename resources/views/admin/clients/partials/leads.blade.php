{{-- resources/views/admin/clients/partials/leads.blade.php --}}

@php
    // Latest 3 leads for this client
    $leads = method_exists($client, 'leads')
        ? $client->leads()->latest()->take(3)->get()
        : collect();

    $statusBadge = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'new' => 'sf-badge-blue',
            'attempting_contact' => 'sf-badge-yellow',
            'contact_on_hold' => 'sf-badge-orange',
            'qualified', 'converted' => 'sf-badge-green',
            'disqualified', 'lost' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Leads
            </h3>

            <p class="sf-section-subtitle">
                Latest lead records linked to this client.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (\Illuminate\Support\Facades\Route::has('admin.leads.index'))
                <a href="{{ route('admin.leads.index', ['client_id' => $client->id]) }}" class="sf-btn-secondary">
                    View All
                </a>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('admin.leads.create'))
                <a href="{{ route('admin.leads.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                    + Add Lead
                </a>
            @endif
        </div>
    </div>

    {{-- Leads --}}
    @forelse ($leads as $lead)
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        @if (\Illuminate\Support\Facades\Route::has('admin.leads.show'))
                            <a href="{{ route('admin.leads.show', $lead->id) }}"
                               class="font-extrabold text-white hover:text-orange-300 hover:underline">
                                {{ $lead->name ?? 'Untitled Lead' }}
                            </a>
                        @else
                            <span class="font-extrabold text-white">
                                {{ $lead->name ?? 'Untitled Lead' }}
                            </span>
                        @endif

                        @if(!empty($lead->status))
                            <span class="{{ $statusBadge($lead->status) }}">
                                {{ ucfirst(str_replace('_', ' ', $lead->status)) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-2 text-sm font-medium text-slate-400">
                        {{ $lead->phone ?? $lead->phone_norm ?? $lead->email ?? 'No contact available' }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Source: {{ $lead->source ?? '—' }}
                        @if(!empty($lead->created_at))
                            · {{ $lead->created_at->format('d M Y') }}
                        @endif
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('admin.leads.show'))
                    <a href="{{ route('admin.leads.show', $lead->id) }}" class="sf-link shrink-0">
                        View
                    </a>
                @endif

            </div>
        </div>
    @empty
        <div class="sf-empty">
            No leads yet.
        </div>
    @endforelse

</div>