<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class LeadFormController extends Controller
{
    public function index()
    {
        return view('admin.leads.custom-form');
    }
}
