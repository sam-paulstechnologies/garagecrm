<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Booking Information
        </h2>

        <p class="sf-section-subtitle">
            Keep the booking accurate so job creation and customer follow-ups work cleanly.
        </p>
    </div>

    <div class="p-5">
        @include('admin.bookings.partials.form', [
            'action'        => route('admin.bookings.update', $booking),
            'isEdit'        => true,
            'booking'       => $booking,
            'clients'       => $clients,
            'opportunities' => $opportunities,
            'vehicles'      => $vehicles,
            'users'         => $users,
            'vehicleMakes'  => $vehicleMakes,
            'vehicleModels' => $vehicleModels,
        ])
    </div>
</div>
