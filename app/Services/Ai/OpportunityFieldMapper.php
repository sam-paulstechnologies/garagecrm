<?php

namespace App\Services\Ai;

use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;

class OpportunityFieldMapper
{
    /**
     * Extract structured opportunity fields from free text.
     *
     * @param string $message
     * @return array<string, mixed>
     */
    public function extract(string $message): array
    {
        $text = strtolower($message);

        $data = [];

        /** -------------------------
         * SERVICE TYPE
         * ------------------------- */
        $serviceKeywords = [
            'oil service'     => 'Oil Service',
            'oil change'      => 'Oil Service',
            'full service'    => 'Full Service',
            'major service'   => 'Major Service',
            'minor service'   => 'Minor Service',
            'brake'           => 'Brake Service',
            'ac service'      => 'AC Service',
            'air conditioning'=> 'AC Service',
            'battery'         => 'Battery Replacement',
            'tyre'            => 'Tyre Service',
            'tire'            => 'Tyre Service',
            'inspection'      => 'Inspection',
        ];

        foreach ($serviceKeywords as $needle => $service) {
            if (str_contains($text, $needle)) {
                $data['service_type'] = $service;
                break;
            }
        }

        /** -------------------------
         * VEHICLE YEAR
         * ------------------------- */
        if (preg_match('/(19|20)\d{2}/', $message, $matches)) {
            $data['notes'] = trim(($data['notes'] ?? '') . ' Year: ' . $matches[0]);
        }

        /** -------------------------
         * VEHICLE MAKE / MODEL (DB lookup first)
         * ------------------------- */
        $makes = VehicleMake::query()->pluck('name', 'id');

        foreach ($makes as $makeId => $makeName) {
            if (str_contains($text, strtolower($makeName))) {

                $data['vehicle_make_id'] = $makeId;

                // Try model detection
                $models = VehicleModel::where('vehicle_make_id', $makeId)
                    ->pluck('name', 'id');

                foreach ($models as $modelId => $modelName) {
                    if (str_contains($text, strtolower($modelName))) {
                        $data['vehicle_model_id'] = $modelId;
                        break;
                    }
                }

                break;
            }
        }

        /** -------------------------
         * FALLBACK: OTHER MAKE / MODEL
         * ------------------------- */
        if (!isset($data['vehicle_make_id'])) {
            if (preg_match('/car is ([a-zA-Z]+)\s?([a-zA-Z0-9]+)?/', $text, $m)) {
                $data['other_make']  = ucfirst($m[1]);
                $data['other_model'] = isset($m[2]) ? ucfirst($m[2]) : null;
            }
        }

        /** -------------------------
         * NOTES / PREFERENCES
         * ------------------------- */
        $preferenceKeywords = [
            'pickup',
            'drop',
            'morning',
            'evening',
            'urgent',
            'today',
            'tomorrow',
        ];

        foreach ($preferenceKeywords as $word) {
            if (str_contains($text, $word)) {
                $data['notes'] = trim(($data['notes'] ?? '') . ' ' . ucfirst($word));
            }
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
