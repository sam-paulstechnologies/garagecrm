<?php

namespace App\Notifications\Push;

use App\Models\Notifications\NotificationIntent;
use App\Models\Notifications\PushDevice;

final class FakePushProvider implements PushProvider
{
    public function deliver(PushDevice $device, NotificationIntent $intent): PushDeliveryResult
    {
        return new PushDeliveryResult(
            delivered: true,
            provider: 'fake',
            providerReference: 'fake-'.$intent->id.'-'.$device->id,
        );
    }
}
