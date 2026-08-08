<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public garage registration
    |--------------------------------------------------------------------------
    |
    | Fail closed unless an environment explicitly enables self-registration.
    | Production therefore remains disabled when the setting is absent.
    |
    */
    'public_enabled' => (bool) env('PUBLIC_REGISTRATION_ENABLED', false),
];
