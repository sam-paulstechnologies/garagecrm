<?php

namespace App\Observers;

use App\Models\Job\Job;
use Illuminate\Support\Facades\Log;

class JobObserver
{
    /*
    |--------------------------------------------------------------------------
    | Job Observer
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | JobCompleted event should NOT be dispatched from this observer anymore.
    |
    | Reason:
    | Admin JobController can also dispatch JobCompleted when a job is marked
    | completed. If this observer also dispatches it, customer feedback WhatsApp
    | can be sent twice.
    |
    | Job completion event dispatch should be handled from one controlled place.
    | For now, JobController will remain the source of truth.
    |
    */

    protected const COMPLETE_FIELDS = [
        'is_completed',
        'completed',
        'completed_at',
    ];

    public function updated(Job $job): void
    {
        foreach (self::COMPLETE_FIELDS as $field) {
            if (! $job->wasChanged($field)) {
                continue;
            }

            $value = $job->getAttribute($field);
            $done = $field === 'completed_at' ? ! empty($value) : (bool) $value;

            if ($done) {
                Log::info('[JobObserver] Job completion observed but JobCompleted dispatch skipped', [
                    'job_id'     => $job->id,
                    'company_id' => $job->company_id ?? null,
                    'field'      => $field,
                    'reason'     => 'JobController is the authoritative JobCompleted dispatcher',
                ]);
            }

            break;
        }
    }
}