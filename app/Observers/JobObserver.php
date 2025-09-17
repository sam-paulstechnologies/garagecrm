<?php

namespace App\Observers;

use App\Models\Job\Job;
use App\Events\JobCompleted;

class JobObserver
{
    // Support either boolean or timestamp style
    protected const COMPLETE_FIELDS = ['is_completed', 'completed', 'completed_at'];

    public function updated(Job $job): void
    {
        foreach (self::COMPLETE_FIELDS as $f) {
            if ($job->isDirty($f) || $job->wasChanged($f)) {
                $val = $job->getAttribute($f);
                $done = $f === 'completed_at' ? (bool) $val : (bool) $val;
                if ($done) {
                    // Try to expose invoice url/id if the model has it
                    $invoiceUrl = method_exists($job, 'invoiceUrl') ? $job->invoiceUrl() : ($job->invoice_url ?? null);
                    event(new JobCompleted($job, $invoiceUrl));
                }
                break;
            }
        }
    }
}
