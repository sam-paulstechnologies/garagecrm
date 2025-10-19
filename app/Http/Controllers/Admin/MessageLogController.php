<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MessageLogController extends Controller
{
    public function index(Request $r)
    {
        $companyId = (int) auth()->user()->company_id;

        $q = MessageLog::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        // -----------------
        // Filters
        // -----------------
        // (Optionally narrow to a specific lead)
        if ($leadId = $r->integer('lead_id')) {
            $q->where('lead_id', $leadId);
        }

        // Direction: supports 'out'/'in' or 'outbound'/'inbound'
        if ($dir = trim((string) $r->input('direction'))) {
            $dir = match (strtolower($dir)) {
                'outbound' => 'out',
                'inbound'  => 'in',
                default    => $dir,
            };
            $q->where('direction', $dir);
        }

        // Channel (e.g., 'whatsapp', 'sms', etc.)
        if ($channel = trim((string) $r->input('channel'))) {
            $q->where('channel', $channel);
        }

        // Provider status (queued|sent|delivered|failed|…)
        if ($status = trim((string) $r->input('status'))) {
            $q->where('provider_status', $status);
        }

        // Phone search covers to/from
        if ($phone = trim((string) $r->input('phone'))) {
            $q->where(function ($x) use ($phone) {
                $x->where('to_number', 'like', "%{$phone}%")
                  ->orWhere('from_number', 'like', "%{$phone}%");
            });
        }

        // Template contains
        if ($tpl = trim((string) $r->input('template'))) {
            $q->where('template', 'like', "%{$tpl}%");
        }

        // Free-text search across body / template / provider_message_id
        if ($search = trim((string) $r->input('q'))) {
            $q->where(function ($x) use ($search) {
                $x->where('body', 'like', "%{$search}%")
                  ->orWhere('template', 'like', "%{$search}%")
                  ->orWhere('provider_message_id', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($from = $r->input('from')) { // YYYY-MM-DD
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $r->input('to')) { // YYYY-MM-DD
            $q->whereDate('created_at', '<=', $to);
        }

        $logs = $q->paginate(25)->appends($r->query());

        return view('admin.whatsapp.logs.index', [
            'logs' => $logs,
            'filters' => [
                'company_id' => $companyId,     // implicit
                'lead_id'    => $leadId ?? null,
                'direction'  => $dir   ?? null,
                'channel'    => $channel ?? null,
                'status'     => $status  ?? null,
                'phone'      => $phone   ?? null,
                'template'   => $tpl     ?? null,
                'q'          => $search  ?? null,
                'from'       => $from    ?? null,
                'to'         => $to      ?? null,
            ],
        ]);
    }

    public function show(MessageLog $log)
    {
        // Optional: ensure tenant scoping when accessing a single log
        $this->authorizeView($log);

        return view('admin.whatsapp.logs.show', ['log' => $log]);
    }

    public function exportCsv(Request $r)
    {
        $companyId = (int) auth()->user()->company_id;

        $q = MessageLog::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        // Same filters as index
        if ($leadId = $r->integer('lead_id')) $q->where('lead_id', $leadId);

        if ($dir = trim((string) $r->input('direction'))) {
            $dir = match (strtolower($dir)) {
                'outbound' => 'out',
                'inbound'  => 'in',
                default    => $dir,
            };
            $q->where('direction', $dir);
        }

        if ($channel = trim((string) $r->input('channel'))) $q->where('channel', $channel);
        if ($status  = trim((string) $r->input('status')))  $q->where('provider_status', $status);

        if ($phone = trim((string) $r->input('phone'))) {
            $q->where(function ($x) use ($phone) {
                $x->where('to_number', 'like', "%{$phone}%")
                  ->orWhere('from_number', 'like', "%{$phone}%");
            });
        }

        if ($tpl = trim((string) $r->input('template'))) $q->where('template', 'like', "%{$tpl}%");

        if ($search = trim((string) $r->input('q'))) {
            $q->where(function ($x) use ($search) {
                $x->where('body', 'like', "%{$search}%")
                  ->orWhere('template', 'like', "%{$search}%")
                  ->orWhere('provider_message_id', 'like', "%{$search}%");
            });
        }

        if ($from = $r->input('from')) $q->whereDate('created_at', '>=', $from);
        if ($to   = $r->input('to'))   $q->whereDate('created_at', '<=', $to);

        $rows = $q->limit(5000)->get([
            'id','company_id','lead_id','direction','channel',
            'to_number','from_number','template','body',
            'provider_message_id','provider_status','created_at',
        ]);

        $filename = 'message_logs_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID','Company','Lead','Direction','Channel',
                'To','From','Template','Body','ProviderID','Status','Created',
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->company_id, $r->lead_id, $r->direction, $r->channel,
                    $r->to_number, $r->from_number, $r->template, $r->body,
                    $r->provider_message_id, $r->provider_status, $r->created_at,
                ]);
            }
            fclose($out);
        };

        return Response::stream($callback, 200, $headers);
    }

    /** Ensure the current user can view the given log (tenant guard). */
    protected function authorizeView(MessageLog $log): void
    {
        if ((int) $log->company_id !== (int) auth()->user()->company_id) {
            abort(403);
        }
    }
}
