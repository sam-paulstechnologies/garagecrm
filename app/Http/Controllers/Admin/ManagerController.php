<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\MessageLog;
use App\Models\Job\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
{
    /**
     * =========================================================
     * 📊 Dashboard (Escalations)
     * =========================================================
     */
    public function dashboard()
    {
        $companyId = Auth::user()->company_id;

        $leads = Lead::with(['client', 'opportunity'])
            ->where('company_id', $companyId)
            ->where('conversation_state', 'human') // 🔥 escalated only
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
        $companyId = Auth::user()->company_id;

        $lead = Lead::with(['client', 'opportunity'])
            ->where('company_id', $companyId)
            ->findOrFail($leadId);

        $messages = MessageLog::where('lead_id', $lead->id)
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
        $companyId = Auth::user()->company_id;

        $lead = Lead::where('company_id', $companyId)
            ->findOrFail($leadId);

        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // 🔥 Send via WhatsApp job
        \App\Jobs\SendWhatsAppFromTemplate::dispatch(
            companyId: $companyId,
            leadId: $lead->id,
            toNumberE164: $lead->phone,
            templateName: 'manual_reply', // 👈 create simple template
            placeholders: [$data['message']],
            context: ['manual' => true],
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
        $companyId = Auth::user()->company_id;

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
        $companyId = Auth::user()->company_id;

        $bookings = Booking::with(['client', 'vehicleData', 'assignedUser'])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->latest()
            ->paginate(20);

        return view('admin.manager.bookings', compact('bookings'));
    }
}