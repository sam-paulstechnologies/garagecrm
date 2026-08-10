<?php

namespace App\Notifications\Push;

use App\Models\Notifications\NotificationIntent;
use App\Models\Notifications\PushDevice;

interface PushProvider
{
    public function deliver(PushDevice $device, NotificationIntent $intent): PushDeliveryResult;
}
