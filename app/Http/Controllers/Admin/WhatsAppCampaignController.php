<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\WhatsApp\WhatsAppTemplate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsAppCampaignController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    /**
     * List WhatsApp campaigns.
     */
    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));

        $campaigns = Campaign::query()
            ->with([
                'template:id,company_id,name,provider_template,language,status,provider',
            ])
            ->withCount([
                'audience as audience_count',
                'audience as queued_count' => fn ($query) => $query->where('status', CampaignAudience::STATUS_QUEUED),
                'audience as sent_count' => fn ($query) => $query->where('status', CampaignAudience::STATUS_SENT),
                'audience as failed_count' => fn ($query) => $query->where('status', CampaignAudience::STATUS_FAILED),
            ])
            ->forCompany($companyId)
            ->whatsapp()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('template', function ($templateQuery) use ($q) {
                            $templateQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('provider_template', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.whatsapp.campaigns.index', compact('campaigns', 'q'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $companyId = $this->companyId();

        $templates = WhatsAppTemplate::forCompany($companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.whatsapp.campaigns.create', compact('templates'));
    }

    /**
     * Store campaign.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'message_template_id' => [
                'required',
                Rule::exists('whatsapp_templates', 'id')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'audience' => ['nullable', 'string', 'max:20000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data, $companyId) {
            $campaign = Campaign::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'type' => Campaign::TYPE_BROADCAST,
                'channel' => Campaign::CHANNEL_WHATSAPP,
                'message_template_id' => $data['message_template_id'],
                'scheduled_at' => ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
                'status' => ! empty($data['scheduled_at'])
                    ? Campaign::STATUS_SCHEDULED
                    : Campaign::STATUS_DRAFT,
                'description' => $data['description'] ?? null,
            ]);

            $this->syncManualAudience($campaign, (string) ($data['audience'] ?? ''));
        });

        return redirect()
            ->route('admin.whatsapp.campaigns.index')
            ->with('success', 'Campaign saved.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->with('audience')
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($id);

        $templates = WhatsAppTemplate::forCompany($companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $audienceText = $campaign->audience
            ->pluck('to')
            ->filter()
            ->implode("\n");

        return view('admin.whatsapp.campaigns.edit', compact('campaign', 'templates', 'audienceText'));
    }

    /**
     * Update campaign.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($id);

        if (! $campaign->isEditable()) {
            return back()->with('error', 'This campaign cannot be edited in its current status.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'message_template_id' => [
                'required',
                Rule::exists('whatsapp_templates', 'id')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'audience' => ['nullable', 'string', 'max:20000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($campaign, $data) {
            $campaign->update([
                'name' => $data['name'],
                'message_template_id' => $data['message_template_id'],
                'description' => $data['description'] ?? null,
                'scheduled_at' => ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
                'status' => ! empty($data['scheduled_at'])
                    ? Campaign::STATUS_SCHEDULED
                    : Campaign::STATUS_DRAFT,
            ]);

            $campaign->audience()->delete();

            $this->syncManualAudience($campaign, (string) ($data['audience'] ?? ''));
        });

        return redirect()
            ->route('admin.whatsapp.campaigns.index')
            ->with('success', 'Campaign updated.');
    }

    /**
     * Delete campaign.
     */
    public function destroy(int $id): RedirectResponse
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($id);

        DB::transaction(function () use ($campaign) {
            $campaign->audience()->delete();
            $campaign->delete();
        });

        return redirect()
            ->route('admin.whatsapp.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    /**
     * Queue immediate send.
     */
    public function sendNow(int $campaignId): RedirectResponse
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($campaignId);

        if ($campaign->audience()->where('status', CampaignAudience::STATUS_QUEUED)->count() === 0) {
            return back()->with('error', 'No queued audience found for this campaign.');
        }

        $campaign->update([
            'status' => Campaign::STATUS_RUNNING,
            'scheduled_at' => now(),
        ]);

        return back()->with('success', 'Campaign queued to send now. Run the campaign dispatcher to process it.');
    }

    /**
     * Schedule campaign for later.
     */
    public function schedule(Request $request, int $campaignId): RedirectResponse
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($campaignId);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $when = Carbon::parse($data['scheduled_at']);

        $campaign->update([
            'scheduled_at' => $when,
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        return back()->with('success', 'Campaign scheduled for ' . $when->toDayDateTimeString() . '.');
    }

    /**
     * Pause campaign.
     */
    public function pause(int $campaignId): RedirectResponse
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($campaignId);

        if (! in_array($campaign->status, [Campaign::STATUS_SCHEDULED, Campaign::STATUS_RUNNING], true)) {
            return back()->with('error', 'Only scheduled or running campaigns can be paused.');
        }

        $campaign->update([
            'status' => Campaign::STATUS_PAUSED,
        ]);

        return back()->with('success', 'Campaign paused.');
    }

    /**
     * Resume campaign.
     */
    public function resume(int $campaignId): RedirectResponse
    {
        $companyId = $this->companyId();

        $campaign = Campaign::query()
            ->forCompany($companyId)
            ->whatsapp()
            ->findOrFail($campaignId);

        if ($campaign->status !== Campaign::STATUS_PAUSED) {
            return back()->with('error', 'Only paused campaigns can be resumed.');
        }

        $campaign->update([
            'status' => $campaign->scheduled_at && $campaign->scheduled_at->isFuture()
                ? Campaign::STATUS_SCHEDULED
                : Campaign::STATUS_RUNNING,
        ]);

        return back()->with('success', 'Campaign resumed.');
    }

    private function syncManualAudience(Campaign $campaign, string $audience): void
    {
        $numbers = collect(preg_split('/[\r\n,;]+/', $audience))
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->map(fn ($number) => $this->normalizePhone($number))
            ->filter()
            ->unique()
            ->values();

        foreach ($numbers as $number) {
            CampaignAudience::create([
                'campaign_id' => $campaign->id,
                'filters' => [
                    'source' => 'manual',
                ],
                'target_type' => 'manual',
                'target_id' => null,
                'to' => $number,
                'status' => CampaignAudience::STATUS_QUEUED,
            ]);
        }
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (! $phone) {
            return null;
        }

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone;
    }
}