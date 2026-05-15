<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Conversation;
use App\Models\Job\Job;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class GrowthController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $last30Days = Carbon::now()->subDays(30);

        $leadQuery = Lead::query()
            ->where('company_id', $companyId);

        $totalLeads = (clone $leadQuery)->count();

        $newLeadsThisMonth = (clone $leadQuery)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $qualifiedLeads = (clone $leadQuery)
            ->whereIn('status', ['Qualified', 'qualified', 'Assigned', 'assigned'])
            ->count();

        $disqualifiedLeads = (clone $leadQuery)
            ->whereIn('status', ['Disqualified', 'disqualified'])
            ->count();

        $hotLeads = 0;

        if (Schema::hasColumn('leads', 'is_hot')) {
            $hotLeads = (clone $leadQuery)
                ->where('is_hot', true)
                ->count();
        }

        $todayLeads = (clone $leadQuery)
            ->whereDate('created_at', $today)
            ->count();

        $conversationQuery = Conversation::query()
            ->where('company_id', $companyId);

        $totalConversations = (clone $conversationQuery)->count();

        $activeConversations = (clone $conversationQuery)
            ->where('last_message_at', '>=', $last30Days)
            ->count();

        $unreadConversations = (clone $conversationQuery)
            ->where('unread_count', '>', 0)
            ->count();

        $messageQuery = MessageLog::query()
            ->where('company_id', $companyId);

        $whatsappMessagesLast30Days = (clone $messageQuery)
            ->where('channel', 'whatsapp')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $inboundWhatsappLast30Days = (clone $messageQuery)
            ->where('channel', 'whatsapp')
            ->where('direction', 'in')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $outboundWhatsappLast30Days = (clone $messageQuery)
            ->where('channel', 'whatsapp')
            ->where('direction', 'out')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $humanRepliesLast30Days = (clone $messageQuery)
            ->where('channel', 'whatsapp')
            ->where('source', 'human')
            ->where('created_at', '>=', $last30Days)
            ->count();

        $jobQuery = Job::query()
            ->where('company_id', $companyId);

        $totalJobs = (clone $jobQuery)->count();

        $completedJobs = (clone $jobQuery)
            ->whereIn('status', ['completed', 'Completed', 'done', 'Done'])
            ->count();

        $growthCards = [
            [
                'label' => 'Total Leads',
                'value' => $totalLeads,
                'helper' => 'All captured leads',
                'tone' => 'blue',
            ],
            [
                'label' => 'New This Month',
                'value' => $newLeadsThisMonth,
                'helper' => 'Fresh lead volume',
                'tone' => 'orange',
            ],
            [
                'label' => 'Qualified / Assigned',
                'value' => $qualifiedLeads,
                'helper' => 'Leads ready for action',
                'tone' => 'green',
            ],
            [
                'label' => 'WhatsApp Messages',
                'value' => $whatsappMessagesLast30Days,
                'helper' => 'Last 30 days',
                'tone' => 'purple',
            ],
        ];

        $leadBreakdown = [
            'total' => $totalLeads,
            'today' => $todayLeads,
            'new_this_month' => $newLeadsThisMonth,
            'qualified' => $qualifiedLeads,
            'disqualified' => $disqualifiedLeads,
            'hot' => $hotLeads,
        ];

        $conversationBreakdown = [
            'total' => $totalConversations,
            'active_last_30_days' => $activeConversations,
            'unread' => $unreadConversations,
        ];

        $whatsappBreakdown = [
            'total_last_30_days' => $whatsappMessagesLast30Days,
            'inbound_last_30_days' => $inboundWhatsappLast30Days,
            'outbound_last_30_days' => $outboundWhatsappLast30Days,
            'human_replies_last_30_days' => $humanRepliesLast30Days,
        ];

        $jobBreakdown = [
            'total' => $totalJobs,
            'completed' => $completedJobs,
        ];

        return view('manager.growth.index', [
            'growthCards' => $growthCards,
            'leadBreakdown' => $leadBreakdown,
            'conversationBreakdown' => $conversationBreakdown,
            'whatsappBreakdown' => $whatsappBreakdown,
            'jobBreakdown' => $jobBreakdown,
        ]);
    }
}