<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\{Campaign, CampaignStep, CampaignAudience};
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\Marketing\CampaignDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;

        $items = Campaign::where('company_id', $companyId)
            ->latest()
            ->paginate(20);

        return view('admin.marketing.campaigns.index', compact('items'));
    }

    /**
     * Show create form.
     * Uses a dedicated `create` view and passes dropdown templates.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id ?? 1;

        // Only id + name needed for dropdown; keep it light
        $templates = WhatsAppTemplate::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        // If you prefer, you can also pass a blank Campaign for form binding
        $campaign = new Campaign([
            'company_id' => $companyId,
            'type'       => 'automation',
            'status'     => 'draft',
        ]);

        return view('admin.marketing.campaigns.create', compact('templates', 'campaign'));
    }

    public function store(Request $r)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $r->validate([
            'name'         => 'required|string|max:160',
            'type'         => 'required|in:broadcast,automation',
            'status'       => 'required|in:draft,active,paused,archived',
            'description'  => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'steps'        => 'nullable|array',
            'audiences'    => 'nullable|array',
        ]);

        DB::transaction(function () use ($data, $companyId, &$campaign) {
            $campaign = Campaign::create([
                'company_id'   => $companyId,
                'name'         => $data['name'],
                'type'         => $data['type'],
                'status'       => $data['status'],
                'description'  => $data['description'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            // Steps
            foreach (($data['steps'] ?? []) as $i => $s) {
                CampaignStep::create([
                    'campaign_id'   => $campaign->id,
                    'step_order'    => $i + 1,
                    'action'        => $s['action'] ?? 'send_template',
                    'template_id'   => $s['template_id'] ?? null,
                    'action_params' => $s['action_params'] ?? null, // keep as array/json if the column is JSON
                ]);
            }

            // Audiences
            foreach (($data['audiences'] ?? []) as $a) {
                CampaignAudience::create([
                    'campaign_id' => $campaign->id,
                    'filters'     => $a['filters'] ?? null, // keep as array/json if the column is JSON
                ]);
            }
        });

        return redirect()
            ->route('admin.marketing.campaigns.index')
            ->with('ok', 'Campaign created');
    }

    public function edit(Campaign $campaign)
    {
        $companyId = auth()->user()->company_id ?? 1;

        // For editing, you may also want templates available for step changes
        $templates = WhatsAppTemplate::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $campaign->load('steps', 'audiences');

        return view('admin.marketing.campaigns.edit', compact('campaign', 'templates'));
    }

    public function update(Request $r, Campaign $campaign)
    {
        $data = $r->validate([
            'name'         => 'required|string|max:160',
            'type'         => 'required|in:broadcast,automation',
            'status'       => 'required|in:draft,active,paused,archived',
            'description'  => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'steps'        => 'nullable|array',
            'audiences'    => 'nullable|array',
        ]);

        DB::transaction(function () use ($campaign, $data) {
            $campaign->update([
                'name'         => $data['name'],
                'type'         => $data['type'],
                'status'       => $data['status'],
                'description'  => $data['description'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);

            // Steps (replace)
            $campaign->steps()->delete();
            foreach (($data['steps'] ?? []) as $i => $s) {
                $campaign->steps()->create([
                    'step_order'    => $i + 1,
                    'action'        => $s['action'] ?? 'send_template',
                    'template_id'   => $s['template_id'] ?? null,
                    'action_params' => $s['action_params'] ?? null,
                ]);
            }

            // Audiences (replace)
            $campaign->audiences()->delete();
            foreach (($data['audiences'] ?? []) as $a) {
                $campaign->audiences()->create([
                    'filters' => $a['filters'] ?? null,
                ]);
            }
        });

        return back()->with('ok', 'Saved');
    }

    public function activate(Campaign $campaign)
    {
        $campaign->update(['status' => 'active']);
        return back()->with('ok', 'Campaign activated');
    }

    public function pause(Campaign $campaign)
    {
        $campaign->update(['status' => 'paused']);
        return back()->with('ok', 'Campaign paused');
    }
}
