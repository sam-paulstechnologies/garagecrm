<?php

namespace App\Security;

use Illuminate\Http\Request;

class SecurityStepUp
{
    private const SESSION_KEY = 'security_step_up_at';

    public function mark(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, now()->getTimestamp());
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public function isRecent(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $verifiedAt = (int) $request->session()->get(self::SESSION_KEY, 0);
        $window = max(1, (int) config('security.step_up_window_minutes', 15)) * 60;

        return $verifiedAt > 0 && $verifiedAt >= now()->getTimestamp() - $window;
    }
}
