<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Company\CompanySetting;
use App\Models\Job\Booking;
use App\Services\WhatsApp\ManagerNotificationService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function index()
    {
        $companyId = $this->companyId();

        $rows = DB::table('feedback')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('admin.feedback.index', compact('rows'));
    }

    public function create()
    {
        return view('admin.feedback.create');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'booking_id'     => ['nullable', 'integer'],
            'lead_id'        => ['nullable', 'integer'],
            'opportunity_id' => ['nullable', 'integer'],
            'rating'         => ['required', 'integer', 'min:1', 'max:5'],
            'comment'        => ['nullable', 'string'],
        ]);

        $companyId = $this->companyId();

        $bookingId = null;
        $leadId = null;
        $opportunityId = null;

        $booking = null;
        $lead = null;
        $opportunity = null;

        $toPhone = null;
        $clientName = null;

        /*
        |--------------------------------------------------------------------------
        | Booking validation — fail closed
        |--------------------------------------------------------------------------
        */

        if (! empty($data['booking_id'])) {
            $booking = Booking::with('client')
                ->where('company_id', $companyId)
                ->find($data['booking_id']);

            abort_if(! $booking, 422, 'Invalid booking for this company.');

            $bookingId = $booking->id;

            $toPhone = $booking->client->phone_norm
                ?? $booking->client->phone
                ?? null;

            $clientName = $booking->client->name ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Lead validation — fail closed
        |--------------------------------------------------------------------------
        */

        if (! empty($data['lead_id'])) {
            $lead = Lead::with('client')
                ->where('company_id', $companyId)
                ->find($data['lead_id']);

            abort_if(! $lead, 422, 'Invalid lead for this company.');

            $leadId = $lead->id;

            if (! $toPhone) {
                $toPhone = $lead->phone_norm
                    ?? $lead->phone
                    ?? ($lead->client->phone_norm ?? null)
                    ?? ($lead->client->phone ?? null);
            }

            if (! $clientName) {
                $clientName = $lead->client->name ?? $lead->name ?? null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Opportunity validation — fail closed
        |--------------------------------------------------------------------------
        */

        if (! empty($data['opportunity_id'])) {
            $opportunity = Opportunity::where('company_id', $companyId)
                ->find($data['opportunity_id']);

            abort_if(! $opportunity, 422, 'Invalid opportunity for this company.');

            $opportunityId = $opportunity->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Try to resolve lead from opportunity / booking if not directly supplied
        |--------------------------------------------------------------------------
        */

        if (! $lead && $opportunity?->lead_id) {
            $lead = Lead::with('client')
                ->where('company_id', $companyId)
                ->find($opportunity->lead_id);

            if ($lead) {
                $leadId = $lead->id;

                if (! $toPhone) {
                    $toPhone = $lead->phone_norm
                        ?? $lead->phone
                        ?? ($lead->client->phone_norm ?? null)
                        ?? ($lead->client->phone ?? null);
                }

                if (! $clientName) {
                    $clientName = $lead->client->name ?? $lead->name ?? null;
                }
            }
        }

        if (! $lead && $booking && ! empty($booking->lead_id)) {
            $lead = Lead::with('client')
                ->where('company_id', $companyId)
                ->find($booking->lead_id);

            if ($lead) {
                $leadId = $lead->id;

                if (! $toPhone) {
                    $toPhone = $lead->phone_norm
                        ?? $lead->phone
                        ?? ($lead->client->phone_norm ?? null)
                        ?? ($lead->client->phone ?? null);
                }

                if (! $clientName) {
                    $clientName = $lead->client->name ?? $lead->name ?? null;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Store feedback
        |--------------------------------------------------------------------------
        */

        $rating = (int) $data['rating'];
        $comment = trim((string) ($data['comment'] ?? ''));

        $feedbackId = DB::table('feedback')->insertGetId([
            'company_id'     => $companyId,
            'booking_id'     => $bookingId,
            'opportunity_id' => $opportunityId,
            'lead_id'        => $leadId,
            'rating'         => $rating,
            'sentiment'      => $this->sentimentFromRating($rating),
            'comment'        => $comment !== '' ? $comment : null,
            'source'         => 'admin',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer feedback reply
        |--------------------------------------------------------------------------
        |
        | Feedback is customer-initiated.
        | So the 24-hour WhatsApp customer service window should be open.
        |
        | Customer reply should use:
        |   WhatsAppService::sendText()
        |
        | Not:
        |   SendWhatsAppMessage::fireEvent()
        |
        */

        if ($toPhone) {
            $this->sendCustomerFeedbackReply(
                companyId: $companyId,
                toPhone: $toPhone,
                clientName: $clientName,
                rating: $rating,
                feedbackId: $feedbackId,
                bookingId: $bookingId,
                leadId: $leadId,
                opportunityId: $opportunityId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Negative feedback manager alert
        |--------------------------------------------------------------------------
        |
        | Rating 1/2:
        | Alert manager using ManagerNotificationService.
        | This remains Meta-template mapped because it is proactive to manager.
        |
        */

        if ($rating <= 2) {
            $this->sendNegativeFeedbackManagerAlert(
                companyId: $companyId,
                feedbackId: $feedbackId,
                rating: $rating,
                comment: $comment,
                lead: $lead,
                bookingId: $bookingId,
                opportunityId: $opportunityId
            );
        }

        return redirect()
            ->route('admin.feedback.index')
            ->with('success', 'Feedback recorded.');
    }

    protected function sendCustomerFeedbackReply(
        int $companyId,
        string $toPhone,
        ?string $clientName,
        int $rating,
        int $feedbackId,
        ?int $bookingId,
        ?int $leadId,
        ?int $opportunityId
    ): void {
        try {
            $name = $clientName ?: 'there';

            $body = $this->feedbackReplyBody(
                companyId: $companyId,
                name: $name,
                rating: $rating
            );

            app(WhatsAppService::class)->sendText(
                $toPhone,
                $body,
                [
                    'company_id'     => $companyId,
                    'feedback_id'    => $feedbackId,
                    'booking_id'     => $bookingId,
                    'lead_id'        => $leadId,
                    'opportunity_id' => $opportunityId,
                    'rating'         => $rating,
                    'source'         => 'feedback_controller',
                    'action'         => 'customer_feedback_reply',
                    'send_mode'      => 'session_message',
                ]
            );

            Log::info('[Feedback] Customer feedback session reply sent', [
                'company_id'  => $companyId,
                'feedback_id' => $feedbackId,
                'lead_id'     => $leadId,
                'booking_id'  => $bookingId,
                'rating'      => $rating,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Feedback] Customer feedback session reply failed', [
                'company_id'  => $companyId,
                'feedback_id' => $feedbackId,
                'lead_id'     => $leadId,
                'booking_id'  => $bookingId,
                'rating'      => $rating,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    protected function feedbackReplyBody(int $companyId, string $name, int $rating): string
    {
        if ($rating >= 4) {
            $set = CompanySetting::where('company_id', $companyId)->first();

            $reviewLink = trim((string) ($set->google_review_link ?? ''));

            $body = "Hi {$name}, thank you for your feedback. We are glad you had a good experience with us.";

            if ($reviewLink !== '') {
                $body .= "\n\nCould you please leave us a Google review here?\n{$reviewLink}";
            }

            return $body;
        }

        if ($rating === 3) {
            return "Hi {$name}, thank you for your feedback. We appreciate you sharing your experience with us. Our team will use it to improve.";
        }

        return "Hi {$name}, thank you for your feedback. We are sorry your experience was not as expected.\n\nOur manager will review this and contact you shortly.";
    }

    protected function sendNegativeFeedbackManagerAlert(
        int $companyId,
        int $feedbackId,
        int $rating,
        string $comment,
        ?Lead $lead,
        ?int $bookingId,
        ?int $opportunityId
    ): void {
        $reason = 'Negative feedback received'
            . " | Rating: {$rating}/5"
            . ($comment !== '' ? " | Comment: {$comment}" : '');

        if (! $lead) {
            Log::warning('[Feedback] Negative feedback has no lead for manager WhatsApp alert', [
                'company_id'     => $companyId,
                'feedback_id'    => $feedbackId,
                'booking_id'     => $bookingId,
                'opportunity_id' => $opportunityId,
                'rating'         => $rating,
            ]);

            return;
        }

        try {
            app(ManagerNotificationService::class)->notifyForLead(
                lead: $lead,
                reason: $reason,
                preferredAt: null,
                bookingId: $bookingId,
                extra: [
                    'source'          => 'feedback_controller',
                    'feedback_id'     => $feedbackId,
                    'rating'          => $rating,
                    'comment'         => $comment,
                    'opportunity_id'  => $opportunityId,
                    'customer_action' => 'negative_feedback',
                ]
            );

            Log::info('[Feedback] Negative feedback manager alert fired', [
                'company_id'  => $companyId,
                'feedback_id' => $feedbackId,
                'lead_id'     => $lead->id,
                'booking_id'  => $bookingId,
                'rating'      => $rating,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Feedback] Negative feedback manager alert failed', [
                'company_id'  => $companyId,
                'feedback_id' => $feedbackId,
                'lead_id'     => $lead->id,
                'booking_id'  => $bookingId,
                'rating'      => $rating,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function companyId(): int
    {
        $companyId = (int) (auth()->user()->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    private function sentimentFromRating(int $rating): string
    {
        return $rating >= 4 ? 'positive' : ($rating === 3 ? 'neutral' : 'negative');
    }
}