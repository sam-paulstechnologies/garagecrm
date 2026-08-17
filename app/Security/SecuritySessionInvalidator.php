<?php

namespace App\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Fable remediation (M5): canonical session + token invalidation for credential
 * and security changes.
 *
 * The app uses the database session driver without the AuthenticateSession
 * middleware, so Auth::logoutOtherDevices() would not reliably terminate other
 * live sessions. This service deletes the persisted session rows directly and
 * revokes Sanctum tokens, giving one place that every credential/security change
 * routes through instead of scattered ad-hoc operations.
 */
class SecuritySessionInvalidator
{
    /**
     * Delete persisted sessions for the user, optionally keeping one (the caller's
     * current session). Returns the number of session rows removed.
     */
    public function invalidateOtherSessions(User $user, ?string $keepSessionId = null): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        $table = (string) config('session.table', 'sessions');
        $query = DB::table($table)->where('user_id', $user->getAuthIdentifier());

        if ($keepSessionId !== null) {
            $query->where('id', '!=', $keepSessionId);
        }

        return $query->delete();
    }

    public function invalidateAllSessions(User $user): int
    {
        return $this->invalidateOtherSessions($user, null);
    }

    public function revokeApiTokens(User $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }

    /**
     * Password reset: the actor is not authenticated in this request, so every
     * existing session and API token is terminated. No implicit privileged
     * session is established by the reset flow.
     */
    public function afterPasswordReset(User $user): void
    {
        $this->invalidateAllSessions($user);
        $this->revokeApiTokens($user);
    }

    /**
     * Password change: keep the actor's current session (rotated), terminate all
     * other sessions and revoke API tokens.
     */
    public function afterPasswordChange(User $user, Request $request): void
    {
        $keep = $request->hasSession() ? $request->session()->getId() : null;
        $this->invalidateOtherSessions($user, $keep);
        $this->revokeApiTokens($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }

    /**
     * Self-service 2FA disable: keep the current (step-up verified) session,
     * terminate the user's other sessions and revoke API tokens.
     */
    public function afterSelfTwoFactorDisable(User $user, Request $request): void
    {
        $keep = $request->hasSession() ? $request->session()->getId() : null;
        $this->invalidateOtherSessions($user, $keep);
        $this->revokeApiTokens($user);
    }

    /**
     * Administrative 2FA reset: terminate ALL of the target user's sessions
     * (they are not the actor) in addition to the token revocation the caller
     * already performs.
     */
    public function afterAdministrativeReset(User $target): void
    {
        $this->invalidateAllSessions($target);
        $this->revokeApiTokens($target);
    }
}
