<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Carbon\Carbon;

class WhatsAppCampaignController extends Controller
{
    /**
     * List campaigns (stub).
     */
    public function index(): View
    {
        // TODO: replace with real pagination from Campaign model
        $campaigns = []; // e.g., Campaign::latest()->paginate(20);

        return view('admin.whatsapp.campaigns.index', compact('campaigns'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        // TODO: pass templates from WhatsAppTemplate::pluck('name','id')
        return view('admin.whatsapp.campaigns.create');
    }

    /**
     * Persist a new campaign (stub).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'template_id' => ['nullable'],         // wire to templates later
            'audience'    => ['nullable', 'string'],
            'schedule_at' => ['nullable', 'date'],
        ]);

        // TODO: persist campaign + (optional) schedule dispatch job
        // e.g., $campaign = Campaign::create([...]);

        return redirect()
            ->route('admin.whatsapp.campaigns.index')
            ->with('success', 'Campaign saved (stub).');
    }

    /**
     * Show edit form (stub).
     */
    public function edit(int $id): View
    {
        // TODO: $campaign = Campaign::findOrFail($id);
        $campaign = null;

        return view('admin.whatsapp.campaigns.edit', compact('campaign'));
    }

    /**
     * Update campaign (stub).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'template_id' => ['nullable'],
            'audience'    => ['nullable', 'string'],
            'schedule_at' => ['nullable', 'date'],
        ]);

        // TODO: $campaign->update($data);

        return redirect()
            ->route('admin.whatsapp.campaigns.index')
            ->with('success', 'Campaign updated (stub).');
    }

    /**
     * Delete campaign (stub).
     */
    public function destroy(int $id): RedirectResponse
    {
        // TODO: Campaign::findOrFail($id)->delete();

        return redirect()
            ->route('admin.whatsapp.campaigns.index')
            ->with('success', 'Campaign deleted (stub).');
    }

    /**
     * Queue immediate send (stub).
     */
    public function sendNow(int $campaignId): RedirectResponse
    {
        // TODO: dispatch(new SendCampaignNow($campaignId));
        return back()->with('success', 'Campaign queued to send now.');
    }

    /**
     * Schedule campaign for later (stub).
     */
    public function schedule(Request $request, int $campaignId): RedirectResponse
    {
        $data = $request->validate([
            'schedule_at' => ['required', 'date'],
        ]);

        // Normalize to Carbon if you need it later
        $when = Carbon::parse($data['schedule_at']);

        // TODO: save schedule to DB + dispatch delayed job
        // e.g., $campaign->update(['schedule_at' => $when, 'status' => 'scheduled']);

        return back()->with('success', 'Campaign scheduled for '.$when->toDayDateTimeString().'.');
    }

    /**
     * Pause a scheduled/running campaign (stub).
     */
    public function pause(int $campaignId): RedirectResponse
    {
        // TODO: $campaign->update(['status' => 'paused']); cancel queued batches as needed
        return back()->with('success', 'Campaign paused.');
    }

    /**
     * Resume a paused campaign (stub).
     */
    public function resume(int $campaignId): RedirectResponse
    {
        // TODO: $campaign->update(['status' => 'scheduled' or 'running']); requeue remaining audience
        return back()->with('success', 'Campaign resumed.');
    }
}
