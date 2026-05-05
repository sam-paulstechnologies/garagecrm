<?php

namespace App\Listeners\Job;

use App\Events\JobCompleted;
use App\Services\WhatsApp\SendWhatsAppMessage;

class JobCompletedFeedback
{
    public function handle(JobCompleted $event): void
    {
        $job = $event->job;

        if (!$job || !$job->client || !$job->client->phone_norm) {
            return;
        }

        $companyId = (int) ($job->company_id ?? 0);

        if (!$companyId) {
            return;
        }

        (new SendWhatsAppMessage())->fireEvent(
            $companyId,
            'job.done.feedback',
            $job->client->phone_norm,
            [
                'name'       => $job->client->name,
                'job_no'     => $job->job_no ?? $job->job_code ?? $job->id,
                'invoice_no' => optional($job->invoice)->number ?? $job->invoice_no ?? '',
            ]
        );
    }
}