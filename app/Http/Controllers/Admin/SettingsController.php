<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // You can load settings from DB here if needed
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        // Handle saving settings
        // Example: Setting::updateOrCreate([...])

        return back()->with('success', 'Settings updated.');
    }
}
