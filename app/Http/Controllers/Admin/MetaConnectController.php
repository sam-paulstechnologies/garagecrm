<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaPage;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaConnectController extends Controller
{
    private string $graph = 'https://graph.facebook.com/v19.0';

    /** Step 1: Send user to FB OAuth */
    public function start(Request $request)
    {
        $companyId   = auth()->user()->company_id ?? auth()->user()->company->id;
        $settings    = new SettingsStore($companyId);

        $appId       = $settings->get('meta.app_id');
        $appSecret   = $settings->get('meta.app_secret');

        abort_unless($appId && $appSecret, 400, 'Meta App ID/Secret not configured.');

        $redirectUri = route('admin.meta.callback');
        $state       = base64_encode(json_encode([
            'company_id' => $companyId,
            'nonce'      => Str::random(24),
        ]));

        $scopes = implode(',', [
            'pages_show_list',
            'pages_manage_metadata',
            'pages_read_engagement',
            'leads_retrieval',
        ]);

        $url = "https://www.facebook.com/v19.0/dialog/oauth"
             . "?client_id={$appId}"
             . "&redirect_uri=" . urlencode($redirectUri)
             . "&response_type=code"
             . "&scope={$scopes}"
             . "&state={$state}";

        return redirect()->away($url);
    }

    /** Step 2: OAuth callback → exchange code → list pages → show picker */
    public function callback(Request $request)
    {
        $code  = $request->query('code');
        $state = json_decode(base64_decode($request->query('state', '')), true) ?: [];

        abort_unless($code && isset($state['company_id']), 400, 'Invalid callback payload.');

        $companyId   = (int) $state['company_id'];
        $settings    = new SettingsStore($companyId);
        $appId       = $settings->get('meta.app_id');
        $appSecret   = $settings->get('meta.app_secret');
        $redirectUri = route('admin.meta.callback');

        // 2a) code -> short-lived user token
        $tokenResp = Http::asForm()->get("{$this->graph}/oauth/access_token", [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ])->throw()->json();

        $userToken = $tokenResp['access_token'] ?? null;
        abort_unless($userToken, 400, 'Failed to get user token.');

        // 2b) short -> long-lived user token
        $llResp = Http::asForm()->get("{$this->graph}/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $userToken,
        ])->throw()->json();

        $longUserToken = $llResp['access_token'] ?? null;
        abort_unless($longUserToken, 400, 'Failed to get long-lived user token.');

        // 2c) list pages
        $pages = Http::get("{$this->graph}/me/accounts", [
            'access_token' => $longUserToken,
            'limit'        => 200,
        ])->throw()->json('data', []);

        // keep token in session for next step
        session([
            'meta_ll_user_token' => $longUserToken,
            'meta_company_id'    => $companyId,
        ]);

        return view('admin.meta.pick-page', [
            'pages' => $pages,
        ]);
    }

    /** Step 3: After selecting a Page → get Page token → fetch forms → save */
    public function selectPage(Request $request)
    {
        $request->validate([
            'page_id'   => 'required|string',
            'page_name' => 'required|string',
        ]);

        $companyId     = (int) session('meta_company_id');
        $longUserToken = (string) session('meta_ll_user_token');

        abort_unless($companyId && $longUserToken, 419, 'Session expired. Please reconnect.');

        $pageId   = $request->page_id;
        $pageName = $request->page_name;

        // Page token
        $pageInfo = Http::get("{$this->graph}/{$pageId}", [
            'fields'       => 'access_token,name',
            'access_token' => $longUserToken,
        ])->throw()->json();

        $pageAccessToken = $pageInfo['access_token'] ?? null;
        abort_unless($pageAccessToken, 400, 'Could not obtain Page access token.');

        // Forms
        $forms = Http::get("{$this->graph}/{$pageId}/leadgen_forms", [
            'access_token' => $pageAccessToken,
            'limit'        => 200,
        ])->throw()->json('data', []);

        // Persist meta_pages row
        $meta = MetaPage::updateOrCreate(
            ['company_id' => $companyId, 'page_id' => $pageId],
            [
                'page_name'         => $pageName,
                'page_access_token' => $pageAccessToken,
                'forms_json'        => json_encode($forms),
            ]
        );

        // Optional defaults
        $settings = new SettingsStore($companyId);
        $settings->set('meta.page_id', $pageId);
        if (!empty($forms[0]['id'])) {
            $settings->set('meta.form_id', $forms[0]['id']);
        }

        // cleanup
        session()->forget(['meta_ll_user_token', 'meta_company_id']);

        return redirect()->route('admin.settings.index')
            ->with('success', "Connected {$meta->page_name} and fetched ".count($forms)." forms.");
    }

    /** Refresh forms for the connected page (button in Settings) */
    public function refresh(Request $request)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->company->id;
        $meta = MetaPage::where('company_id', $companyId)->first();

        abort_unless($meta && $meta->page_access_token, 400, 'No connected page.');

        $forms = Http::get("{$this->graph}/{$meta->page_id}/leadgen_forms", [
            'access_token' => $meta->page_access_token,
            'limit'        => 200,
        ])->throw()->json('data', []);

        $meta->update(['forms_json' => json_encode($forms)]);

        return back()->with('success', 'Forms refreshed: '.count($forms));
    }

    /** Disconnect (delete row + optional settings) */
    public function disconnect(Request $request)
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->company->id;

        MetaPage::where('company_id', $companyId)->delete();

        $settings = new SettingsStore($companyId);
        // FIX: use delete() (SettingsStore doesn't have forget())
        $settings->delete('meta.page_id');
        $settings->delete('meta.form_id');

        return back()->with('success', 'Disconnected from Facebook Page.');
    }
}
