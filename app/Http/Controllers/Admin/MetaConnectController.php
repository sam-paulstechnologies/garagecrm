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
    private string $graph = 'https://graph.facebook.com/v19.0';

    /**
     * Step 1: Redirect user to Facebook OAuth
     */
    public function start(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = http_build_query([
            'client_id'     => config('services.meta.app_id'),

            // ✅ TEMPORARY: force ngrok callback (Meta-friendly)
            'redirect_uri' => url('/public/admin/lead-sources/meta/callback'),


            'response_type' => 'code',
            'scope'         => implode(',', [
                'pages_show_list',
                'pages_read_engagement',
                'leads_retrieval',
            ]),
        ]);

        return redirect("https://www.facebook.com/v19.0/dialog/oauth?{$query}");
    }


    /**
     * Step 2: OAuth callback → exchange token → prepare page selection
     */
    public function callback(Request $request)
    {
        // Meta error (user cancelled / denied)
        if ($request->has('error')) {
            Log::warning('Meta OAuth cancelled', $request->all());

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Facebook connection was cancelled.');
        }

        $code = $request->query('code');

        if (!$code) {
            Log::error('Meta OAuth callback missing code', $request->all());

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Facebook login failed. Please try again.');
        }

        $companyId = auth()->user()->company_id;
        $settings  = new SettingsStore($companyId);

        $appId     = $settings->get('meta.app_id');
        $appSecret = $settings->get('meta.app_secret');

        $redirectUri = route('admin.lead-sources.meta.callback');

        // Exchange code for short-lived user token
        $tokenResponse = Http::asForm()->get("{$this->graph}/oauth/access_token", [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        if (!$tokenResponse->ok()) {
            Log::error('Meta token exchange failed', $tokenResponse->json());

            return redirect()
                ->route('admin.lead-sources.meta')
                ->with('error', 'Unable to authenticate with Facebook.');
        }

        $userAccessToken = $tokenResponse->json('access_token');

        // Store temporarily for page selection
        session([
            'meta_company_id'     => $companyId,
            'meta_user_token'     => $userAccessToken,
        ]);

        return redirect()
            ->route('admin.lead-sources.meta')
            ->with('success', 'Facebook connected. Please select a page.');
    }

    /**
     * Step 3: Page selected → fetch page token + lead forms
     */
    public function selectPage(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|string',
            'page_name' => 'required|string',
        ]);

        $companyId     = session('meta_company_id');
        $userToken     = session('meta_user_token');

        abort_unless($companyId && $userToken, 419, 'Session expired. Please reconnect Facebook.');

        $pageId   = $request->page_id;
        $pageName = $request->page_name;

        // Fetch Page access token
        $pageInfo = Http::get("{$this->graph}/{$pageId}", [
            'fields'       => 'access_token,name',
            'access_token' => $userToken,
        ])->throw()->json();

        $pageAccessToken = $pageInfo['access_token'] ?? null;

        abort_unless($pageAccessToken, 400, 'Unable to fetch Page access token.');

        // Fetch Lead Forms
        $forms = Http::get("{$this->graph}/{$pageId}/leadgen_forms", [
            'access_token' => $pageAccessToken,
            'limit'        => 200,
        ])->throw()->json('data', []);

        $meta = MetaPage::updateOrCreate(
            [
                'company_id' => $companyId,
                'page_id'    => $pageId,
            ],
            [
                'page_name'         => $pageName,
                'page_access_token' => $pageAccessToken,
                'forms_json'        => json_encode($forms),
            ]
        );

        $settings = new SettingsStore($companyId);
        $settings->set('meta.page_id', $pageId);

        if (!empty($forms[0]['id'])) {
            $settings->set('meta.form_id', $forms[0]['id']);
        }

        session()->forget(['meta_company_id', 'meta_user_token']);

        return redirect()
            ->route('admin.lead-sources.meta')
            ->with('success', "Connected {$meta->page_name} and synced " . count($forms) . " forms.");
    }

    /**
     * Refresh lead forms
     */
    public function refresh()
    {
        $companyId = auth()->user()->company_id;
        $meta      = MetaPage::where('company_id', $companyId)->first();

        abort_unless($meta && $meta->page_access_token, 400, 'No connected Meta page.');

        $forms = Http::get("{$this->graph}/{$meta->page_id}/leadgen_forms", [
            'access_token' => $meta->page_access_token,
            'limit'        => 200,
        ])->throw()->json('data', []);

        $meta->update(['forms_json' => json_encode($forms)]);

        return back()->with('success', 'Forms refreshed: ' . count($forms));
    }

    /**
     * Disconnect Meta
     */
    public function disconnect()
    {
        $companyId = auth()->user()->company_id;

        MetaPage::where('company_id', $companyId)->delete();

        $settings = new SettingsStore($companyId);
        $settings->delete('meta.page_id');
        $settings->delete('meta.form_id');

        return back()->with('success', 'Disconnected from Facebook Page.');
    }
}
