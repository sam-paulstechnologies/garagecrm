@php
    $priorityBadge = function ($priority) {
        return match (strtolower((string) $priority)) {
            'urgent' => 'sf-badge-red',
            'high' => 'sf-badge-orange',
            'medium' => 'sf-badge-yellow',
            'low' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };

    $slotBadge = function ($slot) {
        return match (strtolower((string) $slot)) {
            'morning' => 'sf-badge-blue',
            'afternoon' => 'sf-badge-orange',
            'evening' => 'sf-badge-slate',
            'full_day' => 'sf-badge-green',
            default => 'sf-badge-slate',
        };
    };
@endphp

@if($bookings->isEmpty())
    <div class="sf-booking-panel rounded-2xl border p-10 text-center shadow-sm">
        <div class="text-lg font-extrabold sf-booking-value">No archived bookings found.</div>

        <div class="mt-4">
            <a href="{{ route('admin.bookings.index') }}" class="sf-btn-primary">
                Go to Active Bookings
            </a>
        </div>
    </div>
@else
    <div class="sf-booking-panel overflow-hidden rounded-2xl border shadow-sm">
        <div class="sf-table-scroll">
            <table class="sf-table sf-booking-table">
                <thead>
                    <tr>
                        <th class="w-[18%]">Client</th>
                        <th class="w-[18%]">Vehicle</th>
                        <th class="w-[12%]">Date</th>
                        <th class="w-[10%]">Slot</th>
                        <th class="w-[12%]">Priority</th>
                        <th class="w-[14%]">Assigned</th>
                        <th class="w-[10%]">Status</th>
                        <th class="w-[6%] text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($bookings as $booking)
                        @php
                            $vehicle = optional($booking->vehicleData ?? $booking->vehicle ?? null);

                            $makeName = optional($vehicle->make)->name
                                ?? optional($vehicle->vehicleMake)->name;

                            $modelName = optional($vehicle->model)->name
                                ?? optional($vehicle->vehicleModel)->name;

                            $vehicleLabel = trim(($makeName ?? '') . ' ' . ($modelName ?? ''));

                            $dateValue = $booking->booking_date
                                ?? optional($booking->scheduled_at)->format('Y-m-d')
                                ?? ($booking->date ?? null);

                            $assigned = optional($booking->assignedUser)->name
                                ?? optional($booking->assignee)->name;
                        @endphp

                        <tr>
                            <td>
                                <div class="font-extrabold sf-booking-value">
                                    {{ optional($booking->client)->name ?? '-' }}
                                </div>

                                <div class="mt-1 text-xs font-medium sf-booking-faint">
                                    Booking ID: #{{ $booking->id }}
                                </div>
                            </td>

                            <td>
                                <div class="font-bold sf-booking-muted">
                                    {{ $vehicleLabel !== '' ? $vehicleLabel : '-' }}
                                </div>

                                @if(!empty($vehicle->plate_number))
                                    <div class="mt-1 text-xs font-medium sf-booking-faint">
                                        {{ $vehicle->plate_number }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="font-bold sf-booking-muted">
                                    {{ $dateValue ? \Illuminate\Support\Carbon::parse($dateValue)->format('d M Y') : '-' }}
                                </div>
                            </td>

                            <td>
                                <span class="{{ $slotBadge($booking->slot ?? '') }}">
                                    {{ $booking->slot ? ucfirst(str_replace('_', ' ', $booking->slot)) : '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="{{ $priorityBadge($booking->priority ?? '') }}">
                                    {{ $booking->priority ? ucfirst($booking->priority) : '-' }}
                                </span>
                            </td>

                            <td>
                                <div class="font-bold sf-booking-muted">
                                    {{ $assigned ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <span class="sf-badge-red">
                                    Archived
                                </span>
                            </td>

                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if(Route::has('admin.bookings.show'))
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-booking-link">
                                            View
                                        </a>
                                    @endif

                                    <form action="{{ route('admin.bookings.restore', $booking) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Restore this booking?');">
                                        @csrf
                                        @method('PUT')

                                        <button type="submit" class="sf-booking-link">
                                            Restore
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
