<?php

return [
    // Production rollout begins at "off", then "audit", then "required_admins".
    'two_factor_enforcement' => env('TWO_FACTOR_ENFORCEMENT', 'off'),
    'mandatory_roles' => [
        'platform' => ['super_admin', 'platform_admin'],
        'tenant' => ['admin'],
    ],
    'step_up_window_minutes' => (int) env('SECURITY_STEP_UP_WINDOW_MINUTES', 15),
    'challenge_attempts_per_minute' => (int) env('TWO_FACTOR_CHALLENGE_ATTEMPTS_PER_MINUTE', 5),
];
