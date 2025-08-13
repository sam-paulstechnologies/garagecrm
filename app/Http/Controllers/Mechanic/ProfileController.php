<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit() {
        return view('mechanic.profile.edit');
    }

    public function update(Request $request) {
        // Handle profile update logic here
        return back()->with('success', 'Profile updated successfully.');
    }
}
