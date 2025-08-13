<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JobCardController extends Controller
{
    public function index() {
        return view('mechanic.job-cards.index');
    }

    public function show($id) {
        return view('mechanic.job-cards.show', compact('id'));
    }
}
