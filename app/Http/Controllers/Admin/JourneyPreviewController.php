<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journey;

class JourneyPreviewController extends Controller
{
    public function show(Journey $journey)
    {
        $journey->load('steps');

        return view('admin.journeys.preview', compact('journey'));
    }
}
