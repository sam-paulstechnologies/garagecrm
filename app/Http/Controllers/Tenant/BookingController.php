<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant\Booking;

class BookingController extends Controller
{
    public function calendar()
    {
        $companyId = Auth::user()->company_id;

        $bookings = Booking::where('company_id', $companyId)->get()->map(function ($booking) {
            return [
                'title' => $booking->vehicle ?? 'Booking',
                'start' => $booking->date,
                'end'   => $booking->date,
                'status' => $booking->status,
            ];
        });

        return view('calendar', ['bookings' => $bookings]);
    }
}
