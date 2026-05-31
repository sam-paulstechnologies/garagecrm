<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">Service Type(s)</h2>
        <p class="sf-section-subtitle">Services discussed or requested under this opportunity.</p>
    </div>

    <div class="p-5">
        @if($services->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($services as $service)
                    <span class="sf-badge-blue">{{ $service }}</span>
                @endforeach
            </div>
        @else
            <div class="sf-empty">No service type added yet.</div>
        @endif
    </div>
</div>
