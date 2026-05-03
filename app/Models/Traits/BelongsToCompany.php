<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        // Apply scope ONLY when authenticated
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::check() && Auth::user()?->company_id) {
                $builder->where('company_id', Auth::user()->company_id);
            }
        });

        // Auto-fill ONLY when authenticated
        static::creating(function ($model) {
            if (
                empty($model->company_id) &&
                Auth::check() &&
                Auth::user()?->company_id
            ) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\System\Company::class);
    }
}
