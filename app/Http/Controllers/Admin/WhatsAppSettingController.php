<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\CompanySetting;
use Illuminate\Http\Request;

class WhatsAppSettingController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function edit()
    {
        $settings = CompanySetting::where('company_id', $this->companyId())
            ->where('group', 'whatsapp')
            ->pluck('value', 'key')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility
        |--------------------------------------------------------------------------
        | Some older services may read dotted keys like whatsapp.manager_number,
        | while the current Blade uses simple form keys like whatsapp_manager_number.
        |--------------------------------------------------------------------------
        */
        $settings['whatsapp_manager_number'] = $settings['whatsapp_manager_number']
            ?? $settings['whatsapp.manager_number']
            ?? '';

        $settings['google_review_link'] = $settings['google_review_link']
            ?? $settings['whatsapp.google_review_link']
            ?? '';

        $settings['garage_location_link'] = $settings['garage_location_link']
            ?? $settings['whatsapp.garage_location_link']
            ?? '';

        $settings['whatsapp_active'] = $settings['whatsapp_active']
            ?? $settings['whatsapp.active']
            ?? '1';

        $settings['whatsapp_provider'] = $settings['whatsapp_provider']
            ?? $settings['whatsapp.provider']
            ?? 'meta';

        $settings['positive_feedback_threshold'] = $settings['positive_feedback_threshold']
            ?? $settings['whatsapp.positive_feedback_threshold']
            ?? '4';

        return view('admin.whatsapp.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp_active'             => ['required', 'in:0,1'],
            'whatsapp_provider'           => ['required', 'in:meta,twilio'],
            'whatsapp_manager_number'     => ['nullable', 'string', 'max:32'],
            'google_review_link'          => ['nullable', 'url', 'max:512'],
            'garage_location_link'        => ['nullable', 'url', 'max:512'],
            'positive_feedback_threshold' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save both new/simple keys and dotted legacy keys
        |--------------------------------------------------------------------------
        | This prevents current pages and older WhatsApp services from breaking.
        |--------------------------------------------------------------------------
        */
        $settingsToSave = [
            // Simple keys used by current Blade
            'whatsapp_active'             => $data['whatsapp_active'],
            'whatsapp_provider'           => $data['whatsapp_provider'],
            'whatsapp_manager_number'     => $data['whatsapp_manager_number'] ?? '',
            'google_review_link'          => $data['google_review_link'] ?? '',
            'garage_location_link'        => $data['garage_location_link'] ?? '',
            'positive_feedback_threshold' => (string) $data['positive_feedback_threshold'],

            // Dotted keys used by older services / escalation code
            'whatsapp.active'                      => $data['whatsapp_active'],
            'whatsapp.provider'                    => $data['whatsapp_provider'],
            'whatsapp.manager_number'              => $data['whatsapp_manager_number'] ?? '',
            'whatsapp.google_review_link'          => $data['google_review_link'] ?? '',
            'whatsapp.garage_location_link'        => $data['garage_location_link'] ?? '',
            'whatsapp.positive_feedback_threshold' => (string) $data['positive_feedback_threshold'],
        ];

        foreach ($settingsToSave as $key => $value) {
            CompanySetting::updateOrCreate(
                [
                    'company_id' => $this->companyId(),
                    'group'      => 'whatsapp',
                    'key'        => $key,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        return back()->with('success', 'WhatsApp settings saved successfully.');
    }
}