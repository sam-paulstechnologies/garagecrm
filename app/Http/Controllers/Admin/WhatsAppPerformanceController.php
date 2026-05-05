<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class WhatsAppPerformanceController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->company->id ?? null;

        /**
         * ============================
         * Date ranges
         * ============================
         */
        $now   = Carbon::now();
        $d7    = $now->copy()->subDays(7);
        $d30   = $now->copy()->subDays(30);

        /**
         * ============================
         * Stats (what Blade expects)
         * ============================
         */
        $stats = [
            'sent_7d' => DB::table('whatsapp_messages')
                ->where('company_id', $companyId)
                ->where('status', 'sent')
                ->where('created_at', '>=', $d7)
                ->count(),

            'delivered_7d' => DB::table('whatsapp_messages')
                ->where('company_id', $companyId)
                ->where('status', 'delivered')
                ->where('created_at', '>=', $d7)
                ->count(),

            'failed_7d' => DB::table('whatsapp_messages')
                ->where('company_id', $companyId)
                ->where('status', 'failed')
                ->where('created_at', '>=', $d7)
                ->count(),

            'sent_30d' => DB::table('whatsapp_messages')
                ->where('company_id', $companyId)
                ->where('status', 'sent')
                ->where('created_at', '>=', $d30)
                ->count(),

            'top_templates' => collect(),
        ];

        /**
         * ============================
         * Top Templates (guarded)
         * ============================
         */
        if (Schema::hasColumn('whatsapp_messages', 'template')) {
            $stats['top_templates'] = DB::table('whatsapp_messages')
                ->where('company_id', $companyId)
                ->select(
                    DB::raw("COALESCE(NULLIF(template,''), '(none)') as template"),
                    DB::raw('COUNT(*) as c')
                )
                ->groupBy('template')
                ->orderByDesc('c')
                ->limit(10)
                ->get();
        }

        /**
         * ============================
         * Return view
         * ============================
         */
        return view('admin.whatsapp.performance.index', compact('stats'));
    }
}