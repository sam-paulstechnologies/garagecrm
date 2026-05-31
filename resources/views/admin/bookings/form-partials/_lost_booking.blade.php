<div id="lost_reason_wrap" class="sf-card hidden border-red-400/20">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Lost Booking Details
        </h2>

        <p class="sf-section-subtitle">
            Required when status is Lost Booking.
        </p>
    </div>

    <div class="sf-card-body">
        <label for="lost_reason" class="sf-label">
            Lost Reason <span class="text-red-300">*</span>
        </label>

        <select id="lost_reason" name="lost_reason" class="sf-select">
            <option value="">- Select Reason -</option>

            @foreach($lostReasons as $reason)
                <option value="{{ $reason }}" @selected($lostReasonVal === $reason)>
                    {{ $lostReasonLabels[$reason] ?? ucfirst(str_replace('_', ' ', $reason)) }}
                </option>
            @endforeach
        </select>

        @error('lost_reason')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>
</div>
