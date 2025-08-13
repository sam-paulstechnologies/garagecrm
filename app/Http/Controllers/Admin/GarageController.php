<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System\Garage;
use App\Models\User;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    public function index()
    {
        $garages = Garage::latest()->paginate(20);
        return view('admin.garages.index', compact('garages'));
    }

    public function create()
    {
        $users = User::all();
        return view('admin.garages.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|unique:garages,slug',
            'location'       => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email',
            'manager_name'   => 'nullable|string|max:255',
            'created_by'     => 'nullable|exists:users,id',
        ]);

        Garage::create($data);

        return redirect()->route('admin.garages.index')->with('success', 'Garage created successfully.');
    }

    public function edit(Garage $garage)
    {
        $users = User::all();
        return view('admin.garages.edit', compact('garage', 'users'));
    }

    public function update(Request $request, Garage $garage)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|unique:garages,slug,' . $garage->id,
            'location'       => 'nullable|string|max:255',
            'contact_email'  => 'nullable|email',
            'manager_name'   => 'nullable|string|max:255',
            'created_by'     => 'nullable|exists:users,id',
        ]);

        $garage->update($data);

        return redirect()->route('admin.garages.index')->with('success', 'Garage updated successfully.');
    }

    public function destroy(Garage $garage)
    {
        $garage->delete();
        return redirect()->route('admin.garages.index')->with('success', 'Garage deleted successfully.');
    }
}
