<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="sf-section-title">
                Booking Status
            </h2>

            <p class="sf-section-subtitle">
                Booking is the confirmed customer appointment before job creation.
            </p>
        </div>

        <div class="sf-booking-next-action rounded-2xl border px-4 py-3 text-sm font-bold">
            Next Action:
            <span class="sf-booking-value">{{ $nextAction }}</span>
        </div>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="rounded-2xl border px-4 py-4 {{ $stepClass($status === 'pending') }}">
                <div class="text-xs font-bold uppercase tracking-wide opacity-70">Step 1</div>
                <div class="mt-1 text-sm font-extrabold">Pending</div>
            </div>

            <div class="rounded-2xl border px-4 py-4 {{ $stepClass(in_array($status, ['scheduled', 'confirmed', 'approved'], true), in_array($status, ['converted_to_job', 'completed'], true)) }}">
                <div class="text-xs font-bold uppercase tracking-wide opacity-70">Step 2</div>
                <div class="mt-1 text-sm font-extrabold">Scheduled / Confirmed</div>
            </div>

            <div class="rounded-2xl border px-4 py-4 {{ $stepClass(in_array($status, ['converted_to_job', 'completed'], true)) }}">
                <div class="text-xs font-bold uppercase tracking-wide opacity-70">Step 3</div>
                <div class="mt-1 text-sm font-extrabold">Job Created / Completed</div>
            </div>

            <div class="rounded-2xl border px-4 py-4 {{ $stepClass(in_array($status, ['lost', 'cancelled', 'canceled', 'rejected'], true)) }}">
                <div class="text-xs font-bold uppercase tracking-wide opacity-70">Exception</div>
                <div class="mt-1 text-sm font-extrabold">Lost / Cancelled</div>
            </div>
        </div>
    </div>
</div>
