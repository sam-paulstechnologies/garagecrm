<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Services\Booking\BookingActionService;
use App\Services\Booking\BookingStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingTransitionController extends Controller
{
    public function __construct(
        private BookingStateService $svc,
        private BookingActionService $actions
    ) {}

    public function store(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['to' => 'required|string|max:40']);
        $to = strtolower(trim((string) $data['to']));

        $companyId = (int) ($request->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        $booking = Booking::where('company_id', $companyId)
            ->findOrFail($id);

        try {
            if ($to === Booking::STATUS_CONVERTED_TO_JOB) {
                $job = $this->actions->convertToJob($booking, (int) $request->user()->id);

                return response()->json([
                    'ok' => true,
                    'id' => $booking->id,
                    'status' => Booking::STATUS_CONVERTED_TO_JOB,
                    'job_id' => $job->id,
                    'job_url' => route('admin.jobs.show', $job),
                    'message' => $job->wasRecentlyCreated
                        ? 'Booking converted and job created.'
                        : 'Booking already converted. Opening existing job.',
                ]);
            }

            if ($to === Booking::STATUS_LOST) {
                $lostData = $request->validate([
                    'lost_reason' => ['required', 'string', 'max:255'],
                    'notes' => ['nullable', 'string', 'max:1000'],
                ]);

                $updated = $this->actions->reject(
                    $booking,
                    (int) $request->user()->id,
                    $lostData['lost_reason'],
                    $lostData['notes'] ?? null
                );

                return response()->json([
                    'ok' => true,
                    'id' => $updated->id,
                    'status' => $updated->status,
                    'lost_reason' => $updated->lost_reason,
                ]);
            }

            $updated = $this->svc->transition($booking, $data['to']);
        } catch (ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage() ?: 'Unable to update booking status.',
            ], 422);
        }

        return response()->json([
            'ok'     => true,
            'id'     => $updated->id,
            'status' => $updated->status,
        ]);
    }
}
