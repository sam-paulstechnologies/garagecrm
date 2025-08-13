<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function searchClients(Request $request)
    {
        $term = $request->get('q', '');

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->where('name', 'like', "%{$term}%")
            ->select('id', 'name as text')
            ->limit(10)
            ->get();

        return response()->json($clients);
    }
}
