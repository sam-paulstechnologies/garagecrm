<?php

namespace App\Support\Tenancy;

use Illuminate\Support\Facades\Auth;

/**
 * Fable remediation (M7): explicit tenant context for the isolation backstop.
 *
 * Historically tenant scoping was implicit — "authenticated user's company_id,
 * otherwise everything". That fails open in console/queue/webhook contexts and
 * relies on company_id === null meaning "platform / see everything".
 *
 * This context lets callers declare intent explicitly:
 *   - forTenant($id, fn) — scope operations to one tenant (fail closed to it).
 *   - runAsPlatform(fn)  — an audited, explicit cross-tenant/platform bypass.
 *
 * When nothing is set explicitly it falls back to the authenticated tenant
 * user's company (backward compatible), so existing web behaviour is unchanged.
 * Bound as a singleton so the global scope and request share one instance.
 */
class TenantContext
{
    private ?int $tenantId = null;

    private bool $platform = false;

    private bool $explicit = false;

    public function setTenant(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
        $this->platform = false;
        $this->explicit = true;
    }

    public function usePlatform(bool $enabled = true): void
    {
        $this->platform = $enabled;
        $this->explicit = true;
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->platform = false;
        $this->explicit = false;
    }

    public function isPlatform(): bool
    {
        return $this->explicit && $this->platform;
    }

    /**
     * The tenant id to scope by, or null for no scoping (explicit platform
     * bypass, or an unauthenticated/platform context with no explicit tenant).
     */
    public function effectiveTenantId(): ?int
    {
        if ($this->explicit) {
            return $this->platform ? null : $this->tenantId;
        }

        $companyId = Auth::user()?->company_id;

        return $companyId ? (int) $companyId : null;
    }

    /**
     * @template T
     * @param  callable():T  $callback
     * @return T
     */
    public function runAsPlatform(callable $callback): mixed
    {
        $previous = [$this->tenantId, $this->platform, $this->explicit];
        $this->usePlatform(true);

        try {
            return $callback();
        } finally {
            [$this->tenantId, $this->platform, $this->explicit] = $previous;
        }
    }

    /**
     * @template T
     * @param  callable():T  $callback
     * @return T
     */
    public function forTenant(int $tenantId, callable $callback): mixed
    {
        $previous = [$this->tenantId, $this->platform, $this->explicit];
        $this->setTenant($tenantId);

        try {
            return $callback();
        } finally {
            [$this->tenantId, $this->platform, $this->explicit] = $previous;
        }
    }
}
