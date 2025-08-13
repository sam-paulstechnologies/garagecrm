<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the trait to apply a global scope and auto-fill company_id.
     */
    protected static function bootBelongsToCompany()
    {
        // Apply global scope to restrict data to the authenticated user's company
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check() && Auth::user()->company_id) {
                $builder->where('company_id', Auth::user()->company_id);
            }
        });

        // Auto-assign company_id when creating new records
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->company_id && empty($model->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    /**
     * Optional relationship back to the company.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }
}
