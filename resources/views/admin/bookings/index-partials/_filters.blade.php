{{-- resources/views/admin/bookings/index-partials/_filters.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $status = $status ?? request('status', '');
    $bucket = $bucket ?? request('bucket', '');
    $clearUrl = route('admin.bookings.index');

    $statusLabel = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'converted_to_job' => 'Converted To Job',
            'completed' => 'Completed',
            'lost' => 'Lost Booking',
            'cancelled', 'canceled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
    };
@endphp

<div class="sf-booking-panel rounded-2xl border p-5 shadow-sm">
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="space-y-5">
        <div>
            <h2 class="sf-booking-title text-base font-extrabold tracking-tight">Search & Filter Bookings</h2>
            <p class="sf-booking-muted mt-1 text-xs font-medium">
                Find bookings by client, vehicle, status, priority, slot, or booking ID.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="sf-booking-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Search
                </label>

                <input type="text"
                       name="q"
                       value="{{ $q }}"
                       placeholder="Search client, phone, vehicle, booking ID..."
                       class="sf-booking-input h-11 w-full rounded-xl border px-3 text-sm font-semibold transition">
            </div>

            <div>
                <label class="sf-booking-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Status
                </label>

                <select name="status" class="sf-booking-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                    <option value="">All statuses</option>

                    @foreach(['pending', 'scheduled', 'confirmed', 'converted_to_job', 'completed', 'lost'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected($status === $statusOption)>
                            {{ $statusLabel($statusOption) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                @if($bucket)
                    <input type="hidden" name="bucket" value="{{ $bucket }}">
                @endif

                <button type="submit" class="sf-btn-primary w-full">Filter</button>

                @if($bucket || $status || $q)
                    <a href="{{ $clearUrl }}" class="sf-btn-secondary">Reset</a>
                @endif
            </div>
        </div>
    </form>
</div>
