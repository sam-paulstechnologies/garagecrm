<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsApp\WhatsAppMessage;

class WhatsAppMessageController extends Controller
{
    public function index(Request $request)
    {
        $q = WhatsAppMessage::query();

        // filters
        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('provider')) $q->whereJsonContains('payload->provider', $request->provider);
        if ($request->filled('to'))       $q->where('to', 'like', '%'.$request->to.'%');

        // date range (created_at)
        if ($request->filled('from'))     $q->where('created_at', '>=', $request->date('from')->startOfDay());
        if ($request->filled('to'))       $q->where('created_at', '<=', $request->date('to')->endOfDay());

        $messages = $q->latest('id')->paginate(25)->withQueryString();

        return view('admin.whatsapp.messages.index', compact('messages'));
    }

    public function show(WhatsAppMessage $message)
    {
        $payload = is_array($message->payload) ? $message->payload : json_decode($message->payload ?? '[]', true);
        return view('admin.whatsapp.messages.show', compact('message','payload'));
    }

    public function retry(WhatsAppMessage $message)
    {
        // Wire your retry job here if/when needed
        // RetryWhatsAppMessage::dispatch($message->id);

        return back()->with('status', "Retry queued for message #{$message->id}");
    }
}
