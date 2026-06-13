{{-- resources/views/admin/clients/index-partials/_alphabet_nav.blade.php --}}

@php
    $letters = range('A', 'Z');

    $availableLetters = collect($availableLetters ?? [])
        ->map(fn ($letter) => strtoupper(substr(trim((string) $letter), 0, 1)))
        ->filter(fn ($letter) => preg_match('/^[A-Z]$/', $letter))
        ->unique()
        ->values()
        ->toArray();

    $currentLetter = strtoupper(substr(trim((string) ($currentLetter ?? request('letter', ''))), 0, 1));
    $currentLetter = preg_match('/^[A-Z]$/', $currentLetter) ? $currentLetter : null;

    $baseQuery = request()->except('page', 'letter');
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
            <a
                href="{{ route('admin.clients.index', $baseQuery) }}"
                class="inline-flex h-7 items-center justify-center rounded-lg border px-2.5 text-xs font-black transition {{ $currentLetter === null ? 'border-orange-400/50 bg-orange-500/20 text-orange-600 dark:text-orange-200' : 'border-slate-700 bg-slate-800 text-slate-200 hover:border-orange-400/40 hover:bg-orange-500/10 hover:text-orange-500 dark:hover:text-orange-200' }}"
                aria-current="{{ $currentLetter === null ? 'true' : 'false' }}"
            >
                All
            </a>

            @foreach($letters as $letter)
                @php
                    $isAvailable = in_array($letter, $availableLetters, true);
                    $isCurrent = $currentLetter === $letter;
                @endphp

                @if($isAvailable)
                    <a
                        href="{{ route('admin.clients.index', array_merge($baseQuery, ['letter' => $letter])) }}"
                        class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg border px-2 text-xs font-black transition {{ $isCurrent ? 'border-orange-400/50 bg-orange-500/20 text-orange-600 dark:text-orange-200' : 'border-slate-700 bg-slate-800 text-slate-200 hover:border-orange-400/40 hover:bg-orange-500/10 hover:text-orange-500 dark:hover:text-orange-200' }}"
                        aria-current="{{ $isCurrent ? 'true' : 'false' }}"
                    >
                        {{ $letter }}
                    </a>
                @else
                    <span
                        aria-disabled="true"
                        class="inline-flex h-7 min-w-7 cursor-not-allowed items-center justify-center rounded-lg border border-slate-700 bg-slate-800/40 px-2 text-xs font-black text-slate-400"
                    >
                        {{ $letter }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</div>
