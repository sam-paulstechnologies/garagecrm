@php
    $snapshotDate = $booking->booking_date
        ? \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d M Y')
        : 'Not set';
    $snapshotStatus = method_exists($booking, 'getStatusLabelAttribute')
        ? $booking->status_label
        : ucfirst(str_replace('_', ' ', (string) ($booking->status ?? 'pending')));
    $snapshotPhone = $booking->client?->phone ?? $booking->client?->whatsapp ?? $booking->lead?->phone ?? null;
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="sf-booking-panel sf-crm-edit-card rounded-2xl border shadow-sm">
        <div class="sf-crm-card-header border-b border-white/10 p-5">
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

    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <div class="sf-booking-panel sf-booking-edit-side-card rounded-2xl border shadow-sm">
            <div class="border-b border-white/10 p-5">
                <h2 class="sf-section-title">Booking Snapshot</h2>
            </div>

            <div class="divide-y divide-white/10 text-sm">
                @foreach([
                    'Booking' => $booking->name ?? 'Booking #' . $booking->id,
                    'Client' => $booking->client?->name ?? 'No client linked',
                    'Phone' => $snapshotPhone ?: 'Not set',
                    'Status' => $snapshotStatus,
                    'Booking Date' => $snapshotDate,
                    'Slot' => ucfirst(str_replace('_', ' ', (string) ($booking->slot ?? 'Not set'))),
                ] as $label => $value)
                    <div class="px-5 py-3">
                        <div class="sf-booking-faint text-xs font-black uppercase tracking-wide">{{ $label }}</div>
                        <div class="sf-booking-value mt-1 font-extrabold">{{ $value }}</div>
                    </div>
                @endforeach

                @if($booking->job && Route::has('admin.jobs.show'))
                    <div class="px-5 py-3">
                        <a href="{{ route('admin.jobs.show', $booking->job) }}" class="sf-crm-link font-extrabold">
                            Open linked job
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="sf-booking-panel sf-booking-edit-side-card rounded-2xl border p-5 shadow-sm">
            <h2 class="sf-section-title">Edit Guidelines</h2>
            <div class="mt-3 space-y-2 text-sm font-semibold sf-booking-muted">
                <p>Use Lost only when the booking did not happen.</p>
                <p>Converted To Job uses the existing job creation flow.</p>
                <p>Date, slot, and overbooking rules still apply.</p>
            </div>
        </div>

        <div class="sf-booking-edit-note rounded-2xl border p-5 shadow-sm">
            <h2 class="sf-booking-edit-note-title text-base font-extrabold">WhatsApp Note</h2>
            <p class="sf-booking-edit-note-text mt-3 text-sm font-semibold leading-6">
                Editing a booking does not automatically send WhatsApp messages. Continue messaging from Inbox or automations.
            </p>
        </div>
    </aside>
</div>
