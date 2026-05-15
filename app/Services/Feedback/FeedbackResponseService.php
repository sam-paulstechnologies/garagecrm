<?php

namespace App\Services\Feedback;

use App\Models\Client\Lead;
use App\Models\CompanySetting;
use App\Models\Job\Job;
use App\Models\MessageLog;
use App\Models\User;
use App\Notifications\ManagerLeadHandoffNotification;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeedbackResponseService
{
    public function handleIfFeedbackReply(
        int $companyId,
        Lead $lead,
        string $text,
        string $fromE164,
        ?int $conversationId = null
    ): bool {
        $rating = $this->extractRating($text);

        if (! $rating) {
            Log::info('[FeedbackResponse] Not a rating reply', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'text' => $text,
            ]);

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Find Feedback Context
        |--------------------------------------------------------------------------
        | Primary: outbound feedback message in message_logs.
        | Fallback: latest completed job for the same client.
        |--------------------------------------------------------------------------
        */

        $feedbackMessage = $this->latestFeedbackRequest($companyId, (int) $lead->id);
        $job = $this->latestCompletedJobForLead($companyId, $lead);

        $jobId = $feedbackMessage?->meta['job_id'] ?? $job?->id ?? null;

        if (! $jobId && ! $job) {
            Log::info('[FeedbackResponse] Rating found but no feedback context found', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'client_id' => $lead->client_id,
                'rating' => $rating,
                'text' => $text,
            ]);

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Last Action Lock
        |--------------------------------------------------------------------------
        | This prevents the same feedback reply from being processed repeatedly.
        | We keep cache lock here because we have not added the DB lock migration yet.
        |--------------------------------------------------------------------------
        */

        $lockKey = 'feedback_response_handled_' .
            $companyId . '_' .
            $lead->id . '_' .
            ($jobId ?: 'latest');

        if (! Cache::add($lockKey, true, now()->addDays(14))) {
            Log::info('[FeedbackResponse] Duplicate feedback reply skipped', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'rating' => $rating,
                'job_id' => $jobId,
            ]);

            return true;
        }

        Log::info('[FeedbackResponse] Feedback rating received', [
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'client_id' => $lead->client_id,
            'rating' => $rating,
            'job_id' => $jobId,
            'text' => $text,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Rating 1 / 2
        |--------------------------------------------------------------------------
        | Escalate to manager and acknowledge customer.
        |--------------------------------------------------------------------------
        */

        if (in_array($rating, [1, 2], true)) {
            $sent = $this->handleNegativeFeedback(
                companyId: $companyId,
                lead: $lead,
                fromE164: $fromE164,
                rating: $rating,
                jobId: $jobId,
                conversationId: $conversationId
            );

            if (! $sent) {
                Cache::forget($lockKey);
            }

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Rating 3
        |--------------------------------------------------------------------------
        | Neutral thank-you response.
        |--------------------------------------------------------------------------
        */

        if ($rating === 3) {
            $body = $this->neutralThankYouBody($lead);

            $sent = $this->sendCustomerSessionMessage(
                companyId: $companyId,
                leadId: (int) $lead->id,
                conversationId: $conversationId,
                to: $fromE164,
                body: $body,
                context: [
                    'event_key' => 'feedback.neutral.thanks',
                    'template_hint' => 'feedback_neutral_thanks_v1',
                    'action' => 'feedback_neutral_thanks',
                    'rating' => $rating,
                    'job_id' => $jobId,
                    'source' => 'feedback_response_service',
                    'send_mode' => 'session_message',
                ]
            );

            if (! $sent) {
                Cache::forget($lockKey);
            }

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Rating 4 / 5
        |--------------------------------------------------------------------------
        | Ask for Google review when link exists.
        |--------------------------------------------------------------------------
        */

        if (in_array($rating, [4, 5], true)) {
            $reviewLink = $this->googleReviewLink($companyId);
            $body = $this->positiveReviewBody($lead, $reviewLink);

            $sent = $this->sendCustomerSessionMessage(
                companyId: $companyId,
                leadId: (int) $lead->id,
                conversationId: $conversationId,
                to: $fromE164,
                body: $body,
                context: [
                    'event_key' => 'feedback.positive.review',
                    'template_hint' => 'feedback_positive_review_v1',
                    'action' => 'feedback_positive_review_request',
                    'rating' => $rating,
                    'job_id' => $jobId,
                    'google_review_link' => $reviewLink,
                    'source' => 'feedback_response_service',
                    'send_mode' => 'session_message',
                ]
            );

            if (! $sent) {
                Cache::forget($lockKey);
            }

            return true;
        }

        Cache::forget($lockKey);

        return false;
    }

    protected function extractRating(string $text): ?int
    {
        $text = trim($text);

        /*
        |--------------------------------------------------------------------------
        | Supports:
        |--------------------------------------------------------------------------
        | 5
        | 5 - Excellent
        | Rating 5
        | 5 a
        |--------------------------------------------------------------------------
        */

        if (preg_match('/^\s*([1-5])\b/u', $text, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\b([1-5])\b/u', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function latestFeedbackRequest(int $companyId, int $leadId): ?MessageLog
    {
        return MessageLog::query()
            ->where('company_id', $companyId)
            ->where('lead_id', $leadId)
            ->where('direction', 'out')
            ->where('channel', 'whatsapp')
            ->where('created_at', '>=', now()->subDays(14))
            ->where(function ($query) {
                $query->where('template', 'job.done.feedback')
                    ->orWhere('template', 'job_done_feedback_v1')
                    ->orWhere('body', 'like', '%feedback%')
                    ->orWhere('body', 'like', '%rate%')
                    ->orWhere('body', 'like', '%rating%')
                    ->orWhere('meta->event_key', 'job.done.feedback')
                    ->orWhere('meta->action', 'job_completed_feedback');
            })
            ->latest('id')
            ->first();
    }

    protected function latestCompletedJobForLead(int $companyId, Lead $lead): ?Job
    {
        if (! $lead->client_id) {
            return null;
        }

        return Job::query()
            ->where('company_id', $companyId)
            ->where('client_id', $lead->client_id)
            ->where('status', 'completed')
            ->where('updated_at', '>=', now()->subDays(14))
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    protected function handleNegativeFeedback(
        int $companyId,
        Lead $lead,
        string $fromE164,
        int $rating,
        mixed $jobId,
        ?int $conversationId = null
    ): bool {
        $reason = "Customer gave low feedback rating {$rating}/5. Manager should call the customer.";

        /*
        |--------------------------------------------------------------------------
        | Move lead to human handoff
        |--------------------------------------------------------------------------
        */

        try {
            $data = $lead->conversation_data ?? [];
            $data = is_array($data) ? $data : [];

            $lead->conversation_state = 'human';
            $lead->conversation_data = array_merge($data, [
                'is_escalated' => true,
                'escalated_at' => now()->toIso8601String(),
                'escalation_reason' => $reason,
                'feedback_rating' => $rating,
                'feedback_job_id' => $jobId,
                'last_escalation_source' => 'feedback_response_service',
            ]);
            $lead->conversation_updated_at = now();
            $lead->save();
        } catch (\Throwable $e) {
            Log::warning('[FeedbackResponse] Failed to move lead to human state', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'rating' => $rating,
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Notify manager
        |--------------------------------------------------------------------------
        */

        try {
            $targets = collect();

            if ($lead->assigned_to) {
                $assigned = User::query()
                    ->where('company_id', $companyId)
                    ->where('id', $lead->assigned_to)
                    ->first();

                if ($assigned) {
                    $targets->push($assigned);
                }
            }

            if ($targets->isEmpty()) {
                $targets = User::query()
                    ->where('company_id', $companyId)
                    ->whereIn('role', ['admin', 'manager'])
                    ->get();
            }

            foreach ($targets->unique('id') as $user) {
                $user->notify(new ManagerLeadHandoffNotification(
                    companyId: $companyId,
                    leadId: $lead->id,
                    name: $lead->name ?? 'Customer',
                    phone: $fromE164,
                    source: 'WhatsApp Feedback',
                    reason: $reason
                ));
            }

            Log::warning('[FeedbackResponse] Negative feedback manager alert created', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'rating' => $rating,
                'job_id' => $jobId,
                'event_key' => 'feedback.negative.manager_alert',
            ]);
        } catch (\Throwable $e) {
            Log::error('[FeedbackResponse] Negative feedback manager alert failed', [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'rating' => $rating,
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Customer acknowledgement
        |--------------------------------------------------------------------------
        */

        return $this->sendCustomerSessionMessage(
            companyId: $companyId,
            leadId: (int) $lead->id,
            conversationId: $conversationId,
            to: $fromE164,
            body: $this->negativeFeedbackBody($lead),
            context: [
                'event_key' => 'feedback.negative.manager_alert',
                'template_hint' => 'feedback_negative_manager_alert_v1',
                'action' => 'feedback_negative_acknowledged',
                'rating' => $rating,
                'job_id' => $jobId,
                'source' => 'feedback_response_service',
                'send_mode' => 'session_message',
            ]
        );
    }

    protected function sendCustomerSessionMessage(
        int $companyId,
        int $leadId,
        ?int $conversationId,
        string $to,
        string $body,
        array $context = []
    ): bool {
        try {
            /*
            |--------------------------------------------------------------------------
            | Session Message
            |--------------------------------------------------------------------------
            | This is triggered after customer replies to a feedback template.
            | So it is safe to send as normal WhatsApp session message.
            |
            | Hardcoded body remains as fallback text.
            | Context carries template/event hints for logging and later migration.
            |--------------------------------------------------------------------------
            */

            $context = array_merge($context, [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'conversation_id' => $conversationId,
                'send_mode' => 'session_message',
                'source' => $context['source'] ?? 'feedback_response_service',
            ]);

            app(WhatsAppService::class)->sendText(
                $to,
                $body,
                $context
            );

            Log::info('[FeedbackResponse] Customer feedback session message sent', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'conversation_id' => $conversationId,
                'action' => $context['action'] ?? null,
                'event_key' => $context['event_key'] ?? null,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[FeedbackResponse] Customer feedback session message failed', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'conversation_id' => $conversationId,
                'event_key' => $context['event_key'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function neutralThankYouBody(Lead $lead): string
    {
        return "Thank you for sharing your feedback, {$this->customerName($lead)}. "
            . "We appreciate it and will continue working to improve your experience.";
    }

    protected function positiveReviewBody(Lead $lead, ?string $reviewLink): string
    {
        $body = "Thank you for your feedback, {$this->customerName($lead)}. "
            . "We are glad you had a good experience.";

        if ($reviewLink) {
            $body .= "\n\nCould you please leave us a Google review here?\n{$reviewLink}";
        } else {
            $body .= "\n\nWe truly appreciate your support.";
        }

        return $body;
    }

    protected function negativeFeedbackBody(Lead $lead): string
    {
        return "Thank you for sharing your feedback, {$this->customerName($lead)}. "
            . "We are sorry your experience was not ideal. "
            . "Our manager will review this and contact you shortly.";
    }

    protected function customerName(Lead $lead): string
    {
        return $lead->name ?: 'Customer';
    }

    protected function googleReviewLink(int $companyId): ?string
    {
        try {
            $link = CompanySetting::query()
                ->where('company_id', $companyId)
                ->where('key', 'google_review_link')
                ->value('value');

            $link = trim((string) $link);

            if ($link !== '') {
                return $link;
            }
        } catch (\Throwable $e) {
            Log::warning('[FeedbackResponse] Google review link lookup failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }

        return config('services.google.review_link')
            ?: env('GOOGLE_REVIEW_LINK')
            ?: null;
    }
}