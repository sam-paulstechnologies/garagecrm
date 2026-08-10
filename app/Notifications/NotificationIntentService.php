<?php

namespace App\Notifications;

use App\Jobs\DeliverNotificationIntent;
use App\Models\Notifications\NotificationIntent;
use App\Models\System\Company;
use App\Models\User;
use InvalidArgumentException;

final class NotificationIntentService
{
    public function record(
        Company $company,
        User $user,
        string $type,
        string $title,
        string $body,
        string $idempotencyKey,
        ?string $actionUrl = null,
        array $payload = [],
        bool $dispatch = true,
    ): NotificationIntent {
        if (! NotificationTypes::isKnown($type)) {
            throw new InvalidArgumentException('Unknown notification intent type.');
        }
        if ((int) $user->company_id !== (int) $company->id) {
            throw new InvalidArgumentException('Notification recipient is outside the tenant.');
        }
        foreach (array_keys($payload) as $key) {
            if (preg_match('/phone|email|token|secret|password/i', (string) $key)) {
                throw new InvalidArgumentException('Sensitive notification payload keys are not allowed.');
            }
        }

        $intent = NotificationIntent::query()->firstOrCreate(
            ['company_id' => $company->id, 'idempotency_key' => $idempotencyKey],
            [
                'user_id' => $user->id,
                'type' => $type,
                'channel' => 'push',
                'state' => 'pending',
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
                'payload' => $payload ?: null,
                'available_at' => now(),
            ],
        );

        if ($dispatch && $intent->wasRecentlyCreated) {
            DeliverNotificationIntent::dispatch($intent->id)->afterCommit();
        }

        return $intent;
    }
}
