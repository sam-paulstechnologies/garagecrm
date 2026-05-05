<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use Illuminate\Http\Request;

class ClientBookingController extends Controller
{
    public function index(Client $client)
    {
        abort_unless((int) $client->company_id === (int) auth()->user()->company_id, 403);

        // Load bookings relationship if defined
        $bookings = $client->bookings; // Make sure bookings() relationship exists in Client model

        return view('clients.bookings.index', compact('client', 'bookings'));
    }
}