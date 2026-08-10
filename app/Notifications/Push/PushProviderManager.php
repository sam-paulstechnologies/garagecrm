<?php

namespace App\Notifications\Push;

use RuntimeException;

final class PushProviderManager
{
    public function driver(): PushProvider
    {
        $driver = (string) config('mobile_notifications.driver', 'fake');

        if ($driver === 'fake') {
            return app(FakePushProvider::class);
        }

        if (! config('mobile_notifications.external_delivery_enabled')) {
            throw new RuntimeException('External push delivery is disabled.');
        }

        throw new RuntimeException("Push driver [{$driver}] requires external provider configuration.");
    }
}
