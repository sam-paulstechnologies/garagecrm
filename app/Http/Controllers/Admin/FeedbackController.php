<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Company\CompanySetting;
use App\Models\Job\Booking;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function index()
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);

        abort_if(!$companyId, 403);

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

        $companyId = (int)(auth()->user()->company_id ?? 0);

        abort_if(!$companyId, 403);

        $bookingId = null;
        $leadId = null;
        $opportunityId = null;

        $toPhone = null;
        $clientName = null;

        /*
        |--------------------------------------------------------------------------
        | Booking validation — fail closed
        |--------------------------------------------------------------------------
        */
        if (!empty($data['booking_id'])) {
            $booking = Booking::with('client')
                ->where('company_id', $companyId)
                ->find($data['booking_id']);

            abort_if(!$booking, 422, 'Invalid booking for this company.');

            $bookingId = $booking->id;
            $toPhone = $booking->client->phone_norm ?? $booking->client->phone ?? null;
            $clientName = $booking->client->name ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Lead validation — fail closed
        |--------------------------------------------------------------------------
        */
        if (!empty($data['lead_id'])) {
            $lead = Lead::with('client')
                ->where('company_id', $companyId)
                ->find($data['lead_id']);

            abort_if(!$lead, 422, 'Invalid lead for this company.');

            $leadId = $lead->id;

            if (!$toPhone) {
                $toPhone = $lead->phone_norm
                    ?? $lead->phone
                    ?? ($lead->client->phone_norm ?? null)
                    ?? ($lead->client->phone ?? null);
            }

            if (!$clientName) {
                $clientName = $lead->client->name ?? $lead->name ?? null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Opportunity validation — fail closed
        |--------------------------------------------------------------------------
        */
        if (!empty($data['opportunity_id'])) {
            $opportunity = Opportunity::where('company_id', $companyId)
                ->find($data['opportunity_id']);

            abort_if(!$opportunity, 422, 'Invalid opportunity for this company.');

            $opportunityId = $opportunity->id;
        }

        DB::table('feedback')->insert([
            'company_id'     => $companyId,
            'booking_id'     => $bookingId,
            'opportunity_id' => $opportunityId,
            'lead_id'        => $leadId,
            'rating'         => (int) $data['rating'],
            'sentiment'      => $this->sentimentFromRating((int) $data['rating']),
            'comment'        => $data['comment'] ?? null,
            'source'         => 'admin',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        if ($toPhone && (int) $data['rating'] >= 4) {
            $set = CompanySetting::where('company_id', $companyId)->first();
            $reviewLink = $set->google_review_link ?? 'https://google.com';

            app(SendWhatsAppMessage::class)->fireEvent(
                $companyId,
                'feedback.positive.review',
                $toPhone,
                [
                    'name' => $clientName ?: 'there',
                    'review_link' => $reviewLink,
                ]
            );
        }

        return redirect()
            ->route('admin.feedback.index')
            ->with('success', 'Feedback recorded.');
    }

    private function sentimentFromRating(int $rating): string
    {
        return $rating >= 4 ? 'positive' : ($rating === 3 ? 'neutral' : 'negative');
    }
}