{{-- resources/views/admin/clients/archive-partials/_empty_state.blade.php --}}

<div class="sm:col-span-2 lg:col-span-3">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
        <p class="text-sm font-bold text-slate-400">
            No archived clients found.
        </p>

        <p class="mt-2 text-xs font-medium text-slate-500">
            Archived clients will appear here when you archive them from the active client list.
        </p>

        @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
            <a
                href="{{ route('admin.clients.index') }}"
                class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white transition hover:bg-orange-600"
            >
                Back to Clients
            </a>
        @endif
    </div>
</div>