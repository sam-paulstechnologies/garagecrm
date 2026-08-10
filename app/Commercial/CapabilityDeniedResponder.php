<?php

namespace App\Commercial;

use App\Models\System\Company;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CapabilityDeniedResponder
{
    public function __construct(
        private readonly UpsellPresentationService $upsells,
        private readonly ProductEventRecorder $productEvents,
    ) {}

    public function respond(Request $request, Company $company, string $capability): Response
    {
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $upsell = $this->upsells->for($company, $capability);
            $this->productEvents->recordSafely(
                ProductEvents::UPGRADE_VIEWED,
                $company,
                $request->user(),
                [
                    'current_plan' => (string) $upsell['current_plan'],
                    'target_plan' => (string) $upsell['next_plan'],
                    'capability' => $capability,
                ],
                implode(':', ['upgrade-viewed', $company->id, $capability, now()->toDateString()]),
            );

            return response()->view('commercial.locked', [
                'upsell' => $upsell,
            ], 403);
        }

        abort(403, 'Capability not included in this subscription.');
    }
}
