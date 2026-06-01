{{-- resources/views/admin/clients/edit-partials/_header.blade.php --}}

@php
    $showRoute = \Illuminate\Support\Facades\Route::has('admin.clients.show')
        ? route('admin.clients.show', $client->id)
        : null;

    $indexRoute = \Illuminate\Support\Facades\Route::has('admin.clients.index')
        ? route('admin.clients.index')
        : '#';
@endphp

<div class="sf-edit-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="sf-edit-title text-3xl font-extrabold tracking-tight">
                Edit Client
            </h1>

            <p class="sf-edit-muted mt-2 text-sm font-medium">
                Update client profile, contact details, address, preferences, and CRM information.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($showRoute)
                <a
                    href="{{ $showRoute }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    View Client
                </a>
            @endif

            <a
                href="{{ $indexRoute }}"
                class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
            >
                ← Back to Clients
            </a>
        </div>
    </div>
</div>