<h2 class="text-lg font-semibold mb-2 flex items-center justify-between">
    <span>Bookings</span>

    @if(Route::has('admin.bookings.create'))
        <a href="{{ route('admin.bookings.create', ['client_id' => $client->id]) }}"
           class="text-sm text-blue-600 underline">
            + Add Booking
        </a>
    @endif
</h2>

@if($client->bookings->isEmpty())
    <p class="text-sm text-gray-500">No bookings yet.</p>
@else
    <div class="space-y-2">
        @foreach($client->bookings as $booking)
            <div class="border rounded p-3 text-sm">
                <div class="flex justify-between">
                    <strong>Booking #{{ $booking->id }}</strong>
                    <span class="text-xs px-2 py-0.5 rounded bg-gray-100">
                        {{ ucfirst($booking->status ?? 'pending') }}
                    </span>
                </div>

                <div class="text-gray-600 mt-1">
                    {{ $booking->scheduled_at?->format('Y-m-d H:i') ?? '—' }}
                </div>

                @if(Route::has('admin.bookings.show'))
                    <a href="{{ route('admin.bookings.show', $booking) }}"
                       class="text-blue-600 underline text-xs mt-1 inline-block">
                        View Booking
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
