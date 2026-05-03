<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shared\CommunicationLog;
use Illuminate\Http\Request;

class CommunicationLogController extends Controller
{
    public function index(Request $request)
    {
        $companyId = company_id();

        $logs = CommunicationLog::where('company_id', $companyId)
            ->when($request->channel, fn ($q) =>
                $q->where('channel', strtolower($request->channel))
            )
            ->when($request->direction, fn ($q) =>
                $q->where('direction', strtolower($request->direction))
            )
            ->when($request->q, function ($q) use ($request) {
                $q->where('body', 'like', '%'.$request->q.'%')
                  ->orWhere('to_phone', 'like', '%'.$request->q.'%')
                  ->orWhere('to_email', 'like', '%'.$request->q.'%');
            })
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.communication_logs.index', compact('logs'));
    }
}
