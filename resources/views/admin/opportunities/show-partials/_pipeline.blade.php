<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="sf-section-title">Pipeline Status</h2>
            <p class="sf-section-subtitle">Appointment means timing is being discussed. Booking Confirmed means the customer agreed to proceed.</p>
        </div>

        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 px-4 py-3 text-sm font-bold text-orange-300">
            Next Action: <span class="sf-opportunity-value">{{ $nextAction }}</span>
        </div>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            @foreach($timelineItems as $index => $item)
                @php
                    $active = $currentStage === $item['stage'];
                    $done = $currentIndex >= $index;
                    $stepClass = $active ? 'sf-opportunity-step-active' : ($done ? 'sf-opportunity-step-done' : 'sf-opportunity-step-idle');
                @endphp

                <div class="rounded-2xl border px-4 py-4 text-sm {{ $stepClass }}">
                    <div class="font-extrabold">{{ $item['label'] }}</div>
                    <div class="mt-1 text-xs font-medium opacity-80">{{ $active ? 'Current' : ($done ? 'Completed' : 'Pending') }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
