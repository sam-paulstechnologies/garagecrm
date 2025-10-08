<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('company_id')) {
    function company_id(): ?int
    {
        return optional(Auth::user())->company_id;
    }
}

if (! function_exists('user_id')) {
    function user_id(): ?int
    {
        return optional(Auth::user())->id;
    }
}
