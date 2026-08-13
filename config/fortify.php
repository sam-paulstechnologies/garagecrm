<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'auth_middleware' => 'auth',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'views' => false,
    'home' => '/dashboard',
    'prefix' => '_fortify-disabled',
    'domain' => null,
    'lowercase_usernames' => true,
    'limiters' => ['login' => null],
    'features' => [
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            'window' => 1,
            'secret-length' => 32,
        ]),
    ],
];
