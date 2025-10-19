<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppMessage;
use Carbon\Carbon;

class PerformanceController extends Controller
{
    public function index() {
        $from7 = Carbon::now()->subDays(7);
        $from30 = Carbon::now()->subDays(30);

        $stats = [
            'sent_7d'      => WhatsAppMessage::where('status','sent')->where('created_at','>=',$from7)->count(),
            'delivered_7d' => WhatsAppMessage::where('status','delivered')->where('created_at','>=',$from7)->count(),
            'failed_7d'    => WhatsAppMessage::where('status','failed')->where('created_at','>=',$from7)->count(),
            'sent_30d'     => WhatsAppMessage::where('status','sent')->where('created_at','>=',$from30)->count(),
            'delivered_30d'=> WhatsAppMessage::where('status','delivered')->where('created_at','>=',$from30)->count(),
            'failed_30d'   => WhatsAppMessage::where('status','failed')->where('created_at','>=',$from30)->count(),
            'top_templates'=> WhatsAppMessage::selectRaw('template, count(*) c')->groupBy('template')->orderByDesc('c')->limit(10)->get(),
        ];

        return view('whatsapp.performance.index', compact('stats'));
    }
}
