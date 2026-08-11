<?php

namespace App\Http\Controllers\Webhooks;

use App\Billing\BillingWebhookProcessor;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Billing\Exceptions\InvalidBillingWebhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, BillingWebhookProcessor $processor): JsonResponse
    {
        if ($provider === 'fake' && ! app()->environment(['local', 'testing', 'staging'])) {
            abort(404);
        }
        $signature = match ($provider) {
            'stripe' => (string) $request->header('Stripe-Signature'),
            'fake' => (string) $request->header('X-SayaraForce-Fake-Signature'),
            default => abort(404),
        };

        try {
            $result = $processor->process($provider, (string) $request->getContent(), $signature);

            return response()->json(['received' => true, ...$result]);
        } catch (InvalidBillingWebhook $exception) {
            $reason = $this->safeRejectionReason($exception);
            Log::warning('Rejected billing webhook.', ['provider' => $provider, 'reason' => $reason]);

            return response()->json(['received' => false, 'reason' => $reason], 400);
        } catch (BillingConfigurationException $exception) {
            Log::error('Billing webhook configuration or mapping failure.', ['provider' => $provider, 'reason' => class_basename($exception)]);

            return response()->json(['received' => false], 503);
        }
    }

    private function safeRejectionReason(InvalidBillingWebhook $exception): string
    {
        $message = $exception->getMessage();

        return match (true) {
            str_contains($message, 'timestamp is outside') => 'signature_timestamp',
            str_contains($message, 'signature is invalid') => 'signature_mismatch',
            str_contains($message, 'live-mode or unclassified') => 'live_mode_forbidden',
            str_contains($message, 'API version is incompatible') => 'api_version_incompatible',
            str_contains($message, 'JSON is invalid') => 'payload_json_invalid',
            str_contains($message, 'envelope is incomplete') => 'payload_envelope_incomplete',
            str_contains($message, 'must contain'),
            str_contains($message, 'missing required') => 'payload_contract_invalid',
            str_contains($message, 'invalid Price identifier') => 'price_identifier_invalid',
            str_contains($message, 'non-AED invoice') => 'currency_forbidden',
            default => 'webhook_invalid',
        };
    }
}
