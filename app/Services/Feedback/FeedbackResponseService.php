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
        |
        | Reason:
        | Current template sends are not always saved as outbound message_logs.
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

        if ($rating === 3) {
            $sent = $this->sendCustomerSessionMessage(
                companyId: $companyId,
                leadId: (int) $lead->id,
                conversationId: $conversationId,
                to: $fromE164,
                body: "Thank you for sharing your feedback, {$this->customerName($lead)}. We appreciate it and will continue working to improve your experience.",
                context: [
                    'action' => 'feedback_neutral_thanks',
                    'rating' => $rating,
                    'job_id' => $jobId,
                    'source' => 'feedback_response_service',
                ]
            );

            if (! $sent) {
                Cache::forget($lockKey);
            }

            return true;
        }

        if (in_array($rating, [4, 5], true)) {
            $reviewLink = $this->googleReviewLink($companyId);

            $body = "Thank you for your feedback, {$this->customerName($lead)}. We are glad you had a good experience.";

            if ($reviewLink) {
                $body .= "\n\nCould you please leave us a Google review here?\n{$reviewLink}";
            } else {
                $body .= "\n\nWe truly appreciate your support.";
            }

            $sent = $this->sendCustomerSessionMessage(
                companyId: $companyId,
                leadId: (int) $lead->id,
                conversationId: $conversationId,
                to: $fromE164,
                body: $body,
                context: [
                    'action' => 'feedback_positive_review_request',
                    'rating' => $rating,
                    'job_id' => $jobId,
                    'google_review_link' => $reviewLink,
                    'source' => 'feedback_response_service',
                ]
            );

            if (! $sent) {
                Cache::forget($lockKey);
            }

            return true;
        }

        return false;
    }

    protected function extractRating(string $text): ?int
    {
        $text = trim($text);

        /*
        |--------------------------------------------------------------------------
        | Supports:
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

        return $this->sendCustomerSessionMessage(
            companyId: $companyId,
            leadId: (int) $lead->id,
            conversationId: $conversationId,
            to: $fromE164,
            body: "Thank you for sharing your feedback, {$this->customerName($lead)}. We are sorry your experience was not ideal. Our manager will review this and contact you shortly.",
            context: [
                'action' => 'feedback_negative_acknowledged',
                'rating' => $rating,
                'job_id' => $jobId,
                'source' => 'feedback_response_service',
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
            | WhatsAppService::sendText signature:
            |--------------------------------------------------------------------------
            | sendText(string $toE164, string $body, array $context = [])
            |
            | company_id must be inside $context.
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
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[FeedbackResponse] Customer feedback session message failed', [
                'company_id' => $companyId,
                'lead_id' => $leadId,
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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