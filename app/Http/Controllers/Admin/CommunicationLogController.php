<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommunicationLogController extends Controller
{
    public function index(Request $request)
    {
        // TODO: pull from whatsapp_messages (and emails/SMS if you add)
        $logs = []; // placeholder

        return view('admin.communication.logs.index', [
            'logs' => $logs,
        ]);
    }
}
