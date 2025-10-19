<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WhatsAppCampaignApiController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['ok' => true, 'items' => []]);
    }

    public function show($id)
    {
        return response()->json(['ok' => true, 'id' => (int) $id]);
    }

    public function store(Request $request)
    {
        return response()->json(['ok' => true, 'created' => true], 201);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['ok' => true, 'updated' => (int) $id]);
    }

    public function destroy($id)
    {
        return response()->json(['ok' => true, 'deleted' => (int) $id]);
    }
}
