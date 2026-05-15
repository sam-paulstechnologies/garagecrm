@php
    $bookings = $client->bookings instanceof \Illuminate\Support\Collection
        ? $client->bookings
        : collect();

    $statusBadge = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'scheduled', 'confirmed' => 'sf-badge-blue',
            'completed', 'converted_to_job' => 'sf-badge-green',
            'cancelled', 'canceled', 'lost', 'rejected' => 'sf-badge-red',
            'pending' => 'sf-badge-orange',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Bookings
            </h2>

            <p class="sf-section-subtitle">
                Service appointments and booking requests linked to this client.
            </p>
        </div>

        @if(Route::has('admin.bookings.create'))
            <a href="{{ route('admin.bookings.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                + Add Booking
            </a>
        @endif
    </div>

    {{-- List --}}
    @if($bookings->isEmpty())
        <div class="sf-empty">
            No bookings yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach($bookings as $booking)
                @php
                    $bookingDate =
                        $booking->scheduled_at
                        ?? $booking->booking_date
                        ?? $booking->preferred_date
                        ?? null;

                    $bookingDateFormatted = $bookingDate
                        ? \Illuminate\Support\Carbon::parse($bookingDate)->format('d M Y, h:i A')
                        : '—';

                    $vehicleLabel = $booking->vehicle?->label
                        ?? trim(
                            ($booking->vehicle?->year ?? '') . ' ' .
                            ($booking->vehicle?->make?->name ?? '') . ' ' .
                            ($booking->vehicle?->model?->name ?? '')
                        );

                    $vehicleLabel = trim($vehicleLabel) ?: null;
                @endphp

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        {{-- Main --}}
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="font-extrabold text-white">
                                    Booking #{{ $booking->id }}
                                </div>

                                <span class="{{ $statusBadge($booking->status ?? 'pending') }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->status ?? 'pending')) }}
                                </span>
                            </div>

                            <div class="mt-2 text-sm font-bold text-slate-300">
                                {{ $bookingDateFormatted }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                @if($vehicleLabel)
                                    🚗 {{ $vehicleLabel }}
                                @else
                                    No vehicle linked
                                @endif

                                @if(!empty($booking->slot))
                                    · {{ ucfirst(str_replace('_', ' ', $booking->slot)) }}
                                @endif
                            </div>

                            @if(!empty($booking->notes))
                                <div class="mt-2 text-sm font-medium leading-6 text-slate-400">
                                    {{ \Illuminate\Support\Str::limit($booking->notes, 120) }}
                                </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        @if(Route::has('admin.bookings.show'))
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-link shrink-0">
                                View
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>