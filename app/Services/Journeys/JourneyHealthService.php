<?php

namespace App\Services\Journeys;

use App\Models\JourneyEnrollment;
use Carbon\Carbon;

class JourneyHealthService
{
    /**
     * Tunables (keep simple for now)
     */
    public int $waitingMinutes = 90;
    public int $stuckMinutes   = 240;

    /**
     * Returns: ['badge' => 'on_track|waiting|stuck|paused|completed', 'label' => '...', 'minutes_since_update' => int]
     */
    public function enrollmentHealth(JourneyEnrollment $e): array
    {
        $status = strtolower((string) ($e->status ?? 'active'));

        if (in_array($status, ['completed', 'done'], true)) {
            return ['badge' => 'completed', 'label' => 'Completed', 'minutes_since_update' => 0];
        }

        if ($status === 'paused') {
            return ['badge' => 'paused', 'label' => 'Paused', 'minutes_since_update' => 0];
        }

        $updatedAt = $e->updated_at ? Carbon::parse($e->updated_at) : null;
        $mins = $updatedAt ? $updatedAt->diffInMinutes(now()) : 999999;

        // Detect "WAIT" hint (your builder already sets _wake_at in context)
        $ctx = is_array($e->context) ? $e->context : [];
        $wakeAt = $ctx['_wake_at'] ?? null;
        if ($wakeAt) {
            // If wake_at is in future, it is waiting (not stuck)
            try {
                $wake = Carbon::parse($wakeAt);
                if ($wake->isFuture()) {
                    return ['badge' => 'waiting', 'label' => 'Waiting (wake scheduled)', 'minutes_since_update' => $mins];
                }
            } catch (\Throwable $t) {
                // ignore parse issues
            }
        }

        if ($mins >= $this->stuckMinutes) {
            return ['badge' => 'stuck', 'label' => 'Stuck', 'minutes_since_update' => $mins];
        }

        if ($mins >= $this->waitingMinutes) {
            return ['badge' => 'waiting', 'label' => 'Waiting', 'minutes_since_update' => $mins];
        }

        return ['badge' => 'on_track', 'label' => 'On Track', 'minutes_since_update' => $mins];
    }
}
