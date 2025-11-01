<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Company\CompanySetting;
use App\Models\Bookings\Booking;   // if you have a Booking model
use App\Models\Leads\Lead;         // if you have a Lead model
use App\Models\Opportunities\Opportunity; // if present
use App\Services\WhatsApp\SendWhatsAppMessage;

class FeedbackController extends Controller
{
    public function index()
    {
        $companyId = (int)(auth()->user()->company_id ?? 0);

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
            'booking_id'     => 'nullable|integer',
            'lead_id'        => 'nullable|integer',
            'opportunity_id' => 'nullable|integer',
            'rating'         => 'required|integer|min:1|max:5',
            'comment'        => 'nullable|string',
        ]);

        $companyId = (int)(auth()->user()->company_id ?? 0) ?: 1;

        // Figure out a phone number to message (best-effort: try booking->client->phone_norm, then lead phone_norm)
        $toPhone = null;
        $clientName = null;

        if (!empty($data['booking_id']) && class_exists(Booking::class)) {
            if ($b = Booking::with('client')->find($data['booking_id'])) {
                $this->abortIfWrongCompany($b->company_id, $companyId);
                $toPhone   = $b->client->phone_norm ?? null;
                $clientName= $b->client->name ?? null;
            }
        } elseif (!empty($data['lead_id']) && class_exists(Lead::class)) {
            if ($l = Lead::with('client')->find($data['lead_id'])) {
                $this->abortIfWrongCompany($l->company_id, $companyId);
                $toPhone   = $l->phone_norm ?? ($l->client->phone_norm ?? null);
                $clientName= $l->client->name ?? $l->name ?? null;
            }
        }

        // Persist feedback
        DB::table('feedback')->insert([
            'company_id'     => $companyId,
            'booking_id'     => $data['booking_id'] ?? null,
            'opportunity_id' => $data['opportunity_id'] ?? null,
            'lead_id'        => $data['lead_id'] ?? null,
            'rating'         => (int)$data['rating'],
            'sentiment'      => $this->sentimentFromRating((int)$data['rating']),
            'comment'        => $data['comment'] ?? null,
            'source'         => 'admin',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Positive? send review request
        if ($toPhone && (int)$data['rating'] >= 4) {
            $set = CompanySetting::where('company_id', $companyId)->first();
            $reviewLink = $set->google_review_link ?? 'https://google.com';

            app(SendWhatsAppMessage::class)->fireEvent(
                $companyId,
                'feedback.positive.review',
                $toPhone,
                ['name' => $clientName ?: 'there', 'review_link' => $reviewLink]
            );
        }

        return redirect()->route('admin.feedback.index')->with('success', 'Feedback recorded.');
    }

    private function sentimentFromRating(int $rating): string
    {
        return $rating >= 4 ? 'positive' : ($rating == 3 ? 'neutral' : 'negative');
    }

    private function abortIfWrongCompany($rowCompanyId, $authedCompanyId): void
    {
        abort_if((int)$rowCompanyId !== (int)$authedCompanyId, 403, 'Wrong company scope.');
    }
}
