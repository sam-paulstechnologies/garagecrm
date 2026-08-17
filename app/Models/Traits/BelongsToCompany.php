<?php

namespace App\Models\Traits;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        // M7 backstop: scope by the effective tenant context. This is backward
        // compatible — with no explicit context it resolves to the authenticated
        // tenant user's company (as before) — while also honouring an explicit
        // TenantContext::forTenant()/runAsPlatform() declared by jobs/services.
        static::addGlobalScope('company', function (Builder $builder) {
            $tenantId = app(TenantContext::class)->effectiveTenantId();
            if ($tenantId !== null) {
                // Bare column matches the original proven behaviour (resolves via
                // the model's own table / active join context); qualifying it
                // regressed models queried through joins.
                $builder->where('company_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $tenantId = app(TenantContext::class)->effectiveTenantId();
                if ($tenantId !== null) {
                    $model->company_id = $tenantId;
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }
}
