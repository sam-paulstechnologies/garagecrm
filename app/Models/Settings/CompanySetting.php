<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * CompanySetting Model
 * Stores key-value settings for each company
 */
class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'group',
        'is_encrypted',
        'updated_by',
    ];

    /**
     * Cache key for a given company
     * Align with SettingsStore ("company:{id}:settings")
     */
    protected static function cacheKey(int $companyId): string
    {
        return "company:{$companyId}:settings";
    }

    /**
     * Get all settings for a company (cached forever)
     */
    public static function allForCompanyCached(int $companyId): array
    {
        $cacheKey = static::cacheKey($companyId);

        return Cache::rememberForever($cacheKey, function () use ($companyId) {
            return static::where('company_id', $companyId)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Forget cached settings
     */
    public static function forgetCache(int $companyId): void
    {
        Cache::forget(static::cacheKey($companyId));
    }

    /**
     * Get a setting value (non-JSON)
     */
    public static function getValue(int $companyId, string $key, $default = null)
    {
        $all = static::allForCompanyCached($companyId);
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * Get a setting decoded as JSON (if applicable)
     */
    public static function getJsonValue(int $companyId, string $key, $default = null)
    {
        $val = static::getValue($companyId, $key, $default);
        if ($val === null || $val === '') return $default;

        $decoded = json_decode($val, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    /**
     * Set or update a setting
     */
    public static function setValue(int $companyId, string $key, $value): void
    {
        static::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => is_scalar($value) || is_null($value) ? (string)($value ?? '') : json_encode($value)]
        );

        static::forgetCache($companyId);
    }

    /**
     * Bulk update settings (array)
     */
    public static function setMany(int $companyId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::setValue($companyId, $key, $value);
        }
        static::forgetCache($companyId);
    }

    /**
     * Relation (optional, if company model exists)
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
}
