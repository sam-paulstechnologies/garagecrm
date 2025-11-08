<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompanySetting
{
    public static function get(int $companyId, string $key, $default = null)
    {
        $cacheKey = "cs:{$companyId}:{$key}";
        return Cache::remember($cacheKey, 60, function () use ($companyId, $key, $default) {
            $row = DB::table('company_settings')
                ->select('value')
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->first();

            if (!$row) return $default;

            $val = trim((string) $row->value);
            if ($val === '') return $default;

            // JSON if looks like JSON, else string/int/bool
            if (in_array(substr($val, 0, 1), ['{', '['])) {
                $decoded = json_decode($val, true);
                return $decoded === null ? $val : $decoded;
            }
            if (is_numeric($val)) return $val + 0;
            if (in_array(strtolower($val), ['true','false'], true)) return strtolower($val) === 'true';
            return $val;
        });
    }

    public static function set(int $companyId, string $key, $value, string $group = 'ai', ?int $updatedBy = null): void
    {
        $store = is_array($value) ? json_encode($value) : (string) $value;

        DB::table('company_settings')->updateOrInsert(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $store, 'group' => $group, 'updated_by' => $updatedBy, 'updated_at' => now(), 'created_at' => now()]
        );

        Cache::forget("cs:{$companyId}:{$key}");
    }
}
