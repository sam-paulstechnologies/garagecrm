<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garage\Garage;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    public function index()
    {
        $companyId = $this->companyId();

        $garages = Garage::where('company_id', $companyId)
            ->latest()
            ->paginate(20);

        return view('admin.garages.index', compact('garages'));
    }

    public function create()
    {
        return view('admin.garages.create');
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'name'       => 'required|string|max:191',
            'phone'      => 'nullable|string|max:30',
            'email'      => 'nullable|email|max:191',
            'address'    => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $data['company_id'] = $companyId;
        $data['is_default'] = !empty($data['is_default']) ? 1 : 0;

        // If setting default, unset other defaults for the same company
        if ($data['is_default'] === 1) {
            Garage::where('company_id', $companyId)->update(['is_default' => 0]);
        }

        $garage = Garage::create($data);

        return redirect()
            ->route('admin.garages.show', $garage->id)
            ->with('success', 'Garage created successfully.');
    }

    public function show(Garage $garage)
    {
        $companyId = $this->companyId();

        abort_unless((int)$garage->company_id === $companyId, 403);

        return view('admin.garages.show', compact('garage'));
    }

    public function edit(Garage $garage)
    {
        $companyId = $this->companyId();

        abort_unless((int)$garage->company_id === $companyId, 403);

        return view('admin.garages.edit', compact('garage'));
    }

    public function update(Request $request, Garage $garage)
    {
        $companyId = $this->companyId();

        abort_unless((int)$garage->company_id === $companyId, 403);

        $data = $request->validate([
            'name'       => 'required|string|max:191',
            'phone'      => 'nullable|string|max:30',
            'email'      => 'nullable|email|max:191',
            'address'    => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $data['is_default'] = !empty($data['is_default']) ? 1 : 0;

        // If setting default, unset other defaults for the same company
        if ($data['is_default'] === 1) {
            Garage::where('company_id', $companyId)
                ->where('id', '!=', $garage->id)
                ->update(['is_default' => 0]);
        }

        $garage->update($data);

        return redirect()
            ->route('admin.garages.show', $garage->id)
            ->with('success', 'Garage updated successfully.');
    }

    public function destroy(Garage $garage)
    {
        $companyId = $this->companyId();

        abort_unless((int)$garage->company_id === $companyId, 403);

        $garage->delete();

        return redirect()
            ->route('admin.garages.index')
            ->with('success', 'Garage deleted successfully.');
    }
}