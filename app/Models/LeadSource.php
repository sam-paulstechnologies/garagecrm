<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeadSource extends Model
{
    protected $table = 'lead_sources';

    public const CAPTURE_ENABLED_STATUSES = ['active', 'connected'];

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'status',
        'config',
        'form_token',
        'last_received_at',
    ];

    protected $casts = [
        'config' => 'array',
        'last_received_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($source) {
            if ($source->type === 'website' && empty($source->form_token)) {
                $source->form_token = Str::random(32);
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::CAPTURE_ENABLED_STATUSES);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function configValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config ?? [], $key, $default);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::CAPTURE_ENABLED_STATUSES, true);
    }
}
