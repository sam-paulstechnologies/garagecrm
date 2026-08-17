<?php

namespace App\Models\Contracts;

/**
 * Fable remediation (M7): marker for models that belong to exactly one tenant
 * (company) and must be scoped by the tenant backstop. Applied alongside the
 * BelongsToCompany trait, it documents tenant ownership explicitly so isolation
 * no longer rests solely on per-controller manual checks.
 */
interface TenantOwned
{
}
