<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppMessageApiController extends Controller
{
    // GET /api/whatsapp/messages
    public function index(Request $r)
    {
        $q = MessageLog::query()->orderByDesc('id');

        if ($companyId = $r->integer('company_id')) $q->where('company_id', $companyId);
        if ($leadId = $r->integer('lead_id')) $q->where('lead_id', $leadId);
        if ($dir = $r->input('direction')) $q->where('direction', $dir);
        if ($phone = trim((string) $r->input('phone'))) {
            $q->where(function ($x) use ($phone) {
                $x->where('to_number', 'like', "%$phone%")
                  ->orWhere('from_number', 'like', "%$phone%");
            });
        }
        if ($tpl = trim((string) $r->input('template'))) $q->where('template', 'like', "%$tpl%");
        if ($from = $r->input('from')) $q->whereDate('created_at', '>=', $from);
        if ($to = $r->input('to')) $q->whereDate('created_at', '<=', $to);

        $logs = $q->paginate(25)->appends($r->query());

        return response()->json([
            'ok'   => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    // GET /api/whatsapp/messages/{id}
    public function show($id)
    {
        $log = MessageLog::find($id);
        if (!$log) {
            return response()->json(['ok' => false, 'error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['ok' => true, 'data' => $log]);
    }

    // POST /api/whatsapp/messages/{id}/retry
    public function retry($id)
    {
        $log = MessageLog::find($id);
        if (!$log) {
            return response()->json(['ok' => false, 'error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        if ($log->direction !== 'out') {
            return response()->json(['ok' => false, 'error' => 'Only outbound messages can be retried'], 422);
        }

        $template  = $log->template ?: 'lead_welcome';
        $companyId = (int) $log->company_id;
        $leadId    = (int) ($log->lead_id ?? 0);
        $to        = (string) $log->to_number;

        SendWhatsAppFromTemplate::dispatch(
            companyId:    $companyId,
            leadId:       $leadId,
            toNumberE164: $to,
            templateName: $template,
            placeholders: [],
            links:        [],
            context:      ['retry_of_log_id' => $log->id],
            action:       'ask_vehicle_info'
        );

        return response()->json(['ok' => true, 'message' => 'Re-dispatched'], 202);
    }
}
