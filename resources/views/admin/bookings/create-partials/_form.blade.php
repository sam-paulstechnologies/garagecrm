<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Booking Information
        </h2>

        <p class="sf-section-subtitle">
            Add booking details. This should represent a confirmed appointment, not only a tentative enquiry.
        </p>
    </div>

    <div class="p-5">
        @include('admin.bookings.partials.form', [
            'action'        => route('admin.bookings.store'),
            'isEdit'        => false,
            'booking'       => null,
            'clients'       => $clients,
            'opportunities' => $opportunities,
            'vehicles'      => $vehicles,
            'users'         => $users,
            'vehicleMakes'  => $vehicleMakes,
            'vehicleModels' => $vehicleModels,
        ])
    </div>
</div>
