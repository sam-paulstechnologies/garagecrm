<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\System\Company;
use App\Models\System\Garage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['company', 'garage'])->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $companies = Company::all();
        $garages = Garage::all();
        return view('admin.users.create', compact('companies', 'garages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string|max:20',
            'role'       => 'required|string',
            'password'   => 'required|string|min:6|confirmed',
            'company_id' => 'nullable|exists:companies,id',
            'garage_id'  => 'nullable|exists:garages,id',
            'status'     => 'required|string',
        ]);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $companies = Company::all();
        $garages = Garage::all();
        return view('admin.users.edit', compact('user', 'companies', 'garages'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:20',
            'role'       => 'required|string',
            'password'   => 'nullable|string|min:6|confirmed',
            'company_id' => 'nullable|exists:companies,id',
            'garage_id'  => 'nullable|exists:garages,id',
            'status'     => 'required|string',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
