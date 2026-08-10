<?php

namespace App\Http\Controllers\Admin;

use App\Commercial\EntitlementService;
use App\Http\Controllers\Controller;
use App\Models\System\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ServiceDashboardController extends Controller
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function index(Request $request): View
    {
        return $this->render($request, true);
    }

    public function free(Request $request): View
    {
        return $this->render($request, false);
    }

    private function render(Request $request, bool $serviceMode): View
    {
        $company = $this->company($request);
        $companyId = (int) $company->id;
        $periodStart = $company->subscription()->value('current_period_start') ?? now()->startOfMonth();
        $aiLimit = $this->entitlements->limit($company, 'limit.ai_monitored_customers') ?? 0;
        $aiRemaining = $this->entitlements->remaining($company, 'limit.ai_monitored_customers') ?? 0;

        $schemaReady = Schema::hasTable('leads')
            && Schema::hasTable('bookings')
            && Schema::hasTable('message_logs');

        if (! $schemaReady) {
            return view('admin.dashboard.service', [
                'metrics' => $this->emptyMetrics($aiLimit, $aiRemaining),
                'serviceMode' => $serviceMode,
                'planCode' => (string) ($company->subscription()->with('planVersion.plan')->first()?->planVersion?->plan?->code ?? 'free'),
            ]);
        }

        $leads = DB::table('leads')->where('company_id', $companyId);
        $bookings = DB::table('bookings')->where('company_id', $companyId)->whereNull('deleted_at');
        $inboundConversations = DB::table('message_logs')
            ->where('company_id', $companyId)
            ->where('direction', 'in')
            ->whereNotNull('conversation_id')
            ->where('created_at', '>=', $periodStart)
            ->distinct()
            ->count('conversation_id');
        $repliedConversations = DB::table('message_logs')
            ->where('company_id', $companyId)
            ->where('direction', 'out')
            ->whereNotNull('conversation_id')
            ->where('created_at', '>=', $periodStart)
            ->distinct()
            ->count('conversation_id');
        $periodLeads = (clone $leads)->where('created_at', '>=', $periodStart)->count();
        $periodBookings = (clone $bookings)->where('created_at', '>=', $periodStart)->count();

        $metrics = [
            'new_enquiries' => (clone $leads)->where('status', 'new')->count(),
            'needs_follow_up' => (clone $leads)->where(function ($query): void {
                $query->where('follow_up_required', true)
                    ->orWhereNotNull('follow_up_at')
                    ->orWhereNotNull('follow_up_date');
            })->count(),
            'qualified' => (clone $leads)->whereIn('status', ['qualified', 'converted'])->count(),
            'upcoming_bookings' => (clone $bookings)
                ->whereBetween('booking_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->whereNotIn('status', ['lost'])->count(),
            'missed_follow_ups' => (clone $leads)->where(function ($query): void {
                $query->where('follow_up_at', '<', now())
                    ->orWhere('follow_up_date', '<', now()->toDateString());
            })->whereNotIn('status', ['converted', 'lost', 'disqualified'])->count(),
            'due_for_service' => (clone $leads)->where('retention_tag', 'service_due')->where('is_active', true)->count(),
            'response_rate' => $inboundConversations > 0
                ? round(min(100, ($repliedConversations / $inboundConversations) * 100), 1)
                : 0.0,
            'booking_conversion' => $periodLeads > 0
                ? round(min(100, ($periodBookings / $periodLeads) * 100), 1)
                : 0.0,
            'ai_limit' => $aiLimit,
            'ai_used' => max(0, $aiLimit - $aiRemaining),
            'ai_remaining' => $aiRemaining,
        ];

        return view('admin.dashboard.service', [
            'metrics' => $metrics,
            'serviceMode' => $serviceMode,
            'planCode' => (string) ($company->subscription()->with('planVersion.plan')->first()?->planVersion?->plan?->code ?? 'free'),
        ]);
    }

    private function emptyMetrics(int $aiLimit, int $aiRemaining): array
    {
        return [
            'new_enquiries' => 0,
            'needs_follow_up' => 0,
            'qualified' => 0,
            'upcoming_bookings' => 0,
            'missed_follow_ups' => 0,
            'due_for_service' => 0,
            'response_rate' => 0.0,
            'booking_conversion' => 0.0,
            'ai_limit' => $aiLimit,
            'ai_used' => max(0, $aiLimit - $aiRemaining),
            'ai_remaining' => $aiRemaining,
        ];
    }

    private function company(Request $request): Company
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);
        abort_if($companyId < 1, 403);

        return Company::query()->findOrFail($companyId);
    }
}
