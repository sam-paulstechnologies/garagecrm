<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\PlatformMarketing\ProcessPlatformInboundMessage;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use App\Models\PlatformMarketing\PlatformMarketingConversationMessage;
use App\Services\PlatformMarketing\InboundContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookRouterController extends Controller
{
    public function __construct(
        private MetaWhatsAppWebhookController $garageController,
        private InboundContextResolver $resolver
    ) {
    }

    public function verify(Request $request)
    {
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($token) {
            $platformTokenMatched = PlatformMarketingChannel::query()
                ->get()
                ->contains(fn (PlatformMarketingChannel $channel) => hash_equals((string) $channel->decrypted_verify_token, (string) $token));

            if ($platformTokenMatched) {
                return response($challenge, 200);
            }
        }

        return $this->garageController->verify($request);
    }

    public function handle(Request $request)
    {
        $value = $request->input('entry.0.changes.0.value');

        if (! is_array($value)) {
            return $this->garageController->handle($request);
        }

        if (! empty($value['statuses'])) {
            $this->handlePlatformStatuses($value);
        }

        if (empty($value['messages'][0])) {
            return $this->garageController->handle($request);
        }

        $context = $this->resolver->resolve($value);

        if (! $context) {
            return $this->garageController->handle($request);
        }

        $signatureResponse = $this->validateSignature($request);

        if ($signatureResponse) {
            return $signatureResponse;
        }

        ProcessPlatformInboundMessage::dispatch($request->all(), [
            'channel_id' => $context['channel']->id,
            'prospect_id' => $context['prospect']->id,
            'recipient_id' => $context['recipient']?->id,
            'conversation_id' => $context['conversation']?->id,
        ]);

        Log::info('[PlatformMarketing] Meta inbound routed to platform module', [
            'channel_id' => $context['channel']->id,
            'prospect_id' => $context['prospect']->id,
        ]);

        return response()->noContent();
    }

    private function handlePlatformStatuses(array $value): void
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if (! $phoneNumberId) {
            return;
        }

        $channel = PlatformMarketingChannel::query()
            ->where('phone_number_id', $phoneNumberId)
            ->first();

        if (! $channel) {
            return;
        }

        foreach ($value['statuses'] as $status) {
            $messageId = $status['id'] ?? null;

            if (! $messageId) {
                continue;
            }

            PlatformMarketingConversationMessage::query()
                ->where('provider_message_id', $messageId)
                ->update([
                    'provider_status' => $status['status'] ?? null,
                    'meta' => ['provider' => 'meta', 'status_payload' => $status],
                    'updated_at' => now(),
                ]);
        }
    }

    private function validateSignature(Request $request)
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return response('Missing signature', 403);
        }

        $appSecret = config('services.meta_leads.app_secret')
            ?: config('services.meta.app_secret')
            ?: env('META_APP_SECRET');

        if (! $appSecret) {
            return response('Server misconfigured', 500);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! hash_equals($expected, $signature)) {
            return response('Invalid signature', 403);
        }

        return null;
    }
}
