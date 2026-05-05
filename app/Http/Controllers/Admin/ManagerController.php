<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Client\Lead;
use App\Models\Job\Booking;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    /**
     * =========================================================
     * 📊 Dashboard (Escalations)
     * =========================================================
     */
    public function dashboard()
    {
        $companyId = $this->companyId();

        $leads = Lead::with(['client', 'opportunity'])
            ->where('company_id', $companyId)
            ->where('conversation_state', 'human')
            ->latest()
            ->get();

        return view('admin.manager.dashboard', compact('leads'));
    }

    /**
     * =========================================================
     * 💬 Conversation View
     * =========================================================
     */
    public function conversation($leadId)
    {
        $companyId = $this->companyId();

        $lead = Lead::with(['client', 'opportunity'])
            ->where('company_id', $companyId)
            ->findOrFail($leadId);

        $messages = MessageLog::where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->orderBy('created_at')
            ->get();

        return view('admin.manager.conversation', compact('lead', 'messages'));
    }

    /**
     * =========================================================
     * 📤 Manager Reply
     * =========================================================
     */
    public function reply(Request $request, $leadId)
    {
        $companyId = $this->companyId();

        $lead = Lead::where('company_id', $companyId)
            ->findOrFail($leadId);

        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $toNumber = $lead->phone_norm ?: $lead->phone;

        abort_if(!$toNumber, 422, 'Lead phone number is missing.');

        SendWhatsAppFromTemplate::dispatch(
            companyId: $companyId,
            leadId: $lead->id,
            toNumberE164: $toNumber,
            templateName: 'manual_reply',
            placeholders: [$data['message']],
            links: [],
            context: [
                'company_id' => $companyId,
                'lead_id' => $lead->id,
                'manual' => true,
                'source' => 'manager_reply',
            ],
            action: 'manual_reply'
        );

        return back()->with('success', 'Reply sent');
    }

    /**
     * =========================================================
     * 🔓 Resume Bot
     * =========================================================
     */
    public function resumeBot($leadId)
    {
        $companyId = $this->companyId();

        $lead = Lead::where('company_id', $companyId)
            ->findOrFail($leadId);

        $lead->update([
            'conversation_state' => 'idle'
        ]);

        return back()->with('success', 'Bot resumed');
    }

    /**
     * =========================================================
     * 📅 Booking List (Manager View)
     * =========================================================
     */
    public function bookings()
    {
        $companyId = $this->companyId();

        $bookings = Booking::with(['client', 'vehicleData', 'assignedUser'])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->latest()
            ->paginate(20);

        return view('admin.manager.bookings', compact('bookings'));
    }
}