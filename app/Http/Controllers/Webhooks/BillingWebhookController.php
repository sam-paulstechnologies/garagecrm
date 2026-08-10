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
            Log::warning('Rejected billing webhook.', ['provider' => $provider, 'reason' => class_basename($exception)]);

            return response()->json(['received' => false], 400);
        } catch (BillingConfigurationException $exception) {
            Log::error('Billing webhook configuration or mapping failure.', ['provider' => $provider, 'reason' => class_basename($exception)]);

            return response()->json(['received' => false], 503);
        }
    }
}
