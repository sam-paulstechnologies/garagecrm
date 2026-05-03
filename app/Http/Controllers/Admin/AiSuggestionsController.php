<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Services\AI\AiOutboundSender;
use App\Services\AI\AiMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiSuggestionsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = company_id();

        $suggestions = DB::table('ai_suggestions as s')
            ->join('message_logs as m', 'm.id', '=', 's.message_log_id')
            ->where('s.company_id', $companyId)
            ->where('s.status', 'pending')
            ->select([
                's.id',
                's.message_log_id',
                's.suggestion_text',
                's.confidence',
                's.status',
                's.created_at',
                'm.to_number',
                'm.provider_status',
                'm.conversation_id',
            ])
            ->orderByDesc('s.id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ai.suggestions', compact('suggestions'));
    }

    public function approve(Request $request, int $id)
    {
        $companyId = company_id();

        $row = DB::table('ai_suggestions')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->first();

        abort_if(!$row, 404);

        DB::transaction(function () use ($row, $companyId) {

            // Mark approved
            DB::table('ai_suggestions')
                ->where('id', $row->id)
                ->update([
                    'status'      => 'approved',
                    'approved_at'=> now(),
                    'approved_by'=> auth()->id(),
                    'chosen'      => 1,
                    'updated_at' => now(),
                ]);

            // Send outbound message
            $inbound = MessageLog::findOrFail($row->message_log_id);
            AiOutboundSender::sendFromInbound(
                $inbound,
                (string) $row->suggestion_text
            );

            // Metrics
            AiMetricsService::bumpAiOut(
                $companyId,
                $row->confidence !== null ? (float) $row->confidence : null
            );
        });

        return back()->with('success', 'AI suggestion approved and sent.');
    }

    public function reject(Request $request, int $id)
    {
        $companyId = company_id();

        $updated = DB::table('ai_suggestions')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);

        abort_if(!$updated, 404);

        return back()->with('success', 'AI suggestion rejected.');
    }
}
