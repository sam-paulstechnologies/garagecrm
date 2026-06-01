{{-- resources/views/admin/clients/archive-partials/_stats.blade.php --}}

@php
    $clientsCollection = collect($clients instanceof \Illuminate\Pagination\AbstractPaginator ? $clients->items() : $clients);
    $clientsTotal = method_exists($clients, 'total') ? $clients->total() : $clientsCollection->count();
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
            Archived Clients
        </p>

        <p class="mt-3 text-3xl font-black text-orange-300">
            {{ $clientsTotal }}
        </p>

        <p class="mt-2 text-xs font-semibold text-slate-500">
            Hidden from active client list
        </p>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
            Available Action
        </p>

        <p class="mt-3 text-lg font-extrabold text-white">
            Restore Client
        </p>

        <p class="mt-2 text-xs font-semibold text-slate-500">
            Move client back to active list
        </p>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
            Archive Purpose
        </p>

        <p class="mt-3 text-lg font-extrabold text-white">
            Clean Workspace
        </p>

        <p class="mt-2 text-xs font-semibold text-slate-500">
            Keep inactive records safely stored
        </p>
    </div>
</div>