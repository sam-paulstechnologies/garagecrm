<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\CompanySetting;
use App\Models\LeadSource;
use App\Models\MetaPage;
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

        $sources = LeadSource::forCompany($companyId)
            ->get()
            ->groupBy('type');

        /*
        |--------------------------------------------------------------------------
        | WhatsApp
        |--------------------------------------------------------------------------
        */
        $waFrom = trim((string) config('services.whatsapp.twilio.from'));
        $waConfigured = $waFrom !== '';

        $wa = $sources->get('whatsapp')?->first();

        if ($waConfigured && ! $wa) {
            $wa = LeadSource::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'type'       => 'whatsapp',
                    'name'       => 'WhatsApp',
                ],
                [
                    'status' => 'connected',
                    'config' => [
                        'provider' => 'twilio',
                        'from'     => $waFrom,
                    ],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Website
        |--------------------------------------------------------------------------
        */
        $websiteCount = LeadSource::forCompany($companyId)
            ->type('website')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Meta
        |--------------------------------------------------------------------------
        */
        $metaRow = MetaPage::where('company_id', $companyId)->first();

        if ($metaRow && $metaRow->page_access_token) {
            $this->syncMetaLeadSources($companyId, $metaRow);
        }

        $metaFormsCount = LeadSource::forCompany($companyId)
            ->type('meta')
            ->count();

        $metaActiveFormsCount = LeadSource::forCompany($companyId)
            ->type('meta')
            ->active()
            ->count();

        $metaConnected = (bool) ($metaRow && $metaRow->page_access_token);

        $cards = [
            [
                'key'         => 'whatsapp',
                'title'       => 'WhatsApp',
                'subtitle'    => 'Inbound leads & conversations',
                'status'      => $waConfigured ? 'Connected' : 'Not configured',
                'statusTone'  => $waConfigured ? 'green' : 'gray',
                'meta'        => $waConfigured ? "From: {$waFrom}" : 'Not configured',
                'route'       => route('admin.lead-sources.whatsapp'),
                'actionLabel' => 'Manage',
            ],
            [
                'key'         => 'website',
                'title'       => 'Website Forms',
                'subtitle'    => 'Embed forms on your website',
                'status'      => $websiteCount > 0 ? 'Active' : 'Not configured',
                'statusTone'  => $websiteCount > 0 ? 'green' : 'gray',
                'meta'        => $websiteCount > 0
                    ? "{$websiteCount} form(s) created"
                    : 'No form created',
                'route'       => route('admin.lead-sources.website.index'),
                'actionLabel' => $websiteCount > 0 ? 'Manage' : 'Create',
            ],
            [
                'key'         => 'meta',
                'title'       => 'Meta (Facebook / Instagram)',
                'subtitle'    => 'Lead Ads & instant forms',
                'status'      => $metaConnected ? 'Connected' : 'Not connected',
                'statusTone'  => $metaConnected ? 'green' : 'gray',
                'meta'        => $metaConnected
                    ? 'Page: ' . $metaRow->page_name . " | {$metaActiveFormsCount}/{$metaFormsCount} active form(s)"
                    : 'No page connected',
                'route'       => route('admin.lead-sources.meta'),
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
        $forms = LeadSource::forCompany($this->companyId())
            ->type('website')
            ->latest()
            ->get();

        return view('admin.lead_sources.website.index', compact('forms'));
    }

    /*
    |--------------------------------------------------------------------------
    | Website Forms — SHOW
    |--------------------------------------------------------------------------
    */
    public function websiteShow(LeadSource $leadSource)
    {
        abort_if($leadSource->company_id !== $this->companyId(), 403);

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
    | Website Forms — STORE
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
        $companyId = $this->companyId();

        $meta = MetaPage::where('company_id', $companyId)->first();

        if ($meta && $meta->page_access_token) {
            $this->syncMetaLeadSources($companyId, $meta);
        }

        $sources = LeadSource::forCompany($companyId)
            ->type('meta')
            ->latest()
            ->get();

        return view('admin.lead_sources.meta', compact('meta', 'sources'));
    }

    /*
    |--------------------------------------------------------------------------
    | Internal: Sync Meta Forms into Lead Sources
    |--------------------------------------------------------------------------
    | MetaPage = connected Facebook Page.
    | LeadSource = individual CRM source/form used for attribution.
    |--------------------------------------------------------------------------
    */
    private function syncMetaLeadSources(int $companyId, MetaPage $meta): void
    {
        $forms = $this->normalizeMetaForms($meta);

        if (empty($forms)) {
            return;
        }

        $existingSources = LeadSource::forCompany($companyId)
            ->type('meta')
            ->get()
            ->keyBy(function (LeadSource $source) {
                return (string) data_get($source->config ?? [], 'form_id', '');
            });

        $seenFormIds = [];

        foreach ($forms as $form) {
            $formId = (string) ($form['id'] ?? '');

            if ($formId === '') {
                continue;
            }

            $seenFormIds[] = $formId;

            $formName = (string) ($form['name'] ?? "Meta Form {$formId}");

            $config = [
                'platform'      => 'meta',
                'page_id'       => (string) $meta->page_id,
                'page_name'     => (string) $meta->page_name,
                'form_id'       => $formId,
                'form_name'     => $formName,
                'raw_form'      => $form,
                'field_mapping' => data_get($existingSources->get($formId)?->config ?? [], 'field_mapping', []),
            ];

            $existing = $existingSources->get($formId);

            if ($existing) {
                $existing->update([
                    'name'   => "Meta - {$formName}",
                    'status' => $existing->status ?: 'active',
                    'config' => array_merge($existing->config ?? [], $config),
                ]);

                continue;
            }

            LeadSource::create([
                'company_id' => $companyId,
                'type'       => 'meta',
                'name'       => "Meta - {$formName}",
                'status'     => 'active',
                'config'     => $config,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Soft-disable forms that are no longer returned by Meta.
        |--------------------------------------------------------------------------
        */
        LeadSource::forCompany($companyId)
            ->type('meta')
            ->get()
            ->each(function (LeadSource $source) use ($seenFormIds) {
                $formId = (string) data_get($source->config ?? [], 'form_id', '');

                if ($formId !== '' && ! in_array($formId, $seenFormIds, true)) {
                    $source->update([
                        'status' => 'inactive',
                    ]);
                }
            });
    }

    private function normalizeMetaForms(MetaPage $meta): array
    {
        $forms = $meta->forms_json ?? [];

        if (is_string($forms)) {
            $decoded = json_decode($forms, true);
            $forms = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($forms)) {
            return [];
        }

        if (array_key_exists('data', $forms) && is_array($forms['data'])) {
            return $forms['data'];
        }

        return array_values($forms);
    }
}