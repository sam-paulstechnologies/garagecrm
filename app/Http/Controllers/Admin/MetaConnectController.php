<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use App\Models\MetaPage;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaConnectController extends Controller
{
    private function metaConfig(string $key, mixed $default = null): mixed
    {
        return config("services.meta_leads.{$key}")
            ?? config("services.meta.{$key}")
            ?? $default;
    }

    private function graphVersion(): string
    {
        return trim((string) $this->metaConfig('graph_version', 'v20.0'), '/');
    }

    private function graphBase(): string
    {
        $base = rtrim((string) $this->metaConfig('graph_base', 'https://graph.facebook.com'), '/');

        return "{$base}/{$this->graphVersion()}";
    }

    /**
     * Step 1: Redirect user to Facebook OAuth.
     */
    public function start(Request $request)
    {
        $appId = (string) $this->metaConfig('app_id', '');

        if ($appId === '') {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Meta App ID is missing. Please set META_APP_ID in .env.');
        }

        $redirectUri = route('admin.lead-sources.meta.callback');

        $query = http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => implode(',', [
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_metadata',
                'pages_manage_ads',
                'leads_retrieval',
            ]),
        ]);

        return redirect("https://www.facebook.com/{$this->graphVersion()}/dialog/oauth?{$query}");
    }

    /**
     * Step 2: OAuth callback → exchange token → fetch available pages → ask user to select page.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::warning('[META_CONNECT][OAUTH_CANCELLED]', $request->all());

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Facebook connection was cancelled.');
        }

        $code = $request->query('code');

        if (! $code) {
            Log::error('[META_CONNECT][CALLBACK_MISSING_CODE]', $request->all());

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Facebook login failed. Please try again.');
        }

        $companyId = auth()->user()->company_id;

        $appId = (string) $this->metaConfig('app_id', '');
        $appSecret = (string) $this->metaConfig('app_secret', '');

        if ($appId === '' || $appSecret === '') {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Meta App ID or App Secret is missing. Please check META_APP_ID and META_APP_SECRET in .env.');
        }

        $redirectUri = route('admin.lead-sources.meta.callback');

        try {
            $tokenResponse = Http::timeout(20)->get("{$this->graphBase()}/oauth/access_token", [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
            ]);
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][TOKEN_REQUEST_EXCEPTION]', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to connect to Facebook. Please try again.');
        }

        if (! $tokenResponse->ok()) {
            Log::error('[META_CONNECT][TOKEN_EXCHANGE_FAILED]', [
                'company_id' => $companyId,
                'status'     => $tokenResponse->status(),
                'response'   => $tokenResponse->json(),
            ]);

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to authenticate with Facebook.');
        }

        $userAccessToken = $tokenResponse->json('access_token');

        if (! $userAccessToken) {
            Log::error('[META_CONNECT][TOKEN_MISSING_IN_RESPONSE]', [
                'company_id' => $companyId,
                'response'   => $tokenResponse->json(),
            ]);

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Facebook did not return an access token.');
        }

        try {
            $pagesResponse = Http::timeout(20)->get("{$this->graphBase()}/me/accounts", [
                'fields'       => 'id,name,access_token',
                'access_token' => $userAccessToken,
                'limit'        => 100,
            ]);
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][PAGES_REQUEST_EXCEPTION]', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to fetch Facebook pages.');
        }

        if (! $pagesResponse->ok()) {
            Log::error('[META_CONNECT][PAGES_FETCH_FAILED]', [
                'company_id' => $companyId,
                'status'     => $pagesResponse->status(),
                'response'   => $pagesResponse->json(),
            ]);

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to fetch Facebook pages.');
        }

        $pages = $pagesResponse->json('data', []);

        if (empty($pages)) {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'No Facebook Pages were returned. Please make sure your Meta user has access to the Page.');
        }

        session([
            'meta_company_id' => $companyId,
            'meta_user_token' => $userAccessToken,
            'meta_pages'      => $pages,
        ]);

        return redirect()
            ->route('admin.lead-sources.meta')
            ->with('success', 'Facebook connected. Please select the Page you want to use for Meta lead capture.');
    }

    /**
     * Step 3: Page selected → save page token → fetch forms → create/update lead sources → subscribe page.
     */
    public function selectPage(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|string',
            'page_name' => 'nullable|string',
        ]);

        $companyId = session('meta_company_id');
        $userToken = session('meta_user_token');

        abort_unless($companyId && $userToken, 419, 'Session expired. Please reconnect Facebook.');

        if ((int) $companyId !== (int) auth()->user()->company_id) {
            abort(403, 'Invalid Meta connection session.');
        }

        $pageId = (string) $request->page_id;
        $pageName = (string) $request->input('page_name', '');

        try {
            $pageInfo = Http::timeout(20)->get("{$this->graphBase()}/{$pageId}", [
                'fields'       => 'access_token,name',
                'access_token' => $userToken,
            ])->throw()->json();
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][PAGE_TOKEN_FETCH_FAILED]', [
                'company_id' => $companyId,
                'page_id'    => $pageId,
                'error'      => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to fetch Page access token.');
        }

        $pageAccessToken = $pageInfo['access_token'] ?? null;
        $resolvedPageName = $pageInfo['name'] ?? $pageName ?: 'Facebook Page';

        if (! $pageAccessToken) {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to fetch Page access token.');
        }

        $forms = $this->fetchForms($companyId, $pageId, $pageAccessToken);

        $meta = MetaPage::updateOrCreate(
            [
                'company_id' => $companyId,
                'page_id'    => $pageId,
            ],
            [
                'page_name'         => $resolvedPageName,
                'page_access_token' => $pageAccessToken,
                'forms_json'        => json_encode($forms),
            ]
        );

        $createdOrUpdatedSources = $this->syncLeadSourcesFromForms(
            companyId: (int) $companyId,
            pageId: $pageId,
            pageName: $resolvedPageName,
            forms: $forms
        );

        $subscriptionOk = $this->subscribePageToLeadgen(
            companyId: (int) $companyId,
            pageId: $pageId,
            pageAccessToken: $pageAccessToken
        );

        $settings = new SettingsStore($companyId);
        $settings->set('meta_leads.page_id', $pageId);

        if (! empty($forms[0]['id'])) {
            $settings->set('meta_leads.form_id', $forms[0]['id']);
        }

        session()->forget(['meta_company_id', 'meta_user_token', 'meta_pages']);

        if (! $subscriptionOk) {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('warning', "Connected {$meta->page_name} and synced " . count($forms) . " forms, but leadgen webhook subscription failed. Please check Meta permissions and try Refresh Forms.");
        }

        return redirect()
            ->route('admin.lead-sources.meta')
            ->with('success', "Connected {$meta->page_name}, synced " . count($forms) . " forms, and prepared {$createdOrUpdatedSources} lead sources.");
    }

    /**
     * Refresh lead forms and lead sources.
     */
    public function refresh()
    {
        $companyId = auth()->user()->company_id;

        $meta = MetaPage::where('company_id', $companyId)
            ->whereNot('page_id', 'like', 'TEST_%')
            ->latest('id')
            ->first();

        abort_unless($meta && $meta->page_access_token, 400, 'No connected Meta page.');

        $forms = $this->fetchForms(
            companyId: (int) $companyId,
            pageId: (string) $meta->page_id,
            pageAccessToken: (string) $meta->page_access_token
        );

        $meta->update([
            'forms_json' => json_encode($forms),
        ]);

        $createdOrUpdatedSources = $this->syncLeadSourcesFromForms(
            companyId: (int) $companyId,
            pageId: (string) $meta->page_id,
            pageName: (string) $meta->page_name,
            forms: $forms
        );

        $subscriptionOk = $this->subscribePageToLeadgen(
            companyId: (int) $companyId,
            pageId: (string) $meta->page_id,
            pageAccessToken: (string) $meta->page_access_token
        );

        if (! $subscriptionOk) {
            return back()->with('warning', 'Forms refreshed, but leadgen webhook subscription failed. Please check Meta app permissions.');
        }

        return back()->with('success', 'Forms refreshed: ' . count($forms) . '. Lead sources updated: ' . $createdOrUpdatedSources . '.');
    }

    /**
     * Disconnect Meta.
     */
    public function disconnect()
    {
        $companyId = auth()->user()->company_id;

        MetaPage::where('company_id', $companyId)->delete();

        LeadSource::where('company_id', $companyId)
            ->where('type', 'meta')
            ->update([
                'status' => 'inactive',
            ]);

        $settings = new SettingsStore($companyId);
        $settings->delete('meta_leads.page_id');
        $settings->delete('meta_leads.form_id');

        session()->forget(['meta_company_id', 'meta_user_token', 'meta_pages']);

        return back()->with('success', 'Disconnected from Facebook Page. Existing Meta lead sources were marked inactive.');
    }

    private function fetchForms(int $companyId, string $pageId, string $pageAccessToken): array
    {
        try {
            $response = Http::timeout(20)->get("{$this->graphBase()}/{$pageId}/leadgen_forms", [
                'fields'       => 'id,name,status,created_time,questions',
                'access_token' => $pageAccessToken,
                'limit'        => 200,
            ]);

            if (! $response->ok()) {
                Log::error('[META_CONNECT][FORMS_FETCH_FAILED]', [
                    'company_id' => $companyId,
                    'page_id'    => $pageId,
                    'status'     => $response->status(),
                    'response'   => $response->json(),
                ]);

                return [];
            }

            $forms = $response->json('data', []);

            return is_array($forms) ? $forms : [];
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][FORMS_FETCH_EXCEPTION]', [
                'company_id' => $companyId,
                'page_id'    => $pageId,
                'error'      => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function subscribePageToLeadgen(int $companyId, string $pageId, string $pageAccessToken): bool
    {
        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post("{$this->graphBase()}/{$pageId}/subscribed_apps", [
                    'subscribed_fields' => 'leadgen',
                    'access_token'      => $pageAccessToken,
                ]);

            if (! $response->ok()) {
                Log::error('[META_CONNECT][LEADGEN_SUBSCRIPTION_FAILED]', [
                    'company_id' => $companyId,
                    'page_id'    => $pageId,
                    'status'     => $response->status(),
                    'response'   => $response->json(),
                ]);

                return false;
            }

            Log::info('[META_CONNECT][LEADGEN_SUBSCRIBED]', [
                'company_id' => $companyId,
                'page_id'    => $pageId,
                'response'   => $response->json(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][LEADGEN_SUBSCRIPTION_EXCEPTION]', [
                'company_id' => $companyId,
                'page_id'    => $pageId,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function syncLeadSourcesFromForms(int $companyId, string $pageId, string $pageName, array $forms): int
    {
        $count = 0;

        foreach ($forms as $form) {
            $formId = data_get($form, 'id');

            if (! $formId) {
                continue;
            }

            $formName = data_get($form, 'name', "Meta Form {$formId}");

            $leadSource = LeadSource::where('company_id', $companyId)
                ->where('type', 'meta')
                ->where('config->form_id', (string) $formId)
                ->first();

            if (! $leadSource) {
                $leadSource = new LeadSource();
                $leadSource->company_id = $companyId;
                $leadSource->type = 'meta';
                $leadSource->form_token = 'meta_' . Str::random(32);
            }

            $leadSource->name = 'Meta - ' . $formName;
            $leadSource->status = 'active';
            $leadSource->config = [
                'provider'        => 'meta',
                'page_id'         => $pageId,
                'page_name'       => $pageName,
                'form_id'         => (string) $formId,
                'form_name'       => $formName,
                'form_status'     => data_get($form, 'status'),
                'form_created_at' => data_get($form, 'created_time'),
                'questions'       => data_get($form, 'questions', []),
            ];

            $leadSource->save();

            $count++;
        }

        return $count;
    }
}