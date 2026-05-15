<?php

namespace App\Listeners\Job;

use App\Events\JobCompleted;
use App\Models\CompanySetting;
use App\Models\MessageLog;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

        $companyId = (int) ($job->company_id ?? 0);

        if (! $companyId) {
            Log::warning('Job completed feedback skipped: company missing', [
                'job_id' => $job->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Durable + Cache Duplicate Protection
        |--------------------------------------------------------------------------
        | Old issue:
        | The previous code used a 24-hour cache only.
        | If JobCompleted fires again after 24 hours, feedback may resend.
        |
        | New behavior:
        | 1. Check safe persistent markers if columns exist.
        | 2. Check message_logs if available.
        | 3. Use cache as runtime lock.
        |--------------------------------------------------------------------------
        */

        if ($this->feedbackAlreadyRequested($job, $companyId)) {
            Log::info('Job completed feedback skipped: already requested earlier', [
                'job_id' => $job->id,
                'company_id' => $companyId,
                'event_key' => 'job.done.feedback',
            ]);

            return;
        }

        $lockKey = 'job_completed_feedback_sent_' . $companyId . '_' . $job->id;

        if (! Cache::add($lockKey, true, now()->addDays(14))) {
            Log::info('Job completed feedback skipped: duplicate lock active', [
                'job_id' => $job->id,
                'company_id' => $companyId,
                'event_key' => 'job.done.feedback',
            ]);

            return;
        }

        $client = $job->client;

        if (! $client) {
            Cache::forget($lockKey);

            Log::warning('Job completed feedback skipped: client missing', [
                'job_id' => $job->id,
                'client_id' => $job->client_id,
                'company_id' => $companyId,
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
                'company_id' => $companyId,
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
                    'lock_key' => $lockKey,
                ]
            );

            $this->markFeedbackRequested($job);

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

    protected function feedbackAlreadyRequested($job, int $companyId): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Schema-safe persistent checks
        |--------------------------------------------------------------------------
        | This allows the code to work even if the project DB does not yet have
        | the feedback marker columns.
        |--------------------------------------------------------------------------
        */

        try {
            $table = $job->getTable();

            if (Schema::hasColumn($table, 'feedback_requested_at') && ! empty($job->feedback_requested_at)) {
                return true;
            }

            if (Schema::hasColumn($table, 'feedback_sent_at') && ! empty($job->feedback_sent_at)) {
                return true;
            }

            if (Schema::hasColumn($table, 'feedback_request_sent_at') && ! empty($job->feedback_request_sent_at)) {
                return true;
            }
        } catch (\Throwable $e) {
            Log::debug('Job feedback persistent marker check skipped', [
                'job_id' => $job->id ?? null,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Message log fallback
        |--------------------------------------------------------------------------
        | If outbound template/session messages are logged in message_logs,
        | this gives us another durable duplicate check.
        |--------------------------------------------------------------------------
        */

        try {
            if (Schema::hasTable('message_logs')) {
                return MessageLog::query()
                    ->where('company_id', $companyId)
                    ->where('direction', 'out')
                    ->where('channel', 'whatsapp')
                    ->where('created_at', '>=', now()->subDays(90))
                    ->where(function ($query) use ($job) {
                        $query->where('meta->job_id', $job->id)
                            ->orWhere('body', 'like', '%' . $job->id . '%');
                    })
                    ->where(function ($query) {
                        $query->where('template', 'job.done.feedback')
                            ->orWhere('template', 'job_done_feedback_v1')
                            ->orWhere('meta->event_key', 'job.done.feedback')
                            ->orWhere('meta->action', 'job_completed_feedback');
                    })
                    ->exists();
            }
        } catch (\Throwable $e) {
            Log::debug('Job feedback message_log duplicate check skipped', [
                'job_id' => $job->id ?? null,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function markFeedbackRequested($job): void
    {
        try {
            $table = $job->getTable();
            $updates = [];

            if (Schema::hasColumn($table, 'feedback_requested_at')) {
                $updates['feedback_requested_at'] = now();
            }

            if (Schema::hasColumn($table, 'feedback_sent_at')) {
                $updates['feedback_sent_at'] = now();
            }

            if (Schema::hasColumn($table, 'feedback_request_sent_at')) {
                $updates['feedback_request_sent_at'] = now();
            }

            if (! empty($updates)) {
                $job->forceFill($updates)->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Job feedback marker update failed', [
                'job_id' => $job->id ?? null,
                'company_id' => $job->company_id ?? null,
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