<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Commercial\LaunchOfferService;
use App\Commercial\Plans;
use App\Commercial\ProductEvents;
use App\Models\Commercial\Price;
use App\Models\Commercial\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommercialMetricsController extends SuperAdminController
{
    public function index(LaunchOfferService $launchOffer)
    {
        $planCounts = array_fill_keys(Plans::codes(), 0);
        DB::table('subscriptions as s')
            ->join('plan_versions as pv', 'pv.id', '=', 's.plan_version_id')
            ->join('plans as p', 'p.id', '=', 'pv.plan_id')
            ->select('p.code', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('p.code', Plans::codes())
            ->whereIn('s.status', Subscription::ENTITLED_STATES)
            ->groupBy('p.code')
            ->get()
            ->each(function (object $row) use (&$planCounts): void {
                $planCounts[(string) $row->code] = (int) $row->aggregate;
            });

        $eventCount = fn (string $event): int => (int) DB::table('product_events')
            ->where('event_type', $event)->count();
        $conversionCount = fn (string $from, string $to): int => (int) DB::table('product_events')
            ->where('event_type', ProductEvents::PLAN_UPGRADED)
            ->where('properties->from_plan', $from)
            ->where('properties->to_plan', $to)
            ->count();

        $metrics = [
            'free_to_service' => $conversionCount(Plans::FREE, Plans::SERVICE),
            'service_to_growth' => $conversionCount(Plans::SERVICE, Plans::GROWTH),
            'cancelled' => $eventCount(ProductEvents::SUBSCRIPTION_CANCELLED),
            'failed_payments' => (int) DB::table('billing_invoices')->where('status', 'payment_failed')->count(),
            'checkout_abandonment' => (int) DB::table('billing_checkout_sessions')
                ->whereIn('status', ['creating', 'open', 'pending_provider', 'failed'])->count(),
            'ai_analysis_runs' => (int) DB::table('ai_analysis_runs')->count(),
            'ai_input_tokens' => (int) DB::table('ai_analysis_runs')->sum('input_tokens'),
            'ai_output_tokens' => (int) DB::table('ai_analysis_runs')->sum('output_tokens'),
            'ai_estimated_cost' => round(
                ((float) DB::table('ai_analysis_runs')->sum('estimated_cost_micros')) / 1_000_000,
                6,
            ),
            'whatsapp_provider_events' => Schema::hasTable('whatsapp_usage_logs')
                ? (int) DB::table('whatsapp_usage_logs')->count()
                : 0,
            'whatsapp_recorded_cost' => Schema::hasTable('whatsapp_usage_logs')
                ? (float) DB::table('whatsapp_usage_logs')->sum('meta_cost')
                : 0.0,
            'average_monitored_customers' => round((float) DB::table('entitlement_usages')
                ->where('capability', 'limit.ai_monitored_customers')->avg('used'), 2),
            'limit_pressure' => (int) DB::table('entitlement_usages as eu')
                ->join('subscriptions as s', 's.company_id', '=', 'eu.company_id')
                ->join('plan_entitlements as pe', function ($join): void {
                    $join->on('pe.plan_version_id', '=', 's.plan_version_id')
                        ->on('pe.capability', '=', 'eu.capability');
                })
                ->whereNotNull('pe.allowance')
                ->whereColumn('eu.used', '>=', 'pe.allowance')
                ->count(),
            'paid_revenue_aed' => (float) DB::table('billing_invoices')
                ->where('status', 'paid')->where('currency', 'AED')->sum('amount_paid'),
        ];

        $launchOfferEnabled = $launchOffer->isEnabled();
        $promotionCycles = $launchOffer->durationCycles();
        $commercialPrices = Price::query()
            ->where('status', 'active')
            ->where('currency', config('commercial.pricing.currency'))
            ->where('interval', config('commercial.pricing.interval'))
            ->with('planVersion.plan')
            ->get()
            ->filter(fn (Price $price): bool => in_array((string) $price->planVersion?->plan?->code, Plans::codes(), true))
            ->sortBy(fn (Price $price): int => (int) $price->planVersion->plan->rank);

        return view('super_admin.commercial.index', compact(
            'planCounts', 'metrics', 'launchOfferEnabled', 'promotionCycles', 'commercialPrices'
        ));
    }

    public function updateLaunchOffer(Request $request, LaunchOfferService $launchOffer): RedirectResponse
    {
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);
        $launchOffer->setEnabled($request->user(), (bool) $validated['enabled']);

        return back()->with('success', 'Launch Offer eligibility was updated for future subscriptions only.');
    }
}
