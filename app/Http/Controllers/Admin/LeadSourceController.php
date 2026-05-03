<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use App\Models\MetaPage;
use App\Models\Company\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadSourceController extends Controller
{
    private function companyId(): int
    {
        return (int) auth()->user()->company_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Lead Sources Hub
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $companyId = $this->companyId();

        $sources = LeadSource::where('company_id', $companyId)
            ->get()
            ->keyBy('type');

        /** ---------------- WhatsApp ---------------- */
        $waFrom = trim((string) config('services.whatsapp.twilio.from'));
        $waConfigured = $waFrom !== '';

        $wa = $sources->get('whatsapp');

        if ($waConfigured && !$wa) {
            $wa = LeadSource::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'type'       => 'whatsapp',
                ],
                [
                    'name'   => 'WhatsApp',
                    'status' => 'connected',
                    'config' => [
                        'provider' => 'twilio',
                        'from'     => $waFrom,
                    ],
                ]
            );
        }

        /** ---------------- Website ---------------- */
        $websiteExists = LeadSource::where('company_id', $companyId)
            ->where('type', 'website')
            ->exists();

        /** ---------------- Meta ---------------- */
        $metaRow = MetaPage::where('company_id', $companyId)->first();
        $metaConnected = (bool) ($metaRow && $metaRow->page_access_token);

        $cards = [
            [
                'key' => 'whatsapp',
                'title' => 'WhatsApp',
                'subtitle' => 'Inbound leads & conversations',
                'status' => $waConfigured ? 'Connected' : 'Not configured',
                'statusTone' => $waConfigured ? 'green' : 'gray',
                'meta' => $waConfigured ? "From: {$waFrom}" : 'Not configured',
                'route' => route('admin.lead-sources.whatsapp'),
                'actionLabel' => 'Manage',
            ],
            [
                'key' => 'website',
                'title' => 'Website Forms',
                'subtitle' => 'Embed forms on your website',
                'status' => $websiteExists ? 'Active' : 'Not configured',
                'statusTone' => $websiteExists ? 'green' : 'gray',
                'meta' => $websiteExists
                    ? 'Multiple forms supported'
                    : 'No form created',
                'route' => route('admin.lead-sources.website.index'),
                'actionLabel' => $websiteExists ? 'Manage' : 'Create',
            ],
            [
                'key' => 'meta',
                'title' => 'Meta (Facebook / Instagram)',
                'subtitle' => 'Lead Ads & instant forms',
                'status' => $metaConnected ? 'Connected' : 'Not connected',
                'statusTone' => $metaConnected ? 'green' : 'gray',
                'meta' => $metaConnected
                    ? 'Page: ' . $metaRow->page_name
                    : 'No page connected',
                'route' => route('admin.lead-sources.meta'),
                'actionLabel' => $metaConnected ? 'Manage' : 'Setup',
            ],
        ];

        return view('admin.lead_sources.index', compact('cards'));
    }

    /*
    |--------------------------------------------------------------------------
    | Website Forms — INDEX
    |--------------------------------------------------------------------------
    */
    public function websiteIndex()
    {
        $forms = LeadSource::where('company_id', $this->companyId())
            ->where('type', 'website')
            ->latest()
            ->get();

        return view('admin.lead_sources.website.index', compact('forms'));
    }

    /*
    |--------------------------------------------------------------------------
    | Website Forms — SHOW (Embed + Preview)
    |--------------------------------------------------------------------------
    */
    public function websiteShow(LeadSource $leadSource)
    {
        abort_if(
            $leadSource->company_id !== $this->companyId(),
            403
        );

        // ✅ correct route name
        $formUrl = route('api.website-leads.store', $leadSource->form_token);

        $embed = view(
            'admin.lead_sources.website._embed',
            compact('leadSource')
        )->render();

        return view(
            'admin.lead_sources.website.show',
            compact('leadSource', 'formUrl', 'embed')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Website Forms — STORE (FIXED: NO overwrite)
    |--------------------------------------------------------------------------
    */
    public function storeWebsite(Request $request)
    {
        $data = $request->validate([
            'form_name' => 'required|string|max:150',
        ]);

        LeadSource::create([
            'company_id' => $this->companyId(),
            'type'       => 'website',
            'name'       => $data['form_name'],
            'status'     => 'active',
            'config'     => $data,
            'form_token' => Str::random(32),
        ]);

        return redirect()
            ->route('admin.lead-sources.website.index')
            ->with('success', 'Website form saved.');
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    */
    public function whatsapp()
    {
        $companyId = $this->companyId();

        $settings = CompanySetting::where('company_id', $companyId)
            ->where('group', 'whatsapp')
            ->pluck('value', 'key')
            ->toArray();

        return view('admin.lead_sources.whatsapp', [
            'waFrom'             => config('services.whatsapp.twilio.from'),
            'managerWhatsapp'    => $settings['whatsapp_manager_number'] ?? '',
            'googleReviewLink'   => $settings['google_review_link'] ?? '',
            'garageLocationLink' => $settings['garage_location_link'] ?? '',
            'webhookUrl'         => route('webhooks.twilio.whatsapp'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Meta
    |--------------------------------------------------------------------------
    */
    public function meta()
    {
        $meta = MetaPage::where('company_id', $this->companyId())->first();

        return view('admin.lead_sources.meta', compact('meta'));
    }
}
