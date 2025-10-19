<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppPerformanceController extends Controller
{
    public function index()
    {
        // KPI tiles
        $totals = [
            'sent'      => DB::table('whatsapp_messages')->where('status', 'sent')->count(),
            'delivered' => DB::table('whatsapp_messages')->where('status', 'delivered')->count(),
            'failed'    => DB::table('whatsapp_messages')->where('status', 'failed')->count(),
            'queued'    => DB::table('whatsapp_messages')->where('status', 'queued')->count(),
        ];

        // Breakdown by provider
        $byProvider = DB::table('whatsapp_messages')
            ->select('provider', DB::raw('COUNT(*) as c'))
            ->groupBy('provider')
            ->orderByDesc('c')
            ->get();

        // Top templates (only if column exists)
        $topTemplates = collect();
        if (Schema::hasColumn('whatsapp_messages', 'template')) {
            $topTemplates = DB::table('whatsapp_messages')
                ->select(DB::raw("COALESCE(NULLIF(template,''), '(none)') as template"), DB::raw('COUNT(*) as c'))
                ->groupBy('template')
                ->orderByDesc('c')
                ->limit(10)
                ->get();
        }

        return view('admin.whatsapp.performance.index', compact('totals', 'byProvider', 'topTemplates'));
    }
}
