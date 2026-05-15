<?php

namespace App\Services\Jobs;

use App\Events\JobCompleted;
use App\Models\Job\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class JobActionService
{
    public function updateStatus(Job $job, string $status, int $actorId): Job
    {
        return DB::transaction(function () use ($job, $status, $actorId) {
            $this->assertJobIsUsable($job);

            $oldStatus = (string) $job->status;

            $allowedStatuses = [
                'pending',
                'in_progress',
                'completed',
                'cancelled',
            ];

            if (! in_array($status, $allowedStatuses, true)) {
                throw new \RuntimeException('Invalid job status selected.');
            }

            $payload = [
                'status' => $status,
                'updated_at' => now(),
            ];

            if ($status === 'in_progress' && Schema::hasColumn('jobs', 'start_time') && empty($job->start_time)) {
                $payload['start_time'] = now();
            }

            if ($status === 'completed' && Schema::hasColumn('jobs', 'end_time')) {
                $payload['end_time'] = now();
            }

            DB::table('jobs')
                ->where('id', $job->id)
                ->where('company_id', $job->company_id)
                ->update($payload);

            $freshJob = Job::with(['client', 'booking'])
                ->where('company_id', $job->company_id)
                ->findOrFail($job->id);

            if ($oldStatus !== 'completed' && $status === 'completed') {
                DB::afterCommit(function () use ($freshJob, $actorId) {
                    event(new JobCompleted($freshJob, $actorId));
                });
            }

            Log::info('[ManagerJob] Job status updated', [
                'job_id' => $freshJob->id,
                'company_id' => $freshJob->company_id,
                'old_status' => $oldStatus,
                'new_status' => $freshJob->status,
                'actor_id' => $actorId,
            ]);

            return $freshJob;
        });
    }

    public function assign(Job $job, ?int $assignedTo, int $actorId): Job
    {
        return DB::transaction(function () use ($job, $assignedTo, $actorId) {
            $this->assertJobIsUsable($job);

            if ($assignedTo) {
                $exists = DB::table('users')
                    ->where('id', $assignedTo)
                    ->where('company_id', $job->company_id)
                    ->exists();

                if (! $exists) {
                    throw new \RuntimeException('Selected team member does not belong to this company.');
                }
            }

            DB::table('jobs')
                ->where('id', $job->id)
                ->where('company_id', $job->company_id)
                ->update([
                    'assigned_to' => $assignedTo,
                    'updated_at' => now(),
                ]);

            $freshJob = Job::with(['client', 'booking', 'assignedUser'])
                ->where('company_id', $job->company_id)
                ->findOrFail($job->id);

            Log::info('[ManagerJob] Job assigned', [
                'job_id' => $freshJob->id,
                'company_id' => $freshJob->company_id,
                'assigned_to' => $assignedTo,
                'actor_id' => $actorId,
            ]);

            return $freshJob;
        });
    }

    public function updateWorkDetails(Job $job, array $data, int $actorId): Job
    {
        return DB::transaction(function () use ($job, $data, $actorId) {
            $this->assertJobIsUsable($job);

            $payload = [
                'updated_at' => now(),
            ];

            foreach ([
                'description',
                'work_summary',
                'issues_found',
                'parts_used',
                'vehicle_mileage',
                'total_time_minutes',
            ] as $field) {
                if (array_key_exists($field, $data) && Schema::hasColumn('jobs', $field)) {
                    $payload[$field] = $data[$field];
                }
            }

            DB::table('jobs')
                ->where('id', $job->id)
                ->where('company_id', $job->company_id)
                ->update($payload);

            $freshJob = Job::with(['client', 'booking', 'assignedUser'])
                ->where('company_id', $job->company_id)
                ->findOrFail($job->id);

            Log::info('[ManagerJob] Job work details updated', [
                'job_id' => $freshJob->id,
                'company_id' => $freshJob->company_id,
                'actor_id' => $actorId,
            ]);

            return $freshJob;
        });
    }

    protected function assertJobIsUsable(Job $job): void
    {
        if ((int) ($job->is_archived ?? 0) === 1) {
            throw new \RuntimeException('Archived job cannot be updated.');
        }
    }
}