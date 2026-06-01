{{-- resources/views/admin/clients/archive-partials/_client_grid.blade.php --}}

@php
    $renderedLetters = [];
@endphp

<div id="archived-client-cards" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($clients as $client)
        @php
            $clientLetter = strtoupper(substr(trim((string) ($client->name ?? '')), 0, 1));

            if (!preg_match('/[A-Z]/', $clientLetter)) {
                $clientLetter = 'OTHER';
            }

            $shouldRenderAnchor = !in_array($clientLetter, $renderedLetters, true);
            $renderedLetters[] = $clientLetter;
        @endphp

        @if($shouldRenderAnchor && $clientLetter !== 'OTHER')
            <div id="archived-client-letter-{{ $clientLetter }}" class="scroll-mt-24 sm:col-span-2 lg:col-span-3">
                <div class="mb-[-10px] inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                    {{ $clientLetter }}
                </div>
            </div>
        @endif

        @include('admin.clients.archive-partials._client_card', ['client' => $client])
    @empty
        @include('admin.clients.archive-partials._empty_state')
    @endforelse
</div>

{{-- Pagination --}}
@if(method_exists($clients, 'links'))
    <div class="text-slate-300">
        {{ $clients->links() }}
    </div>
@endif