<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleMake extends Model
{
    protected $fillable = [
        'name',

        /* optional alias support */
        'alias'
    ];

    public $timestamps = false;

    protected $casts = [
        'alias' => 'array'
    ];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'make_id');
    }

    /**
     * Return searchable names for resolver
     */
    public function getSearchNames(): array
    {
        $names = [$this->name];

        if (!empty($this->alias)) {
            $names = array_merge($names, $this->alias);
        }

        return array_unique(array_map('strtolower', $names));
    }
}