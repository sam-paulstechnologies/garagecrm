<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index() {
        return view('mechanic.leads.index');
    }

    public function show($id) {
        return view('mechanic.leads.show', compact('id'));
    }
}
