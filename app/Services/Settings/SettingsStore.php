<?php

namespace App\Services\Settings;

use App\Models\Settings\CompanySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;

/**
 * Handles get/set of per-company settings (Meta, Twilio, System, etc.)
 * with caching and optional encryption handled at model level.
 */
class SettingsStore
{
    protected int $companyId;

    /**
     * Initialize store for a given company (defaults to logged-in user's company)
     */
    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId ?? (auth()->user()?->company_id);
    }

    /**
     * Cache key for this company
     */
    protected function cacheKey(): string
    {
        // Aligned with model's cache key to avoid duplicate caches
        return "company:{$this->companyId}:settings";
    }

    /**
     * Fetch all settings for company (cached)
     */
    public function all(): array
    {
        if (!$this->companyId) {
            return [];
        }

        return Cache::remember($this->cacheKey(), 600, function () {
            return CompanySetting::where('company_id', $this->companyId)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->key => $row->value])
                ->toArray();
        });
    }

    /**
     * Get a single setting value (cached)
     */
    public function get(string $key, $default = null)
    {
        $all = $this->all();
        return Arr::get($all, $key, $default);
    }

    /**
     * Set or update a setting for the current company
     */
    public function set(string $key, $value, array $options = []): void
    {
        if (!$this->companyId) {
            return;
        }

        $group   = $options['group'] ?? null;
        $encrypt = (bool)($options['encrypt'] ?? false);

        $row = CompanySetting::firstOrNew([
            'company_id' => $this->companyId,
            'key'        => $key,
        ]);

        $row->group        = $group;
        $row->is_encrypted = $encrypt;
        $row->updated_by   = auth()->id();
        $row->value        = is_scalar($value) || is_null($value) ? (string)($value ?? '') : json_encode($value);
        $row->save();

        $this->forgetCache();
    }

    /**
     * Forget cache for this company
     */
    public function forgetCache(): void
    {
        if ($this->companyId) {
            Cache::forget($this->cacheKey());
        }
    }

    /**
     * Delete a key (optional utility)
     */
    public function delete(string $key): void
    {
        if (!$this->companyId) return;

        CompanySetting::where('company_id', $this->companyId)
            ->where('key', $key)
            ->delete();

        $this->forgetCache();
    }
}
