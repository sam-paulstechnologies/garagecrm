<?php

namespace App\Commercial;

use App\Models\System\Company;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CapabilityDeniedResponder
{
    public function __construct(private readonly UpsellPresentationService $upsells) {}

    public function respond(Request $request, Company $company, string $capability): Response
    {
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            return response()->view('commercial.locked', [
                'upsell' => $this->upsells->for($company, $capability),
            ], 403);
        }

        abort(403, 'Capability not included in this subscription.');
    }
}
