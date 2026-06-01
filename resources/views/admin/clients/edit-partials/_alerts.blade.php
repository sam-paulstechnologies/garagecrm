{{-- resources/views/admin/clients/edit-partials/_alerts.blade.php --}}

@if(session('success'))
    <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm font-bold text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if(session('warning'))
    <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-5 py-4 text-sm font-bold text-yellow-300">
        {{ session('warning') }}
    </div>
@endif

@if(session('error'))
    <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-300">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-300">
        <div class="mb-2 font-extrabold">
            Please fix the following:
        </div>

        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif