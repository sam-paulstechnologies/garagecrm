<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Services\Booking\BookingStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingTransitionController extends Controller
{
    public function __construct(private BookingStateService $svc) {}

    public function store(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['to' => 'required|string|max:40']);

        $booking = Booking::findOrFail($id);
        abort_if($booking->company_id !== auth()->user()->company_id, 403);

        try {
            $updated = $this->svc->transition($booking, $data['to']);
        } catch (ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'ok'     => true,
            'id'     => $updated->id,
            'status' => $updated->status,
        ]);
    }
}
