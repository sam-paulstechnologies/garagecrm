<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleLeadSourceController extends Controller
{
    public function index()
    {
        $companyId = (int) auth()->user()->company_id;

        $sources = LeadSource::query()
            ->where('company_id', $companyId)
            ->where('type', 'google')
            ->latest('id')
            ->get();

        $webhookUrl = url('/api/v1/webhooks/google/leads');

        return view('admin.lead_sources.google', [
            'sources' => $sources,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'form_id' => ['nullable', 'string', 'max:191'],
            'campaign_id' => ['nullable', 'string', 'max:191'],
            'campaign_name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = (int) auth()->user()->company_id;
        $webhookKey = $this->generateWebhookKey();

        LeadSource::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'type' => 'google',
            'status' => 'active',
            'form_token' => $webhookKey,
            'config' => [
                'provider' => 'google',
                'webhook_key' => $webhookKey,
                'form_id' => $request->filled('form_id') ? $request->form_id : null,
                'campaign_id' => $request->filled('campaign_id') ? $request->campaign_id : null,
                'campaign_name' => $request->filled('campaign_name') ? $request->campaign_name : null,
                'description' => $request->filled('description') ? $request->description : null,
                'source_label' => 'Google Ads',
                'utm_source' => 'google',
                'utm_medium' => 'lead_form',
                'created_from' => 'admin_google_lead_source_page',
            ],
        ]);

        return redirect()
            ->route('admin.lead-sources.google')
            ->with('success', 'Google Ads lead source created. Copy the webhook URL and key into Google Ads Lead Form Asset.');
    }

    public function rotateToken(LeadSource $leadSource)
    {
        $this->ensureGoogleSourceBelongsToCompany($leadSource);

        $webhookKey = $this->generateWebhookKey();
        $config = $leadSource->config ?? [];

        $config['webhook_key'] = $webhookKey;
        $config['token_rotated_at'] = now()->toISOString();

        $leadSource->update([
            'form_token' => $webhookKey,
            'config' => $config,
        ]);

        return redirect()
            ->route('admin.lead-sources.google')
            ->with('success', 'Google webhook key rotated. Update the new key in Google Ads.');
    }

    public function activate(LeadSource $leadSource)
    {
        $this->ensureGoogleSourceBelongsToCompany($leadSource);

        $leadSource->update([
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.lead-sources.google')
            ->with('success', 'Google lead source activated.');
    }

    public function deactivate(LeadSource $leadSource)
    {
        $this->ensureGoogleSourceBelongsToCompany($leadSource);

        $leadSource->update([
            'status' => 'inactive',
        ]);

        return redirect()
            ->route('admin.lead-sources.google')
            ->with('success', 'Google lead source deactivated.');
    }

    private function ensureGoogleSourceBelongsToCompany(LeadSource $leadSource): void
    {
        abort_unless(
            (int) $leadSource->company_id === (int) auth()->user()->company_id &&
            $leadSource->type === 'google',
            403
        );
    }

    private function generateWebhookKey(): string
    {
        return 'gads_' . strtolower(str_replace('-', '', (string) Str::uuid()));
    }
}