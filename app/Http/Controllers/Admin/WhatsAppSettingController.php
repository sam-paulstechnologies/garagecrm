<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\CompanySetting;
use Illuminate\Http\Request;

class WhatsAppSettingController extends Controller
{
    protected function companyId(): int { return (int)(auth()->user()->company_id ?? 1); }

    public function edit()
    {
        $set = CompanySetting::firstOrCreate(['company_id'=>$this->companyId()]);
        return view('admin.whatsapp.settings.edit', compact('set'));
    }

    public function save(Request $r)
    {
        $data = $r->validate([
            'manager_phone'      => 'nullable|string|max:32',
            'google_review_link' => 'nullable|url|max:512',
        ]);

        $set = CompanySetting::firstOrCreate(['company_id'=>$this->companyId()]);
        $set->update($data);

        return back()->with('success','Settings saved.');
    }
}
