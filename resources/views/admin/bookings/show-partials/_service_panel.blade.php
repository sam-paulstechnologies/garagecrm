<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Service Type(s)
        </h2>
    </div>

    <div class="p-5">
        @if($services->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($services as $service)
                    <span class="sf-badge-blue">
                        {{ $service }}
                    </span>
                @endforeach
            </div>
        @else
            <div class="sf-booking-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-booking-muted">
                No service type added.
            </div>
        @endif
    </div>
</div>
