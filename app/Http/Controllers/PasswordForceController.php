<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordForceController extends Controller
{
    /**
     * Show the "set a new password" screen after an admin reset.
     */
    public function edit()
    {
        return view('auth.force-password');
    }

    /**
     * Persist the new password and clear the must_change_password flag.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($data['password']);

        // Clear the flag if present.
        if (\Schema::hasColumn('users', 'must_change_password')) {
            $user->must_change_password = false;
        }

        $user->save();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Password updated successfully.');
    }
}
