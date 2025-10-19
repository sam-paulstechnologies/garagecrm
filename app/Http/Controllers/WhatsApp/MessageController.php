<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $r) {
        $q = WhatsAppMessage::query()->orderByDesc('id');
        if ($r->filled('status'))   $q->where('status', $r->string('status'));
        if ($r->filled('to'))       $q->where('to_number', 'like', '%'.$r->string('to').'%');
        if ($r->filled('template')) $q->where('template', $r->string('template'));
        $messages = $q->paginate(25);
        return view('whatsapp.messages.index', compact('messages'));
    }

    public function show($id) {
        $msg = WhatsAppMessage::findOrFail($id);
        return view('whatsapp.messages.show', compact('msg'));
    }

    public function retry($id) {
        $msg = WhatsAppMessage::findOrFail($id);
        // Re-send using the same payload
        dispatch(function() use ($msg) {
            (new SendWhatsAppMessage())->sendRaw(
                $msg->to_number, $msg->from_number, $msg->payload ?? [], $msg->template
            );
        });
        return back()->with('ok','Retry queued.');
    }
}
