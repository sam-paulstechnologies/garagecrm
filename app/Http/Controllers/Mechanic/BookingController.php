<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index() {
        return view('mechanic.bookings.index');
    }

    public function show($id) {
        return view('mechanic.bookings.show', compact('id'));
    }
}
