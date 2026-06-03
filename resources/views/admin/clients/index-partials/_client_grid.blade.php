{{-- resources/views/admin/clients/index-partials/_client_grid.blade.php --}}

@php
    $renderedLetters = [];
@endphp

<div id="client-cards" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
    @forelse($clients as $client)
        @php
            $clientLetter = strtoupper(substr(trim((string) ($client->name ?? '')), 0, 1));

            if (!preg_match('/[A-Z]/', $clientLetter)) {
                $clientLetter = 'OTHER';
            }

            $shouldRenderAnchor = !in_array($clientLetter, $renderedLetters, true);
            $renderedLetters[] = $clientLetter;
        @endphp

        @if($shouldRenderAnchor && $clientLetter !== 'OTHER')
            <div id="client-letter-{{ $clientLetter }}" class="scroll-mt-20 pt-1 sm:col-span-2 xl:col-span-3 2xl:col-span-4">
                <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-2.5 py-0.5 text-[11px] font-black text-orange-300">
                    {{ $clientLetter }}
                </div>
            </div>
        @endif

        @include('admin.clients.index-partials._client_card', ['client' => $client])
    @empty
        <div class="sm:col-span-2 xl:col-span-3 2xl:col-span-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 text-center">
                <p class="text-sm font-bold text-slate-400">
                    No clients found.
                </p>

                <p class="mt-2 text-xs font-medium text-slate-500">
                    Try changing the filters or add a new client.
                </p>

                @if(\Illuminate\Support\Facades\Route::has('admin.clients.create'))
                    <a
                        href="{{ route('admin.clients.create') }}"
                        class="mt-3 inline-flex h-9 items-center justify-center rounded-lg bg-orange-500 px-3 text-xs font-extrabold text-white transition hover:bg-orange-600"
                    >
                        + Add Client
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if(method_exists($clients, 'links'))
    <div class="pt-1 text-slate-300">
        {{ $clients->links() }}
    </div>
@endif
