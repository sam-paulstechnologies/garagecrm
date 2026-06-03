{{-- resources/views/admin/clients/index-partials/_alphabet_nav.blade.php --}}

@php
    $letters = range('A', 'Z');

    $clientsCollection = collect($clients instanceof \Illuminate\Pagination\AbstractPaginator ? $clients->items() : $clients);

    $availableLetters = $clientsCollection
        ->map(function ($client) {
            return strtoupper(substr(trim((string) ($client->name ?? '')), 0, 1));
        })
        ->filter(fn ($letter) => preg_match('/[A-Z]/', $letter))
        ->unique()
        ->values()
        ->toArray();
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 shadow-sm">
    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 lg:flex lg:items-baseline lg:gap-2">
            <h2 class="text-xs font-extrabold uppercase tracking-wide text-white">
                Jump to Client
            </h2>

            <p class="mt-0.5 text-[11px] font-medium leading-4 text-slate-500 lg:mt-0">
                Use letters to jump through the list.
            </p>
        </div>

        <div class="flex flex-wrap gap-1">
            @foreach($letters as $letter)
                @php
                    $isAvailable = in_array($letter, $availableLetters, true);
                @endphp

                @if($isAvailable)
                    <a
                        href="#client-letter-{{ $letter }}"
                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 px-2 text-xs font-black text-slate-200 transition hover:border-orange-400/40 hover:bg-orange-500/10 hover:text-orange-300"
                    >
                        {{ $letter }}
                    </a>
                @else
                    <span
                        class="inline-flex h-7 min-w-7 cursor-not-allowed items-center justify-center rounded-lg border border-slate-800 bg-slate-950/40 px-2 text-xs font-black text-slate-700"
                    >
                        {{ $letter }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</div>
