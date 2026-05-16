<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleLeadWebhookController extends Controller
{
    public function __construct(
        private GoogleLeadService $googleLeadService
    ) {}

    /**
     * Google Ads Lead Form webhook receiver.
     *
     * POST /api/v1/webhooks/google/leads
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = $request->all();
        }

        Log::info('[GOOGLE_LEADS][WEBHOOK_HIT]', [
            'ip' => $request->ip(),
            'has_payload' => ! empty($payload),
            'lead_id' => $payload['lead_id'] ?? null,
            'form_id' => $payload['form_id'] ?? null,
            'campaign_id' => $payload['campaign_id'] ?? null,
        ]);

        $result = $this->googleLeadService->ingest($payload, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => [
                'content_type' => $request->header('Content-Type'),
                'user_agent' => $request->header('User-Agent'),
            ],
        ]);

        $httpStatus = (int) ($result['http_status'] ?? 200);

        return response()->json([
            'success' => (bool) ($result['ok'] ?? false),
            'status' => $result['status'] ?? 'unknown',
            'lead_id' => $result['lead_id'] ?? null,
            'message' => $result['message'] ?? null,
        ], $httpStatus);
    }
}