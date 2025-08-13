<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Company;

class SettingsController extends Controller
{
    public function edit()
    {
        $company = auth()->user()->company;
        return view('settings.company-profile', compact('company'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'whatsapp_number' => 'nullable|string|max:20',
            'email_integration' => 'nullable|string|max:255',
        ]);

        $company = auth()->user()->company;
        $company->update([
            'name' => $request->company_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'whatsapp_number' => $request->whatsapp_number,
            'email_integration' => $request->email_integration,
        ]);

        return redirect()->route('settings.company.edit')->with('success', 'Company profile updated successfully.');
    }
}