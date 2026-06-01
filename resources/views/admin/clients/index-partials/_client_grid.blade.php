{{-- resources/views/admin/clients/index-partials/_client_grid.blade.php --}}

@php
    $renderedLetters = [];
@endphp

<div id="client-cards" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
            <div id="client-letter-{{ $clientLetter }}" class="scroll-mt-24 sm:col-span-2 lg:col-span-3">
                <div class="mb-[-10px] inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                    {{ $clientLetter }}
                </div>
            </div>
        @endif

        @include('admin.clients.index-partials._client_card', ['client' => $client])
    @empty
        <div class="sm:col-span-2 lg:col-span-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
                <p class="text-sm font-bold text-slate-400">
                    No clients found.
                </p>

                <p class="mt-2 text-xs font-medium text-slate-500">
                    Try changing the filters or add a new client.
                </p>

                @if(\Illuminate\Support\Facades\Route::has('admin.clients.create'))
                    <a
                        href="{{ route('admin.clients.create') }}"
                        class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white transition hover:bg-orange-600"
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
    <div class="text-slate-300">
        {{ $clients->links() }}
    </div>
@endif