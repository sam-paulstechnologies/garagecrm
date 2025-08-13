<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job\Booking;

class CalendarController extends Controller
{
    public function index()
    {
        $events = Booking::where('company_id', auth()->user()->company_id)
            ->where('status', 'confirmed') // Only show confirmed bookings
            ->get()
            ->map(function ($booking) {
                return [
                    'title' => $booking->client->name . ' - ' . $booking->service_type,
                    'start' => $booking->scheduled_at->format('Y-m-d\TH:i:s'),
                    'end'   => $booking->scheduled_end_at?->format('Y-m-d\TH:i:s'), // Optional
                    'url'   => route('bookings.show', $booking->id),
                ];
            });

        return view('admin.calendar.index', [
            'events' => $events->toJson(),
        ]);
    }
}
