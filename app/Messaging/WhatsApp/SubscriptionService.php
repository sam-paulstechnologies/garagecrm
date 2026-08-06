<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Exceptions\MessagingProvisioningException;

class SubscriptionService
{
    public function __construct(private readonly MetaApiClient $meta)
    {
    }

    public function ensureSubscribed(string $wabaId, string $token, int $attempts = 3): array
    {
        $this->meta->subscribeWaba($wabaId, $token);

        $attempts = max(1, min($attempts, 4));
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $payload = $this->meta->getWabaSubscriptions($wabaId, $token);
            if ($this->meta->appIsSubscribed($payload)) {
                return ['subscribed' => true, 'attempts' => $attempt];
            }

            if ($attempt < $attempts && ! app()->runningUnitTests()) {
                usleep(250000 * $attempt);
            }
        }

        throw new MessagingProvisioningException(
            'subscription_readback_failed',
            'Meta accepted the connection but has not confirmed webhook delivery yet. Retry provisioning shortly.',
            ['attempts' => $attempts],
        );
    }

    public function isSubscribed(string $wabaId, string $token): bool
    {
        return $this->meta->appIsSubscribed($this->meta->getWabaSubscriptions($wabaId, $token));
    }
}
