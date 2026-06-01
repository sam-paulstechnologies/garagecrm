{{-- resources/views/admin/clients/create-partials/_alerts.blade.php --}}

@if(session('success'))
    <div class="relative rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200">
        <strong class="font-extrabold">Success!</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif

@if(session('warning'))
    <div class="relative rounded-2xl border border-orange-400/20 bg-orange-500/10 px-5 py-4 text-sm text-orange-200">
        <strong class="font-extrabold">Warning!</strong>
        <span class="block sm:inline">{{ session('warning') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="relative rounded-2xl border border-red-400/20 bg-red-500/10 px-5 py-4 text-sm text-red-200">
        <strong class="font-extrabold">Error!</strong>
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-5 py-4 text-sm text-red-200">
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