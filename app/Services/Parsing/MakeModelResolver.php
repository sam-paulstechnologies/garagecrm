<?php

namespace App\Services\Parsing;

use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;

class MakeModelResolver
{
    /**
     * Parse free text like "Toyota Corolla 2018", "Jeep Grand Cherokee", "Honda City"
     * and resolve to (make_id, model_id, other_make, other_model).
     *
     * Priority: exact make match → fuzzy model under that make → fallbacks to other_*.
     */
    public static function resolve(string $text): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text));
        if ($clean === '') {
            return [null, null, null, null];
        }

        // Try to detect make by scanning all known makes (quick cache could be added)
        $makes = VehicleMake::query()->select('id','name')->get();
        $makeHit = null;

        foreach ($makes as $m) {
            if (stripos($clean, $m->name) !== false) {
                $makeHit = $m;
                break;
            }
        }

        // If make found → try detect model by searching models of that make inside text
        if ($makeHit) {
            $models = VehicleModel::query()
                ->where('vehicle_make_id', $makeHit->id)
                ->select('id','name')
                ->get();

            $modelHit = null;
            foreach ($models as $mm) {
                if (stripos($clean, $mm->name) !== false) {
                    $modelHit = $mm;
                    break;
                }
            }

            if ($modelHit) {
                return [$makeHit->id, $modelHit->id, null, null];
            }

            // Make found but model not recognized → store model as "other_model"
            $otherModel = self::guessOtherModel($clean, $makeHit->name);
            return [$makeHit->id, null, null, $otherModel];
        }

        // No make detected → fallback to other_* (keep first 2 tokens as a best-effort)
        [$otherMake, $otherModel] = self::fallbackOther($clean);
        return [null, null, $otherMake, $otherModel];
    }

    protected static function guessOtherModel(string $text, string $makeName): ?string
    {
        // remove make name and extra spaces; take next 1–2 tokens
        $rest = trim(str_ireplace($makeName, '', $text));
        if ($rest === '') return null;

        $tokens = preg_split('/[ ,\/\-]+/', $rest) ?: [];
        $pick   = array_slice($tokens, 0, 2);
        $candidate = trim(implode(' ', array_filter($pick)));
        return $candidate !== '' ? $candidate : null;
    }

    protected static function fallbackOther(string $text): array
    {
        $tokens = preg_split('/[ ,\/\-]+/', $text) ?: [];
        $otherMake  = isset($tokens[0]) ? $tokens[0] : null;
        $otherModel = isset($tokens[1]) ? $tokens[1] : null;
        return [$otherMake, $otherModel];
    }
}
