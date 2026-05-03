<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = [
        'name',
        'category',
        'default_trt_days',
        'is_active'
    ];

    public function jobServices()
    {
        return $this->hasMany(JobService::class);
    }
}