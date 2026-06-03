{{-- resources/views/admin/clients/index-partials/_hero.blade.php --}}

@php
    $archivedRoute = \Illuminate\Support\Facades\Route::has('admin.clients.archived')
        ? route('admin.clients.archived')
        : '#';

    $importRoute = \Illuminate\Support\Facades\Route::has('admin.clients.import.form')
        ? route('admin.clients.import.form')
        : '#';

    $createRoute = \Illuminate\Support\Facades\Route::has('admin.clients.create')
        ? route('admin.clients.create')
        : '#';
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">
                Clients
            </h1>

            <p class="mt-1 max-w-3xl text-xs font-medium text-slate-400">
                Manage client profiles, contact details, vehicles, bookings, service history, and CRM activity.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.clients.archived'))
                <a
                    href="{{ $archivedRoute }}"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 px-3 text-xs font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    View Archived
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.form'))
                <a
                    href="{{ $importRoute }}"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-blue-400/20 bg-blue-500/10 px-3 text-xs font-bold text-blue-300 transition hover:bg-blue-500/15"
                >
                    Import Clients
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.create'))
                <a
                    href="{{ $createRoute }}"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-orange-500 px-3 text-xs font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    + Add Client
                </a>
            @endif
        </div>
    </div>
</div>
