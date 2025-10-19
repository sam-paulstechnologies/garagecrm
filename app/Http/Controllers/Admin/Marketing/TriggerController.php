<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\{Trigger, Campaign};
use Illuminate\Http\Request;

class TriggerController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $items = Trigger::with('campaign:id,name')
            ->where('company_id',$companyId)
            ->latest()
            ->paginate(20);

        return view('admin.marketing.triggers.index', compact('items'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $campaigns = Campaign::where('company_id',$companyId)
            ->orderBy('name')->get(['id','name']);

        // available events (grow this list as you wire listeners)
        $events = [
            'lead.created'        => 'Lead Created',
            'lead.status.changed' => 'Lead Status Changed',
            'booking.created'     => 'Booking Created',
            'schedule.cron'       => 'Scheduled/Cron',
        ];

        return view('admin.marketing.triggers.create', compact('campaigns','events'));
    }

    public function store(Request $r)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $r->validate([
            'name'        => 'required|string|max:160',
            'event'       => 'required|string|max:80',
            'conditions'  => 'nullable|array',
            'campaign_id' => 'required|exists:campaigns,id',
            'status'      => 'required|in:active,paused,archived',
        ]);

        Trigger::create([
            'company_id'  => $companyId,
            'name'        => $data['name'],
            'event'       => $data['event'],
            'conditions'  => $data['conditions'] ?? null,
            'campaign_id' => $data['campaign_id'],
            'status'      => $data['status'],
        ]);

        return redirect()->route('admin.marketing.triggers.index')->with('ok','Trigger created');
    }

    public function edit(Trigger $trigger)
    {
        $this->authorizeCompany($trigger->company_id);

        $companyId = auth()->user()->company_id ?? 1;

        $campaigns = Campaign::where('company_id',$companyId)
            ->orderBy('name')->get(['id','name']);

        $events = [
            'lead.created'        => 'Lead Created',
            'lead.status.changed' => 'Lead Status Changed',
            'booking.created'     => 'Booking Created',
            'schedule.cron'       => 'Scheduled/Cron',
        ];

        return view('admin.marketing.triggers.edit', compact('trigger','campaigns','events'));
    }

    public function update(Request $r, Trigger $trigger)
    {
        $this->authorizeCompany($trigger->company_id);

        $data = $r->validate([
            'name'        => 'required|string|max:160',
            'event'       => 'required|string|max:80',
            'conditions'  => 'nullable|array',
            'campaign_id' => 'required|exists:campaigns,id',
            'status'      => 'required|in:active,paused,archived',
        ]);

        $trigger->update($data);

        return back()->with('ok','Saved');
    }

    public function destroy(Trigger $trigger)
    {
        $this->authorizeCompany($trigger->company_id);
        $trigger->delete();
        return back()->with('ok','Deleted');
    }

    private function authorizeCompany($rowCompanyId): void
    {
        $companyId = auth()->user()->company_id ?? 1;
        abort_if((int)$rowCompanyId !== (int)$companyId, 403);
    }
}
