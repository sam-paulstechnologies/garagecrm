<?php

namespace App\Services\Journeys;

use App\Models\JourneyAction;
use App\Models\JourneyEnrollment;
use Illuminate\Support\Facades\DB;

class JourneyActionService
{
    public function pause(JourneyEnrollment $e, int $actorUserId, string $reason = ''): void
    {
        DB::transaction(function () use ($e, $actorUserId, $reason) {
            $e->status = 'paused';
            $e->save();

            $this->log($e, $actorUserId, 'pause', [
                'reason' => $reason,
                'current_step_position' => $e->current_step_position,
            ]);
        });
    }

    public function resume(JourneyEnrollment $e, int $actorUserId, string $reason = ''): void
    {
        DB::transaction(function () use ($e, $actorUserId, $reason) {
            $e->status = 'active';
            $e->save();

            $this->log($e, $actorUserId, 'resume', [
                'reason' => $reason,
                'current_step_position' => $e->current_step_position,
            ]);
        });
    }

    public function skipStep(JourneyEnrollment $e, int $actorUserId, string $reason = ''): void
    {
        DB::transaction(function () use ($e, $actorUserId, $reason) {
            $maxPos = (int) ($e->journey?->steps?->max('position') ?? $e->current_step_position);
            $old = (int) $e->current_step_position;

            $e->current_step_position = min($old + 1, $maxPos);
            $e->status = $e->status ?: 'active';
            $e->save();

            $this->log($e, $actorUserId, 'skip_step', [
                'reason' => $reason,
                'from' => $old,
                'to'   => (int) $e->current_step_position,
                'max'  => $maxPos,
            ]);
        });
    }

    public function forceAdvanceTo(JourneyEnrollment $e, int $actorUserId, int $position, string $reason = ''): void
    {
        DB::transaction(function () use ($e, $actorUserId, $position, $reason) {
            $maxPos = (int) ($e->journey?->steps?->max('position') ?? $position);
            $old = (int) $e->current_step_position;

            $pos = max(0, min($position, $maxPos));
            $e->current_step_position = $pos;
            $e->status = $e->status ?: 'active';
            $e->save();

            $this->log($e, $actorUserId, 'force_advance', [
                'reason' => $reason,
                'from' => $old,
                'to'   => $pos,
                'max'  => $maxPos,
            ]);
        });
    }

    private function log(JourneyEnrollment $e, int $actorUserId, string $action, array $payload = []): void
    {
        JourneyAction::create([
            'company_id'     => (int) $e->company_id,
            'journey_id'     => (int) $e->journey_id,
            'enrollment_id'  => (int) $e->id,
            'actor_user_id'  => $actorUserId,
            'action'         => $action,
            'payload'        => $payload,
            'created_at'     => now(),
        ]);
    }
}
