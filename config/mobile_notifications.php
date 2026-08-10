<?php

return [
    'driver' => env('PUSH_NOTIFICATION_DRIVER', 'fake'),
    'external_delivery_enabled' => filter_var(env('PUSH_EXTERNAL_DELIVERY_ENABLED', false), FILTER_VALIDATE_BOOL),
    'allowed_drivers' => ['fake', 'fcm', 'apns', 'web_push'],
];
