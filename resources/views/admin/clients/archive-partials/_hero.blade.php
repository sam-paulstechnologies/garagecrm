{{-- resources/views/admin/clients/archive-partials/_hero.blade.php --}}

@php
    $clientsRoute = \Illuminate\Support\Facades\Route::has('admin.clients.index')
        ? route('admin.clients.index')
        : '#';
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                Archived Clients
            </h1>

            <p class="mt-2 max-w-3xl text-sm text-slate-400">
                Review archived client profiles and restore them when they need to return to the active client list.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                <a
                    href="{{ $clientsRoute }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    ← Back to Clients
                </a>
            @endif
        </div>
    </div>
</div>