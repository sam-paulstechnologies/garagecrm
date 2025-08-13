<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = Job::where('company_id', $companyId);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $jobs = $query->with(['beforePhotos', 'afterPhotos'])->paginate(10);

        return view('jobs.index', compact('jobs'));
    }
}
