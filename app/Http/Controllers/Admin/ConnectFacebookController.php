<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConnectFacebookController extends Controller
{
    public function redirect(Request $request)
    {
        $appId   = config('services.meta.app_id');
        $version = ltrim(config('services.meta.graph_version', 'v19.0'), 'v');
        $scopes  = [
            'pages_show_list',     // list pages
            'pages_manage_metadata', // subscribe webhooks
            'leads_retrieval',     // read leads
            'pages_read_engagement'// safe default
        ];

        $redirectUri = route('admin.settings.meta.callback'); // must match in Meta app settings

        $url = 'https://www.facebook.com/v' . $version . '/dialog/oauth?' . http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirectUri,
            'scope'         => implode(',', $scopes),
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $code    = $request->query('code');
        $appId   = config('services.meta.app_id');
        $secret  = config('services.meta.app_secret');
        $version = config('services.meta.graph_version', 'v19.0');
        $redirectUri = route('admin.settings.meta.callback');

        if (!$code) {
            return redirect()->route('admin.settings.index')->with('error', 'Facebook connect failed: missing code.');
        }

        $http = Http::withOptions(['verify' => config('services.curl_ca_bundle', true)]);

        // 1) short-lived user token
        $tokenResp = $http->get("https://graph.facebook.com/{$version}/oauth/access_token", [
            'client_id'     => $appId,
            'client_secret' => $secret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        if ($tokenResp->failed()) {
            return redirect()->route('admin.settings.index')->with('error', 'Facebook token exchange failed: '.$tokenResp->body());
        }

        $shortUserToken = $tokenResp->json('access_token');

        // 2) long-lived user token (60 days)
        $longResp = $http->get("https://graph.facebook.com/{$version}/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $secret,
            'fb_exchange_token' => $shortUserToken,
        ]);

        if ($longResp->failed()) {
            return redirect()->route('admin.settings.index')->with('error', 'Facebook long-lived token failed: '.$longResp->body());
        }

        $longUserToken = $longResp->json('access_token');

        // 3) fetch pages for this user (includes page access tokens)
        $pagesResp = $http->get("https://graph.facebook.com/{$version}/me/accounts", [
            'access_token' => $longUserToken,
            'fields'       => 'id,name,access_token',
            'limit'        => 200,
        ]);

        if ($pagesResp->failed()) {
            return redirect()->route('admin.settings.index')->with('error', 'Facebook pages fetch failed: '.$pagesResp->body());
        }

        $pages = collect($pagesResp->json('data') ?: [])->map(function ($p) {
            return [
                'id'            => $p['id'] ?? null,
                'name'          => $p['name'] ?? null,
                'access_token'  => $p['access_token'] ?? null,
            ];
        })->filter(fn($p) => $p['id'] && $p['access_token'])->values()->all();

        if (empty($pages)) {
            return redirect()->route('admin.settings.index')->with('error', 'No Facebook Pages found for this user.');
        }

        // Store pages in session for selection
        session()->put('fb_pages_select', $pages);
        return view('admin.settings.meta-connect', ['pages' => $pages]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'page_id' => 'required|string',
        ]);

        $companyId = (int) $request->user()->company_id;
        $pages = (array) session()->get('fb_pages_select', []);

        $selected = collect($pages)->firstWhere('id', $request->input('page_id'));
        if (!$selected) {
            return redirect()->route('admin.settings.index')->with('error', 'Selected page not found in session.');
        }

        $pageId   = $selected['id'];
        $pageName = $selected['name'] ?? null;
        $pageToken= $selected['access_token'];

        $version = config('services.meta.graph_version', 'v19.0');
        $http = Http::withOptions(['verify' => config('services.curl_ca_bundle', true)]);

        // Subscribe this Page to leadgen webhooks
        $subResp = $http->asForm()->post("https://graph.facebook.com/{$version}/{$pageId}/subscribed_apps", [
            'subscribed_fields' => 'leadgen',
            'access_token'      => $pageToken,
        ]);

        if ($subResp->failed()) {
            Log::error('FB subscribe failed', ['body' => $subResp->body()]);
            return redirect()->route('admin.settings.index')->with('error', 'Failed to subscribe the Page to webhooks.');
        }

        // Save/Update in DB
        MetaPage::updateOrCreate(
            ['page_id' => $pageId],
            [
                'company_id'        => $companyId,
                'page_name'         => $pageName,
                'page_access_token' => $pageToken,
                // 'forms_json'      => json_encode([...]) // optional later
            ]
        );

        // Cleanup
        session()->forget('fb_pages_select');

        return redirect()->route('admin.settings.index')->with('success', "Connected Facebook Page: {$pageName} ({$pageId}).");
    }
}
