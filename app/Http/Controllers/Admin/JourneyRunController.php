<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyStepExecutor;

class JourneyRunController extends Controller
{
    public function run(JourneyEnrollment $enrollment)
    {
        app(JourneyStepExecutor::class)->execute($enrollment);

        return back()->with('success', 'Journey executed');
    }
}
