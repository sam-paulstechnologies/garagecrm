<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PasswordForceController extends Controller
{
    /**
     * Show force password screen
     */
    public function edit()
    {
        return view('auth.force-password');
    }

    /**
     * Update password
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        $user->password = Hash::make($data['password']);

        // Clear flag if exists
        if (Schema::hasColumn('users', 'must_change_password')) {
            $user->must_change_password = false;
        }

        $user->save();

        // 🔥 FIX: redirect based on role
        return redirect()
            ->route('dashboard')
            ->with('success', 'Password updated successfully.');
    }
}