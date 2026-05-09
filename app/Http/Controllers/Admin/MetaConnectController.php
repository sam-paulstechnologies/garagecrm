<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaPage;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaConnectController extends Controller
{
    private function graphBase(): string
    {
        $base = rtrim((string) config('services.meta_leads.graph_base', 'https://graph.facebook.com'), '/');
        $version = trim((string) config('services.meta_leads.graph_version', 'v20.0'), '/');

        return "{$base}/{$version}";
    }

    /**
     * Step 1: Redirect user to Facebook OAuth.
     */
    public function start(Request $request)
    {
        $appId = (string) config('services.meta_leads.app_id');

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
                'leads_retrieval',
            ]),
        ]);

        return redirect("https://www.facebook.com/" . config('services.meta_leads.graph_version', 'v20.0') . "/dialog/oauth?{$query}");
    }

    /**
     * Step 2: OAuth callback → exchange token → prepare page selection.
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

        $appId = (string) config('services.meta_leads.app_id');
        $appSecret = (string) config('services.meta_leads.app_secret');

        if ($appId === '' || $appSecret === '') {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Meta App ID or App Secret is missing. Please check META_APP_ID and META_APP_SECRET in .env.');
        }

        $redirectUri = route('admin.lead-sources.meta.callback');

        try {
            $tokenResponse = Http::asForm()->get("{$this->graphBase()}/oauth/access_token", [
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
            $pagesResponse = Http::get("{$this->graphBase()}/me/accounts", [
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

        session([
            'meta_company_id' => $companyId,
            'meta_user_token' => $userAccessToken,
            'meta_pages'      => $pages,
        ]);

        return redirect()
            ->route('admin.lead-sources.meta')
            ->with('success', 'Facebook connected. Please select a page.');
    }

    /**
     * Step 3: Page selected → fetch page token + lead forms.
     */
    public function selectPage(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|string',
            'page_name' => 'required|string',
        ]);

        $companyId = session('meta_company_id');
        $userToken = session('meta_user_token');

        abort_unless($companyId && $userToken, 419, 'Session expired. Please reconnect Facebook.');

        $pageId   = (string) $request->page_id;
        $pageName = (string) $request->page_name;

        try {
            $pageInfo = Http::get("{$this->graphBase()}/{$pageId}", [
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
        $resolvedPageName = $pageInfo['name'] ?? $pageName;

        if (! $pageAccessToken) {
            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to fetch Page access token.');
        }

        try {
            $forms = Http::get("{$this->graphBase()}/{$pageId}/leadgen_forms", [
                'access_token' => $pageAccessToken,
                'limit'        => 200,
            ])->throw()->json('data', []);
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][FORMS_FETCH_FAILED]', [
                'company_id' => $companyId,
                'page_id'    => $pageId,
                'error'      => $e->getMessage(),
            ]);

            $forms = [];
        }

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

        $settings = new SettingsStore($companyId);
        $settings->set('meta_leads.page_id', $pageId);

        if (! empty($forms[0]['id'])) {
            $settings->set('meta_leads.form_id', $forms[0]['id']);
        }

        session()->forget(['meta_company_id', 'meta_user_token', 'meta_pages']);

        return redirect()
            ->route('admin.lead-sources.meta')
            ->with('success', "Connected {$meta->page_name} and synced " . count($forms) . " forms.");
    }

    /**
     * Refresh lead forms.
     */
    public function refresh()
    {
        $companyId = auth()->user()->company_id;

        $meta = MetaPage::where('company_id', $companyId)->first();

        abort_unless($meta && $meta->page_access_token, 400, 'No connected Meta page.');

        try {
            $forms = Http::get("{$this->graphBase()}/{$meta->page_id}/leadgen_forms", [
                'access_token' => $meta->page_access_token,
                'limit'        => 200,
            ])->throw()->json('data', []);
        } catch (\Throwable $e) {
            Log::error('[META_CONNECT][REFRESH_FORMS_FAILED]', [
                'company_id' => $companyId,
                'page_id'    => $meta->page_id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to refresh Meta lead forms.');
        }

        $meta->update([
            'forms_json' => json_encode($forms),
        ]);

        return back()->with('success', 'Forms refreshed: ' . count($forms));
    }

    /**
     * Disconnect Meta.
     */
    public function disconnect()
    {
        $companyId = auth()->user()->company_id;

        MetaPage::where('company_id', $companyId)->delete();

        $settings = new SettingsStore($companyId);
        $settings->delete('meta_leads.page_id');
        $settings->delete('meta_leads.form_id');

        return back()->with('success', 'Disconnected from Facebook Page.');
    }
}