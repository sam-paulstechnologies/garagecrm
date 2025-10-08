<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Services\Settings\SettingsStore;
use App\Services\Settings\SettingsValidator;
use App\Services\IntegrationTest\MetaTester;
use App\Services\IntegrationTest\TwilioTester;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $company  = $user->company;
        $store    = new SettingsStore($company->id);
        $settings = $store->all(); // returns flat ['meta.access_token' => '...', ...]

        return view('admin.settings.index', [
            'company'  => $company,
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $company = auth()->user()->company;
        $this->persistSettings($request, $company->id);

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated.');
    }

    /** Save + test Meta in one submit */
    public function testMetaInline(UpdateSettingsRequest $request)
    {
        $company = auth()->user()->company;
        $this->persistSettings($request, $company->id);

        $store = new SettingsStore($company->id);
        $token = (string) $store->get('meta.access_token', '');

        $res = app(MetaTester::class)->test($token);
        return redirect()->route('admin.settings.index')
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    /** Save + test Twilio in one submit */
    public function testTwilioInline(UpdateSettingsRequest $request)
    {
        $company = auth()->user()->company;
        $this->persistSettings($request, $company->id);

        $store = new SettingsStore($company->id);
        $sid  = (string) $store->get('twilio.account_sid', '');
        $tok  = (string) $store->get('twilio.auth_token', '');
        $from = (string) $store->get('twilio.whatsapp_from', '');

        $res = app(TwilioTester::class)->test($sid, $tok, $from);
        return redirect()->route('admin.settings.index')
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    /** ---- helpers ---- */

    private function persistSettings(UpdateSettingsRequest $request, int $companyId): void
    {
        $company = auth()->user()->company;

        // 1) Update company basics
        $company->fill([
            'name'    => $request->input('company.name', $company->name),
            'email'   => $request->input('company.email', $company->email),
            'phone'   => $request->input('company.phone', $company->phone),
            'address' => $request->input('company.address', $company->address),
        ])->save();

        // 2) Flatten + normalize settings
        $input = [
            'meta.access_token'           => $request->input('meta.access_token'),
            'meta.page_id'                => $request->input('meta.page_id'),
            'meta.app_id'                 => $request->input('meta.app_id'),
            'meta.form_ids'               => $request->input('meta.form_ids'),
            'twilio.account_sid'          => $request->input('twilio.account_sid'),
            'twilio.auth_token'           => $request->input('twilio.auth_token'),
            'twilio.whatsapp_from'        => $request->input('twilio.whatsapp_from'),
            'system.timezone'             => $request->input('system.timezone', 'Asia/Dubai'),
            'system.default_country_code' => $request->input('system.default_country_code', '+971'),
            'system.notification_email'   => $request->input('system.notification_email'),
        ];

        // Accept CSV / JSON / array for form_ids; store JSON string
        if (!empty($input['meta.form_ids'])) {
            $raw = $input['meta.form_ids'];
            if (is_array($raw)) {
                $arr = array_values(array_filter(array_map('trim', $raw)));
                $input['meta.form_ids'] = json_encode($arr);
            } else {
                $raw = trim((string) $raw);
                if (!$this->looksLikeJsonArray($raw)) {
                    $arr = array_values(array_filter(array_map('trim', explode(',', $raw))));
                    $input['meta.form_ids'] = json_encode($arr);
                }
            }
        }

        // 3) Validate formats
        SettingsValidator::validate($input);

        // 4) Save via SettingsStore
        $store = new SettingsStore($companyId);

        // Meta
        $store->set('meta.access_token', $input['meta.access_token'] ?? null, ['group' => 'meta', 'encrypt' => true]);
        $store->set('meta.page_id',      $input['meta.page_id'] ?? null,      ['group' => 'meta']);
        $store->set('meta.app_id',       $input['meta.app_id'] ?? null,       ['group' => 'meta']);
        $store->set('meta.form_ids',     $input['meta.form_ids'] ?? null,     ['group' => 'meta']);

        // Twilio
        $store->set('twilio.account_sid',   $input['twilio.account_sid'] ?? null,   ['group' => 'twilio', 'encrypt' => true]);
        $store->set('twilio.auth_token',    $input['twilio.auth_token'] ?? null,    ['group' => 'twilio', 'encrypt' => true]);
        $store->set('twilio.whatsapp_from', $input['twilio.whatsapp_from'] ?? null, ['group' => 'twilio']);

        // System
        $store->set('system.timezone',             $input['system.timezone'] ?? 'Asia/Dubai', ['group' => 'system']);
        $store->set('system.default_country_code', $input['system.default_country_code'] ?? '+971', ['group' => 'system']);
        $store->set('system.notification_email',   $input['system.notification_email'] ?? null, ['group' => 'system']);

        // Audit (optional)
        DB::table('settings_audit_logs')->insert([
            'company_id'     => $companyId,
            'key'            => 'bulk_update',
            'old_value_hash' => null,
            'new_value_hash' => substr(hash('sha256', json_encode($input)), 0, 64),
            'updated_by'     => auth()->id(),
            'created_at'     => now(),
        ]);
    }

    private function looksLikeJsonArray(string $s): bool
    {
        if (!str_starts_with($s, '[') || !str_ends_with($s, ']')) return false;
        json_decode($s, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
