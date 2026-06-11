{{-- resources/views/admin/clients/show-partials/sections/_bookings_section.blade.php --}}

@php
    $bookings = collect($client->bookings ?? []);

    $bookingCreateRoute = \Illuminate\Support\Facades\Route::has('admin.bookings.create')
        ? route('admin.bookings.create', ['client_id' => $client->id])
        : null;

    $bookingShowRoute = function ($booking) {
        return \Illuminate\Support\Facades\Route::has('admin.bookings.show')
            ? route('admin.bookings.show', $booking->id)
            : null;
    };

    $formatDate = function ($value) {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-bookings-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-bookings-title {
        color: #ffffff;
    }

    .sf-bookings-muted {
        color: #cbd5e1;
    }

    .sf-bookings-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-bookings-value {
        color: #ffffff;
    }

    .sf-bookings-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    html[data-theme="light"] .sf-bookings-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-bookings-title,
    html[data-theme="light"] .sf-bookings-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-bookings-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-bookings-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-bookings-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
</style>

<section id="bookings" class="sf-bookings-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-bookings-title text-lg font-extrabold tracking-tight">
                Bookings
            </h2>

            <p class="sf-bookings-muted mt-1 text-sm font-medium">
                Service appointments and booking requests linked to this client.
            </p>
        </div>

        @if($bookingCreateRoute)
            <a
                href="{{ $bookingCreateRoute }}"
                class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
            >
                + Add Booking
            </a>
        @endif
    </div>

    @if($bookings->isNotEmpty())
        <div class="space-y-3">
            @foreach($bookings->take(5) as $booking)
                @php
                    $showRoute = $bookingShowRoute($booking);
                @endphp

                <div class="sf-bookings-card rounded-2xl border p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="sf-bookings-value text-sm font-black">
                                {{ $booking->service_type ?? $booking->name ?? 'Booking #' . $booking->id }}
                            </p>

                            <p class="sf-bookings-muted mt-1 text-xs font-medium">
                                {{ $formatDate($booking->booking_date ?? $booking->scheduled_at ?? $booking->created_at ?? null) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1 text-xs font-black text-blue-700 dark:text-blue-200">
                                {{ $booking->status ?? 'pending' }}
                            </span>

                            @if($showRoute)
                                <a href="{{ $showRoute }}" class="text-xs font-black text-orange-700 hover:text-orange-800 dark:text-orange-200 dark:hover:text-orange-100">
                                    View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sf-bookings-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
            No bookings yet.
        </div>
    @endif
</section>
