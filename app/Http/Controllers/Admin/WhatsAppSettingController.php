<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\CompanySetting;
use Illuminate\Http\Request;

class WhatsAppSettingController extends Controller
{
    protected function companyId(): int
    {
        return (int) auth()->user()->company_id;
    }

    public function edit()
    {
        $settings = CompanySetting::where('company_id', $this->companyId())
            ->where('group', 'whatsapp')
            ->pluck('value', 'key')
            ->toArray();

        return view('admin.whatsapp.settings.edit', compact('settings'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'whatsapp_manager_number' => 'nullable|string|max:32',
            'google_review_link'      => 'nullable|url|max:512',
            'garage_location_link'    => 'nullable|url|max:512',
        ]);

        foreach ($data as $key => $value) {
            CompanySetting::updateOrCreate(
                [
                    'company_id' => $this->companyId(),
                    'key'        => $key,
                ],
                [
                    'value' => $value,
                    'group' => 'whatsapp',
                ]
            );
        }

        return back()->with('success', 'WhatsApp settings saved.');
    }
}
