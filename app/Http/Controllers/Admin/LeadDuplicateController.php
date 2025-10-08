<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadDuplicate;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;

class LeadDuplicateController extends Controller
{
    public function index(Request $request)
    {
        $companyId  = (int) $request->user()->company_id;
        $store      = new SettingsStore($companyId);
        $windowDays = (int) $store->get('leads.dedupe_days', config('services.leads.dedupe_days', 30));

        $dupes = LeadDuplicate::with('primary')
            ->where('company_id', $companyId)
            ->orderByDesc('detected_at')
            ->paginate(25);

        return view('admin.leads.duplicates.index', compact('dupes','windowDays'));
    }

    public function updateWindow(Request $request)
    {
        $request->validate(['window_days' => 'required|integer|min:1|max:365']);

        $companyId = (int) $request->user()->company_id;
        (new SettingsStore($companyId))->set('leads.dedupe_days', (int) $request->input('window_days'));

        return back()->with('success', 'Duplicate detection window updated.');
    }
}
