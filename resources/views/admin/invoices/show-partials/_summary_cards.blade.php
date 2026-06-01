<div class="grid grid-cols-1 gap-4 md:grid-cols-4">
    <div class="sf-stat-card">
        <div class="sf-stat-label">
            Invoice Amount
        </div>

        <div class="mt-2 text-2xl font-extrabold text-white">
            {{ $currency }} {{ number_format($amount, 2) }}
        </div>

        <div class="sf-stat-note">
            Revenue value captured
        </div>
    </div>

    <div class="sf-stat-card">
        <div class="sf-stat-label">
            Status
        </div>

        <div class="mt-2 text-2xl font-extrabold text-white">
            {{ ucwords($statusValue) }}
        </div>

        <div class="sf-stat-note">
            Payment / invoice state
        </div>
    </div>

    <div class="sf-stat-card">
        <div class="sf-stat-label">
            Linked Job
        </div>

        <div class="mt-2 text-2xl font-extrabold {{ $hasJob ? 'text-green-300' : 'text-red-300' }}">
            {{ $hasJob ? 'Yes' : 'No' }}
        </div>

        <div class="sf-stat-note">
            Needed for attribution
        </div>
    </div>

    <div class="sf-stat-card">
        <div class="sf-stat-label">
            ROI Status
        </div>

        @if($roiReady)
            <div class="mt-2 text-2xl font-extrabold text-orange-300">
                Ready
            </div>

            <div class="sf-stat-note">
                Job + paid revenue available
            </div>
        @else
            <div class="mt-2 text-2xl font-extrabold text-yellow-300">
                Pending
            </div>

            <div class="sf-stat-note">
                Missing job, paid status, or amount
            </div>
        @endif
    </div>
</div>
