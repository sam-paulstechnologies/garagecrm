<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use Illuminate\Http\JsonResponse;

class BookingSummaryController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['client','opportunity','vehicleData','assignedUser'])->findOrFail($id);

        abort_if($booking->company_id !== auth()->user()->company_id, 403);

        return response()->json([
            'id'           => $booking->id,
            'name'         => $booking->name,
            'status'       => $booking->status,
            'service_type' => $booking->service_type,
            'slot'         => $booking->slot,
            'booking_date' => $booking->booking_date,
            'expected_close_date' => $booking->expected_close_date,
            'client' => [
                'id'    => $booking->client?->id,
                'name'  => $booking->client?->name,
                'phone' => $booking->client?->phone,
            ],
            'opportunity' => [
                'id'    => $booking->opportunity?->id,
                'stage' => $booking->opportunity?->stage,
                'title' => $booking->opportunity?->title,
            ],
            'vehicle' => [
                'id'    => $booking->vehicleData?->id,
                'plate' => $booking->vehicleData?->plate_number,
                'vin'   => $booking->vehicleData?->vin,
                'year'  => $booking->vehicleData?->year,
                'color' => $booking->vehicleData?->color,
            ],
            'assigned_user' => [
                'id'   => $booking->assignedUser?->id,
                'name' => $booking->assignedUser?->name,
            ],
            'pickup' => [
                'required'       => (bool) $booking->pickup_required,
                'address'        => $booking->pickup_address,
                'contact_number' => $booking->pickup_contact_number,
            ],
            'notes'      => $booking->notes,
            'created_at' => $booking->created_at,
            'updated_at' => $booking->updated_at,
        ]);
    }
}
