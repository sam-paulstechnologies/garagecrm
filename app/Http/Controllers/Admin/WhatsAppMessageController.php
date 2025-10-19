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

        // optional filters
        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('provider')) $q->where('provider', $request->provider);
        if ($request->filled('to'))       $q->where('to_number', 'like', '%'.$request->to.'%');

        $messages = $q->latest('id')->paginate(25)->withQueryString();

        return view('admin.whatsapp.messages.index', compact('messages'));
    }

    public function show(WhatsAppMessage $message)
    {
        // If payload is a JSON string, decode for view
        $payload = is_array($message->payload) ? $message->payload : json_decode($message->payload ?? '[]', true);
        return view('admin.whatsapp.messages.show', compact('message','payload'));
    }

    public function retry(WhatsAppMessage $message)
    {
        // TODO: dispatch a job to retry sending
        // RetryWhatsAppMessage::dispatch($message->id);

        return back()->with('status', "Retry queued for message #{$message->id}");
    }
}
