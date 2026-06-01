{{-- resources/views/admin/clients/create-partials/_hero.blade.php --}}

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                Create Client
            </h1>

            <p class="mt-2 max-w-3xl text-sm text-slate-400">
                Add a new customer profile with contact details for bookings, vehicles, invoices, and service history.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                <a
                    href="{{ route('admin.clients.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    ← Back to Clients
                </a>
            @endif
        </div>
    </div>
</div>