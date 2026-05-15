<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Services\Settings\SettingsStore;
use App\Services\Settings\SettingsValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SettingsController extends Controller
{
    protected function company()
    {
        $company = auth()->user()?->company;

        abort_if(!$company || !$company->id, 403);

        return $company;
    }

    public function index()
    {
        $company = $this->company();

        $store = new SettingsStore($company->id);

        return view('admin.settings.index', [
            'company'              => $company,
            'settings'             => $store->all(),
            'waFrom'               => $store->get('twilio.whatsapp_from'),
            'managerWhatsapp'      => $store->get('whatsapp.manager_number'),
            'googleReviewLink'     => $store->get('garage.google_review_link'),
            'garageLocationLink'   => $store->get('garage.location_link'),

            // Current registered route name is api.webhooks.twilio.whatsapp.
            // Fallback prevents /admin/settings from crashing if route name changes later.
            'webhookUrl'           => Route::has('api.webhooks.twilio.whatsapp')
                ? route('api.webhooks.twilio.whatsapp')
                : url('/api/v1/webhooks/twilio/whatsapp'),
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $company = $this->company();

        DB::transaction(function () use ($request, $company) {
            $this->persistSettings($request, (int) $company->id);
        });

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    public function testMetaInline()
    {
        $company = $this->company();
        $store = new SettingsStore($company->id);

        $hasToken = filled($store->get('meta.access_token'));
        $hasPageId = filled($store->get('meta.page_id'));

        if (!$hasToken || !$hasPageId) {
            return back()->with(
                'warning',
                'Meta settings are incomplete. Please add Meta access token and page ID.'
            );
        }

        return back()->with(
            'success',
            'Meta settings found. Full live test can be connected after tester service method is confirmed.'
        );
    }

    public function testTwilioInline()
    {
        $company = $this->company();
        $store = new SettingsStore($company->id);

        $hasSid = filled($store->get('twilio.account_sid'));
        $hasToken = filled($store->get('twilio.auth_token'));
        $hasFrom = filled($store->get('twilio.whatsapp_from'));

        if (!$hasSid || !$hasToken || !$hasFrom) {
            return back()->with(
                'warning',
                'Twilio settings are incomplete. Please add Account SID, Auth Token, and WhatsApp From number.'
            );
        }

        return back()->with(
            'success',
            'Twilio settings found. Full live test can be connected after tester service method is confirmed.'
        );
    }

    /** ---------------- Helpers ---------------- */

    private function persistSettings(UpdateSettingsRequest $request, int $companyId): void
    {
        $company = $this->company();

        abort_if((int) $company->id !== (int) $companyId, 403);

        // 1️⃣ Company basics
        $company->fill([
            'name'    => $request->input('company.name', $company->name),
            'email'   => $request->input('company.email', $company->email),
            'phone'   => $request->input('company.phone', $company->phone),
            'address' => $request->input('company.address', $company->address),
        ])->save();

        // 2️⃣ Normalize inputs
        $input = [
            // Meta
            'meta.access_token'      => $request->input('meta.access_token'),
            'meta.page_id'           => $request->input('meta.page_id'),
            'meta.app_id'            => $request->input('meta.app_id'),
            'meta.form_ids'          => $request->input('meta.form_ids'),

            // Twilio
            'twilio.account_sid'     => $request->input('twilio.account_sid'),
            'twilio.auth_token'      => $request->input('twilio.auth_token'),
            'twilio.whatsapp_from'   => $request->input('twilio.whatsapp_from'),

            // WhatsApp / Garage
            'whatsapp.manager_number'    => $request->input('manager_whatsapp'),
            'garage.google_review_link'  => $request->input('google_review_link'),
            'garage.location_link'       => $request->input('garage_location_link'),

            // System
            'system.timezone'             => $request->input('system.timezone', 'Asia/Dubai'),
            'system.default_country_code' => $request->input('system.default_country_code', '+971'),
            'system.notification_email'   => $request->input('system.notification_email'),
        ];

        // Meta form IDs normalization
        if (!empty($input['meta.form_ids'])) {
            $raw = $input['meta.form_ids'];

            if (is_array($raw)) {
                $input['meta.form_ids'] = json_encode(array_values(array_filter($raw)));
            } else {
                $raw = trim((string) $raw);

                if (!$this->looksLikeJsonArray($raw)) {
                    $arr = array_map('trim', explode(',', $raw));
                    $input['meta.form_ids'] = json_encode(array_values(array_filter($arr)));
                }
            }
        }

        // 3️⃣ Validate formats
        SettingsValidator::validate($input);

        // 4️⃣ Persist
        $store = new SettingsStore($companyId);

        // Meta
        $store->set('meta.access_token', $input['meta.access_token'], ['group' => 'meta', 'encrypt' => true]);
        $store->set('meta.page_id',      $input['meta.page_id'],      ['group' => 'meta']);
        $store->set('meta.app_id',       $input['meta.app_id'],       ['group' => 'meta']);
        $store->set('meta.form_ids',     $input['meta.form_ids'],     ['group' => 'meta']);

        // Twilio
        $store->set('twilio.account_sid',   $input['twilio.account_sid'],   ['group' => 'twilio', 'encrypt' => true]);
        $store->set('twilio.auth_token',    $input['twilio.auth_token'],    ['group' => 'twilio', 'encrypt' => true]);
        $store->set('twilio.whatsapp_from', $input['twilio.whatsapp_from'], ['group' => 'twilio']);

        // WhatsApp / Garage
        $store->set('whatsapp.manager_number', $input['whatsapp.manager_number'], ['group' => 'whatsapp']);
        $store->set('garage.google_review_link', $input['garage.google_review_link'], ['group' => 'garage']);
        $store->set('garage.location_link', $input['garage.location_link'], ['group' => 'garage']);

        // System
        $store->set('system.timezone',             $input['system.timezone'],             ['group' => 'system']);
        $store->set('system.default_country_code', $input['system.default_country_code'], ['group' => 'system']);
        $store->set('system.notification_email',   $input['system.notification_email'],   ['group' => 'system']);

        // 5️⃣ Audit
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
        if (!str_starts_with($s, '[') || !str_ends_with($s, ']')) {
            return false;
        }

        json_decode($s, true);

        return json_last_error() === JSON_ERROR_NONE;
    }
}