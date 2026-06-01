{{-- resources/views/admin/clients/archive-partials/_alerts.blade.php --}}

@if(session('success'))
    <div class="relative rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200">
        <strong class="font-extrabold">
            Success!
        </strong>

        <span class="block sm:inline">
            {{ session('success') }}
        </span>

        <button
            type="button"
            onclick="this.parentElement.remove()"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-xl font-bold text-emerald-200 hover:text-white"
            aria-label="Close alert"
        >
            &times;
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="relative rounded-2xl border border-orange-400/20 bg-orange-500/10 px-5 py-4 text-sm text-orange-200">
        <strong class="font-extrabold">
            Warning!
        </strong>

        <span class="block sm:inline">
            {{ session('warning') }}
        </span>

        <button
            type="button"
            onclick="this.parentElement.remove()"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-xl font-bold text-orange-200 hover:text-white"
            aria-label="Close alert"
        >
            &times;
        </button>
    </div>
@endif

@if(session('error'))
    <div class="relative rounded-2xl border border-red-400/20 bg-red-500/10 px-5 py-4 text-sm text-red-200">
        <strong class="font-extrabold">
            Error!
        </strong>

        <span class="block sm:inline">
            {{ session('error') }}
        </span>

        <button
            type="button"
            onclick="this.parentElement.remove()"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-xl font-bold text-red-200 hover:text-white"
            aria-label="Close alert"
        >
            &times;
        </button>
    </div>
@endif