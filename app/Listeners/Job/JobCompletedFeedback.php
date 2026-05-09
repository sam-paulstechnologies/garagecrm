<?php

namespace App\Listeners\Job;

use App\Events\JobCompleted;
use App\Models\CompanySetting;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JobCompletedFeedback
{
    public function handle(JobCompleted $event): void
    {
        $job = $event->job?->fresh([
            'client',
            'invoice',
            'invoices',
        ]);

        if (! $job) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate protection
        |--------------------------------------------------------------------------
        | Prevent duplicate feedback messages if JobCompleted fires twice.
        | If sending fails, we clear the lock so it can be retried after fixing.
        |--------------------------------------------------------------------------
        */

        $lockKey = 'job_completed_feedback_sent_' . $job->id;

        if (! Cache::add($lockKey, true, now()->addHours(24))) {
            Log::info('Job completed feedback skipped: duplicate lock active', [
                'job_id' => $job->id,
                'company_id' => $job->company_id ?? null,
            ]);

            return;
        }

        $client = $job->client;

        if (! $client) {
            Cache::forget($lockKey);

            Log::warning('Job completed feedback skipped: client missing', [
                'job_id' => $job->id,
                'client_id' => $job->client_id,
            ]);

            return;
        }

        $phone = $client->phone_norm
            ?? $client->phone
            ?? $client->whatsapp
            ?? $client->mobile
            ?? null;

        if (! $phone) {
            Cache::forget($lockKey);

            Log::warning('Job completed feedback skipped: client phone missing', [
                'job_id' => $job->id,
                'client_id' => $client->id,
            ]);

            return;
        }

        $companyId = (int) ($job->company_id ?? 0);

        if (! $companyId) {
            Cache::forget($lockKey);

            Log::warning('Job completed feedback skipped: company missing', [
                'job_id' => $job->id,
            ]);

            return;
        }

        $invoice = $job->invoice ?? $job->invoices?->first();

        $invoiceNo = $invoice?->invoice_number
            ?? $invoice?->number
            ?? $job->invoice_no
            ?? '';

        $invoiceUrl = $event->invoiceUrl;

        if (! $invoiceUrl) {
            if (method_exists($job, 'invoiceUrl')) {
                $invoiceUrl = $job->invoiceUrl();
            } elseif (! empty($job->invoice_url)) {
                $invoiceUrl = $job->invoice_url;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Template variables expected by job_done_feedback_v1
        |--------------------------------------------------------------------------
        | DB mapping:
        | ["name", "job_no", "garage"]
        |--------------------------------------------------------------------------
        */

        $customerName = $client->name ?: 'Customer';

        $jobNo = $job->job_no
            ?? $job->job_code
            ?? $job->id;

        $garage = $this->companySetting(
            companyId: $companyId,
            key: 'garage_name',
            fallback: config('app.name', 'Garage')
        );

        try {
            app(SendWhatsAppMessage::class)->fireEvent(
                $companyId,
                'job.done.feedback',
                (string) $phone,
                [
                    /*
                    |--------------------------------------------------------------------------
                    | Exact template variables
                    |--------------------------------------------------------------------------
                    */

                    'name' => $customerName,
                    'customer_name' => $customerName,

                    'job_no' => (string) $jobNo,
                    'job_code' => (string) $jobNo,

                    'garage' => $garage,
                    'garage_name' => $garage,

                    /*
                    |--------------------------------------------------------------------------
                    | Extra context variables
                    |--------------------------------------------------------------------------
                    */

                    'invoice_no' => $invoiceNo,
                    'invoice_url' => $invoiceUrl,

                    'company_id' => $companyId,
                    'job_id' => $job->id,
                    'client_id' => $client->id,
                    'invoice_id' => $invoice?->id,

                    'event_key' => 'job.done.feedback',
                    'source' => 'job_completed_feedback_listener',
                    'action' => 'job_completed_feedback',
                    'send_mode' => 'meta_template',
                ]
            );

            Log::info('Job completed feedback WhatsApp triggered', [
                'job_id' => $job->id,
                'client_id' => $client->id,
                'company_id' => $companyId,
                'phone' => $phone,
                'event_key' => 'job.done.feedback',
                'template_vars' => [
                    'name' => $customerName,
                    'job_no' => (string) $jobNo,
                    'garage' => $garage,
                ],
            ]);
        } catch (\Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Clear lock on failure
            |--------------------------------------------------------------------------
            | Important because Meta/template issues can be fixed and retried.
            |--------------------------------------------------------------------------
            */

            Cache::forget($lockKey);

            Log::error('Job completed feedback WhatsApp failed', [
                'job_id' => $job->id,
                'client_id' => $client->id,
                'company_id' => $companyId,
                'phone' => $phone,
                'event_key' => 'job.done.feedback',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function companySetting(int $companyId, string $key, string $fallback): string
    {
        try {
            $value = CompanySetting::query()
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');

            $value = trim((string) $value);

            return $value !== '' ? $value : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}