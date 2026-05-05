<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return view('manager.dashboard');
    }
}