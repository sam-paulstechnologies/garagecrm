<?php

namespace App\Services\Booking;

use App\Events\BookingStatusUpdated;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BookingActionService
{
    public function confirm(Booking $booking, int $actorId): Booking
    {
        return DB::transaction(function () use ($booking, $actorId) {
            $this->assertBookingIsUsable($booking);

            DB::table('bookings')
                ->where('id', $booking->id)
                ->where('company_id', $booking->company_id)
                ->update([
                    'status' => 'scheduled',
                    'confirmed_at' => now(),
                    'state_changed_at' => now(),
                    'state_changed_by' => $actorId,
                    'updated_at' => now(),
                ]);

            if ($booking->opportunity_id && Schema::hasTable('opportunities')) {
                DB::table('opportunities')
                    ->where('id', $booking->opportunity_id)
                    ->where('company_id', $booking->company_id)
                    ->update([
                        'stage' => 'appointment',
                        'is_converted' => 0,
                        'expected_close_date' => $booking->expected_close_date ?: $booking->booking_date,
                        'updated_at' => now(),
                    ]);
            }

            $freshBooking = Booking::with(['client', 'opportunity', 'vehicleData.make', 'vehicleData.model'])
                ->where('company_id', $booking->company_id)
                ->findOrFail($booking->id);

            DB::afterCommit(function () use ($freshBooking) {
                event(new BookingStatusUpdated($freshBooking, 'scheduled'));
            });

            Log::info('[ManagerBooking] Booking confirmed', [
                'booking_id' => $freshBooking->id,
                'company_id' => $freshBooking->company_id,
            ]);

            return $freshBooking;
        });
    }

    public function reject(Booking $booking, int $actorId, string $reason, ?string $notes = null): Booking
    {
        return DB::transaction(function () use ($booking, $actorId, $reason, $notes) {
            $this->assertBookingIsUsable($booking);

            $existingNotes = trim((string) ($booking->notes ?? ''));

            $appendNotes = trim(implode("\n", array_filter([
                '',
                'Rejected by manager',
                'Reason: ' . $reason,
                $notes ? 'Notes: ' . $notes : null,
            ])));

            DB::table('bookings')
                ->where('id', $booking->id)
                ->where('company_id', $booking->company_id)
                ->update([
                    'status' => 'lost',
                    'lost_reason' => $reason,
                    'notes' => trim($existingNotes . "\n" . $appendNotes),
                    'cancelled_at' => now(),
                    'state_changed_at' => now(),
                    'state_changed_by' => $actorId,
                    'updated_at' => now(),
                ]);

            if ($booking->opportunity_id && Schema::hasTable('opportunities')) {
                DB::table('opportunities')
                    ->where('id', $booking->opportunity_id)
                    ->where('company_id', $booking->company_id)
                    ->update([
                        'stage' => 'closed_lost',
                        'is_converted' => 0,
                        'close_reason' => $reason,
                        'updated_at' => now(),
                    ]);
            }

            $freshBooking = Booking::with(['client', 'opportunity', 'vehicleData.make', 'vehicleData.model'])
                ->where('company_id', $booking->company_id)
                ->findOrFail($booking->id);

            DB::afterCommit(function () use ($freshBooking) {
                event(new BookingStatusUpdated($freshBooking, 'lost'));
            });

            Log::info('[ManagerBooking] Booking rejected', [
                'booking_id' => $freshBooking->id,
                'company_id' => $freshBooking->company_id,
                'reason' => $reason,
            ]);

            return $freshBooking;
        });
    }

    public function convertToJob(Booking $booking, int $actorId): Job
    {
        return DB::transaction(function () use ($booking, $actorId) {
            $this->assertBookingIsUsable($booking);

            if (empty($booking->client_id)) {
                throw new \RuntimeException('Booking cannot be converted because client is missing.');
            }

            if (empty($booking->vehicle_id)) {
                throw new \RuntimeException('Booking cannot be converted because vehicle is missing.');
            }

            $booking->loadMissing(['opportunity']);

            $lookup = [
                'company_id' => $booking->company_id,
                'booking_id' => $booking->id,
            ];

            $payload = [
                'client_id' => $booking->client_id,
                'assigned_to' => $booking->assigned_to,
                'status' => 'pending',
                'description' => $booking->service_type ?: $booking->name ?: 'Service job',
                'start_time' => now(),
            ];

            if (Schema::hasColumn('jobs', 'lead_id')) {
                $payload['lead_id'] = $booking->lead_id ?: $booking->opportunity?->lead_id;
            }

            if (Schema::hasColumn('jobs', 'opportunity_id')) {
                $payload['opportunity_id'] = $booking->opportunity_id;
            }

            if (Schema::hasColumn('jobs', 'job_code')) {
                $payload['job_code'] = $this->nextJobCode((int) $booking->company_id);
            }

            if (Schema::hasColumn('jobs', 'vehicle_id')) {
                $payload['vehicle_id'] = $booking->vehicle_id;
            }

            $job = Job::firstOrCreate($lookup, $payload);

            DB::table('bookings')
                ->where('id', $booking->id)
                ->where('company_id', $booking->company_id)
                ->update([
                    'status' => 'converted_to_job',
                    'state_changed_at' => now(),
                    'state_changed_by' => $actorId,
                    'updated_at' => now(),
                ]);

            if ($booking->opportunity_id && Schema::hasTable('opportunities')) {
                DB::table('opportunities')
                    ->where('id', $booking->opportunity_id)
                    ->where('company_id', $booking->company_id)
                    ->update([
                        'stage' => Opportunity::STAGE_BOOKING_CONFIRMED,
                        'is_converted' => 1,
                        'close_reason' => null,
                        'expected_close_date' => $booking->expected_close_date ?: $booking->booking_date,
                        'updated_at' => now(),
                    ]);
            }

            Log::info('[ManagerBooking] Booking converted to job', [
                'booking_id' => $booking->id,
                'job_id' => $job->id,
                'company_id' => $booking->company_id,
            ]);

            return $job;
        });
    }

    protected function assertBookingIsUsable(Booking $booking): void
    {
        if ((int) ($booking->is_archived ?? 0) === 1) {
            throw new \RuntimeException('Archived booking cannot be updated.');
        }

        if (in_array((string) $booking->status, ['lost', 'cancelled'], true)) {
            throw new \RuntimeException('Lost/cancelled booking cannot be updated.');
        }
    }

    protected function nextJobCode(int $companyId): string
    {
        $prefix = 'JOB-' . now()->format('Ymd') . '-';

        $latestId = (int) Job::where('company_id', $companyId)->max('id');

        return $prefix . str_pad((string) ($latestId + 1), 4, '0', STR_PAD_LEFT);
    }
}
