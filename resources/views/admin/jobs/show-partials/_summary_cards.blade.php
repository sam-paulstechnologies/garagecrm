<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="sf-stat-card">
        <div class="sf-stat-label">
            Current Stage
        </div>

        <div class="mt-2 text-xl font-extrabold text-white">
            {{ ucwords(str_replace('_', ' ', $status)) }}
        </div>

        <div class="sf-stat-note">
            Operational job status
        </div>
    </div>

    <div class="sf-stat-card">
        <div class="sf-stat-label">
            Service Bucket
        </div>

        <div class="mt-2 text-xl font-extrabold text-white">
            {{ $serviceBucket }}
        </div>

        <div class="sf-stat-note">
            Used later for WhatsApp follow-up
        </div>
    </div>

    <div class="sf-stat-card">
        <div class="sf-stat-label">
            Closure / ROI
        </div>

        @if($status === 'completed')
            <div class="mt-2 text-xl font-extrabold text-green-300">
                Closed
            </div>

            <div class="sf-stat-note">
                Revenue available for ROI reporting
            </div>
        @else
            <div class="mt-2 text-xl font-extrabold text-orange-300">
                Invoice Required
            </div>

            <div class="sf-stat-note">
                Invoice no. + amount required to close
            </div>
        @endif
    </div>
</div>
