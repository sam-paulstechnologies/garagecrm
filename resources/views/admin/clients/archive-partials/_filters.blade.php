{{-- resources/views/admin/clients/archive-partials/_filters.blade.php --}}

@php
    $q = $q ?? request('q', '');
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <form method="GET" action="{{ route('admin.clients.archived') }}" class="space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="archived-client-search" class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Search Archived Clients
                </label>

                <input
                    type="text"
                    id="archived-client-search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search client, phone, email, WhatsApp, vehicle make/model, plate number, VIN..."
                    class="h-11 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 text-sm font-semibold text-white outline-none transition placeholder:text-slate-600 focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                >

                <p class="mt-2 text-xs font-medium text-slate-500">
                    Searches across archived client name, contact details, and linked vehicle details.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.clients.archived') }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-5 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-6 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    Search
                </button>
            </div>
        </div>
    </form>
</div>