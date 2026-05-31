{{-- resources/views/admin/bookings/partials/errors.blade.php --}}

@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-4">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-red-500/20 text-red-300">
                !
            </div>

            <div>
                <h3 class="text-sm font-bold text-red-200">
                    Please fix the following errors
                </h3>

                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-100/90">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-4">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-red-500/20 text-red-300">
                !
            </div>

            <div>
                <h3 class="text-sm font-bold text-red-200">
                    Error
                </h3>

                <p class="mt-2 text-sm text-red-100/90">
                    {{ session('error') }}
                </p>
            </div>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300">
                ✓
            </div>

            <div>
                <h3 class="text-sm font-bold text-emerald-200">
                    Success
                </h3>

                <p class="mt-2 text-sm text-emerald-100/90">
                    {{ session('success') }}
                </p>
            </div>
        </div>
    </div>
@endif