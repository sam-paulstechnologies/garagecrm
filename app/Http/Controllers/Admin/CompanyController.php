<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\Company;
use App\Models\System\Plan;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with('plan')->latest()->paginate(20);
        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        $plans = Plan::all();
        return view('admin.companies.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:companies,email',
            'phone'        => 'nullable|string',
            'address'      => 'nullable|string',
            'plan_id'      => 'nullable|exists:plans,id',
            'trial_ends_at'=> 'nullable|date',
        ]);

        Company::create($data);

        return redirect()->route('admin.companies.index')->with('success', 'Company created.');
    }

    public function edit(Company $company)
    {
        $plans = Plan::all();
        return view('admin.companies.edit', compact('company', 'plans'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:companies,email,' . $company->id,
            'phone'        => 'nullable|string',
            'address'      => 'nullable|string',
            'plan_id'      => 'nullable|exists:plans,id',
            'trial_ends_at'=> 'nullable|date',
        ]);

        $company->update($data);

        return redirect()->route('admin.companies.index')->with('success', 'Company updated.');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()->route('admin.companies.index')->with('success', 'Company deleted.');
    }
}
