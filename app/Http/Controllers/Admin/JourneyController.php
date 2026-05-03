<?php

// app/Http/Controllers/Admin/JourneyController.php
// (PLACEHOLDER - because you said you do not have it)
// This does NOT change your existing Journeys flow; it prevents "class not found" when you add future routes.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.dashboard')
            ->with('error', 'Journeys builder UI not enabled yet. Timeline is available under enrollments.');
    }
}
