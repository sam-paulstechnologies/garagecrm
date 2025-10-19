<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppSettingApiController extends Controller
{
    // GET /api/whatsapp/settings
    public function index(Request $request)
    {
        // Return an empty list for now; wire up real settings later
        return response()->json(['ok' => true, 'items' => []]);
    }

    // GET /api/whatsapp/settings/{id}
    public function show($id)
    {
        return response()->json(['ok' => true, 'id' => (int) $id, 'data' => []]);
    }

    // PUT/PATCH /api/whatsapp/settings/{id}
    public function update(Request $request, $id)
    {
        // Accept payload but do nothing yet
        return response()->json(['ok' => true, 'updated' => (int) $id]);
    }
}
