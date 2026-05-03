<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleModel extends Model
{
    protected $fillable = [
        'make_id',
        'name',

        /* optional alias support */
        'alias'
    ];

    public $timestamps = false;

    protected $casts = [
        'alias' => 'array'
    ];

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    /**
     * Return all searchable names for resolver
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