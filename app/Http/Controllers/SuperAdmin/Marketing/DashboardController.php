<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingAppointment;
use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Models\PlatformMarketing\PlatformMarketingCampaignRecipient;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use App\Models\PlatformMarketing\PlatformMarketingConversation;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $metrics = [
            'total_prospects' => PlatformMarketingProspect::count(),
            'contactable' => PlatformMarketingProspect::where('consent_status', 'opted_in')->whereNotIn('status', ['opted_out', 'blocked', 'invalid'])->count(),
            'new' => PlatformMarketingProspect::where('status', 'new')->count(),
            'engaged' => PlatformMarketingProspect::whereIn('status', ['replied', 'engaged'])->count(),
            'qualified' => PlatformMarketingProspect::where('status', 'qualified')->count(),
            'demo_requests' => PlatformMarketingProspect::whereNotNull('demo_requested_at')->count(),
            'demo_bookings' => PlatformMarketingAppointment::where('status', 'confirmed')->count(),
            'won' => PlatformMarketingProspect::where('status', 'won')->count(),
            'lost' => PlatformMarketingProspect::where('status', 'lost')->count(),
            'opt_outs' => PlatformMarketingProspect::where('status', 'opted_out')->count(),
            'active_campaigns' => PlatformMarketingCampaign::whereIn('status', ['scheduled', 'running', 'paused'])->count(),
            'messages_queued' => PlatformMarketingCampaignRecipient::where('status', 'queued')->count(),
            'messages_sent' => PlatformMarketingCampaignRecipient::whereIn('status', ['sent', 'delivered', 'read', 'replied'])->count(),
            'delivered' => PlatformMarketingCampaignRecipient::whereNotNull('delivered_at')->count(),
            'read' => PlatformMarketingCampaignRecipient::whereNotNull('read_at')->count(),
            'failed' => PlatformMarketingCampaignRecipient::where('status', 'failed')->count(),
            'replies' => PlatformMarketingCampaignRecipient::whereNotNull('replied_at')->count(),
        ];

        $metrics['reply_rate'] = $metrics['messages_sent'] > 0
            ? round(($metrics['replies'] / $metrics['messages_sent']) * 100, 1)
            : 0;
        $metrics['demo_conversion_rate'] = $metrics['total_prospects'] > 0
            ? round(($metrics['demo_bookings'] / $metrics['total_prospects']) * 100, 1)
            : 0;

        return view('super_admin.marketing.dashboard', [
            'metrics' => $metrics,
            'channel' => PlatformMarketingChannel::latest('id')->first(),
            'recentConversations' => PlatformMarketingConversation::with('prospect')->latest('last_message_at')->limit(6)->get(),
            'upcomingAppointments' => PlatformMarketingAppointment::query()->where('starts_at', '>=', now())->orderBy('starts_at')->limit(6)->get(),
        ]);
    }
}
