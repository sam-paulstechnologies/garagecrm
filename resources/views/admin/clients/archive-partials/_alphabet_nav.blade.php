{{-- resources/views/admin/clients/archive-partials/_alphabet_nav.blade.php --}}

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

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-sm font-extrabold text-white">
                Jump to Archived Client
            </h2>

            <p class="mt-1 text-xs font-medium text-slate-500">
                Use letters to move quickly through the current archived list.
            </p>
        </div>

        <div class="flex flex-wrap gap-1.5">
            @foreach($letters as $letter)
                @php
                    $isAvailable = in_array($letter, $availableLetters, true);
                @endphp

                @if($isAvailable)
                    <a
                        href="#archived-client-letter-{{ $letter }}"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-xs font-black text-slate-200 transition hover:border-orange-400/40 hover:bg-orange-500/10 hover:text-orange-300"
                    >
                        {{ $letter }}
                    </a>
                @else
                    <span
                        class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-800 bg-slate-950/40 text-xs font-black text-slate-700"
                    >
                        {{ $letter }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</div>