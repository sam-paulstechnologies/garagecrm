<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Jobs\PlatformMarketing\DispatchPlatformCampaign;
use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Models\PlatformMarketing\PlatformMarketingSegment;
use App\Services\PlatformMarketing\CampaignSafetyService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        return view('super_admin.marketing.campaigns.index', [
            'campaigns' => PlatformMarketingCampaign::withCount('recipients')->latest()->paginate(20),
            'buckets' => PlatformMarketingCampaign::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create()
    {
        return view('super_admin.marketing.campaigns.form', [
            'campaign' => new PlatformMarketingCampaign(['status' => 'draft', 'batch_size' => 25, 'delay_between_batches' => 300, 'daily_cap' => 100]),
            'segments' => PlatformMarketingSegment::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $campaign = PlatformMarketingCampaign::query()->create($this->validated($request) + [
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return redirect()->route('super-admin.marketing.campaigns.show', $campaign)->with('success', 'Campaign draft created.');
    }

    public function show(PlatformMarketingCampaign $campaign)
    {
        return view('super_admin.marketing.campaigns.show', [
            'campaign' => $campaign->load(['segment', 'recipients.prospect']),
        ]);
    }

    public function prepare(PlatformMarketingCampaign $campaign, CampaignSafetyService $safety)
    {
        $summary = $safety->prepareRecipients($campaign);

        return back()->with('success', "Recipients prepared: {$summary['eligible']} eligible, {$summary['suppressed']} suppressed, {$summary['duplicates']} duplicates.");
    }

    public function approve(Request $request, PlatformMarketingCampaign $campaign)
    {
        $campaign->forceFill([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();

        return back()->with('success', 'Campaign approved.');
    }

    public function launch(PlatformMarketingCampaign $campaign)
    {
        DispatchPlatformCampaign::dispatch($campaign->id);

        return back()->with('success', 'Campaign dispatch queued.');
    }

    public function pause(PlatformMarketingCampaign $campaign)
    {
        $campaign->forceFill(['status' => 'paused', 'paused_at' => now()])->save();

        return back()->with('success', 'Campaign paused.');
    }

    public function stop(PlatformMarketingCampaign $campaign)
    {
        $campaign->forceFill(['status' => 'stopped', 'stopped_at' => now()])->save();

        return back()->with('success', 'Campaign stopped.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'product' => ['nullable', 'string', 'max:120'],
            'segment_id' => ['nullable', 'integer'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'template_language' => ['nullable', 'string', 'max:20'],
            'scheduled_at' => ['nullable', 'date'],
            'batch_size' => ['required', 'integer', 'min:1', 'max:100'],
            'delay_between_batches' => ['required', 'integer', 'min:30', 'max:86400'],
            'daily_cap' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
    }
}
