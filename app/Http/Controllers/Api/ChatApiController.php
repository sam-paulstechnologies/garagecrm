<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateAiSuggestion;
use App\Models\Conversation;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class ChatApiController extends Controller
{
    public function conversations(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        $items = Conversation::where('company_id',$companyId)
            ->orderByDesc('latest_message_at')->paginate(20);
        return response()->json($items);
    }

    public function thread(Request $request, int $conversationId)
    {
        $conv = Conversation::findOrFail($conversationId);
        return response()->json([
            'conversation' => $conv,
            'messages'     => $conv->messages()->get(),
        ]);
    }

    public function send(Request $request, int $conversationId)
    {
        $request->validate(['text'=>'required|string|max:1000']);
        $companyId = (int) optional($request->user())->company_id ?: 1;

        $conv = Conversation::findOrFail($conversationId);

        $msg = MessageLog::create([
            'company_id'   => $companyId,
            'conversation_id' => $conv->id,
            'direction'    => 'out',
            'channel'      => 'in_app',
            'source'       => 'human',
            'body'         => $request->input('text'),
            'to_number'    => null,
            'from_number'  => null,
        ]);

        $conv->latest_message_at = now();
        $conv->save();

        event(new MessageCreated($msg));
        return response()->json(['ok'=>true,'message'=>$msg]);
    }

    /** Optional: trigger AI suggest for the latest inbound in this thread */
    public function suggest(int $conversationId)
    {
        $inbound = MessageLog::where('conversation_id',$conversationId)
            ->where('direction','in')
            ->latest('id')->first();

        if (!$inbound) return response()->json(['ok'=>false,'reason'=>'no_inbound'], 404);

        dispatch(new GenerateAiSuggestion($inbound->id));
        return response()->json(['ok'=>true]);
    }
}
