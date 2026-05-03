<?php

namespace App\Services\Vehicles;

use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Support\Facades\Cache;

class VehicleResolver
{
    protected array $makes = [];
    protected array $models = [];

    protected array $junkWords = [
        'thanks', 'thank', 'you', 'ok', 'okay', 'yes', 'no', 'hi', 'hello',
        'service', 'servicing', 'repair', 'maintenance', 'booking', 'book',
        'appointment', 'oil', 'change', 'brake', 'ac', 'battery', 'tyre',
        'tire', 'car', 'vehicle', 'please', 'need', 'want', 'for', 'my',
        'the', 'a', 'an', 'is', 'it', 'its', 'this', 'that'
    ];

    public function __construct()
    {
        $this->loadVehicleDictionary();
    }

    protected function loadVehicleDictionary(): void
    {
        $data = Cache::remember('vehicle_dictionary', 300, function () {

            $makes = VehicleMake::select('id', 'name', 'alias')->get();

            $models = VehicleModel::select('id', 'name', 'make_id', 'alias')
                ->get()
                ->groupBy('make_id');

            return [
                'makes'  => $makes,
                'models' => $models,
            ];
        });

        $this->makes  = $data['makes']->toArray();
        $this->models = $data['models']->toArray();
    }

    public static function clearCache(): void
    {
        Cache::forget('vehicle_dictionary');
    }

    public function resolve(string $text): array
    {
        $clean = $this->cleanText($text);

        if ($clean === '') {
            return [null, null, null, null];
        }

        $tokens = $this->tokens($clean);

        if (empty($tokens)) {
            return [null, null, null, null];
        }

        if (!$this->hasVehicleCandidateToken($tokens)) {
            return [null, null, null, null];
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 1: MATCH MAKE FIRST
        |--------------------------------------------------------------------------
        */

        $matchedMake = collect($this->makes)
            ->sortByDesc(fn ($m) => strlen((string) $m['name']))
            ->first(function ($make) use ($clean, $tokens) {

                $names = $this->namesWithAliases($make);

                foreach ($names as $makeName) {

                    if ($makeName === '') {
                        continue;
                    }

                    if (preg_match('/\b' . preg_quote($makeName, '/') . '\b/', $clean)) {
                        return true;
                    }

                    foreach ($tokens as $token) {
                        if ($this->isFuzzyMatch($token, $makeName)) {
                            return true;
                        }
                    }
                }

                return false;
            });

        if ($matchedMake) {

            $makeId = (int) $matchedMake['id'];

            $modelsForMake = $this->models[$makeId] ?? [];

            $matchedModel = $this->matchModel($modelsForMake, $clean, $tokens);

            if ($matchedModel) {
                return [
                    $makeId,
                    (int) $matchedModel['id'],
                    null,
                    null,
                ];
            }

            $freeTextModel = $this->extractModelForMatchedMake($matchedMake, $tokens);

            return [
                $makeId,
                null,
                null,
                $freeTextModel,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 2: MATCH MODEL GLOBALLY
        |--------------------------------------------------------------------------
        */

        foreach ($this->models as $makeId => $modelsForMake) {

            $matchedModel = $this->matchModel($modelsForMake, $clean, $tokens);

            if ($matchedModel) {
                return [
                    (int) $makeId,
                    (int) $matchedModel['id'],
                    null,
                    null,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 3: SAFE FALLBACK
        |--------------------------------------------------------------------------
        | This prevents the bot from looping forever for valid but unknown vehicles.
        | Example:
        | Audi q5 → other_make = Audi, other_model = Q5
        | BYD Seal → other_make = Byd, other_model = Seal
        |--------------------------------------------------------------------------
        */

        $vehicleTokens = $this->possibleVehicleTokens($tokens);

        if (count($vehicleTokens) >= 2) {
            return [
                null,
                null,
                $this->formatVehicleWord($vehicleTokens[0]),
                $this->formatVehicleWord($vehicleTokens[1]),
            ];
        }

        if (count($vehicleTokens) === 1 && strlen($vehicleTokens[0]) >= 3) {
            return [
                null,
                null,
                $this->formatVehicleWord($vehicleTokens[0]),
                null,
            ];
        }

        return [null, null, null, null];
    }

    protected function extractModelForMatchedMake(array $matchedMake, array $tokens): ?string
    {
        $makeNames = $this->namesWithAliases($matchedMake);

        $modelTokens = collect($tokens)
            ->reject(fn ($token) => in_array($token, $this->junkWords, true))
            ->reject(function ($token) use ($makeNames) {
                foreach ($makeNames as $makeName) {
                    if ($token === $makeName || $this->isFuzzyMatch($token, $makeName)) {
                        return true;
                    }
                }

                return false;
            })
            ->filter(fn ($token) => $this->looksLikeModelToken($token))
            ->values();

        if ($modelTokens->isEmpty()) {
            return null;
        }

        return $this->formatVehicleWord((string) $modelTokens->first());
    }

    protected function matchModel(array $modelsForMake, string $clean, array $tokens)
    {
        return collect($modelsForMake)
            ->sortByDesc(fn ($m) => strlen((string) $m['name']))
            ->first(function ($model) use ($clean, $tokens) {

                $names = $this->namesWithAliases($model);

                foreach ($names as $name) {

                    if ($name === '') {
                        continue;
                    }

                    if (preg_match('/\b' . preg_quote($name, '/') . '\b/', $clean)) {
                        return true;
                    }

                    foreach ($tokens as $token) {
                        if ($this->isFuzzyMatch($token, $name)) {
                            return true;
                        }
                    }
                }

                return false;
            });
    }

    protected function namesWithAliases(array $row): array
    {
        $names = [
            strtolower(trim((string) ($row['name'] ?? ''))),
        ];

        if (!empty($row['alias'])) {

            $aliases = is_array($row['alias'])
                ? $row['alias']
                : json_decode($row['alias'], true);

            if (is_array($aliases)) {
                foreach ($aliases as $alias) {
                    $names[] = strtolower(trim((string) $alias));
                }
            }
        }

        return array_values(array_unique(array_filter($names)));
    }

    protected function cleanText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s\-\/]/i', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    protected function tokens(string $clean): array
    {
        return collect(preg_split('/[ ,\-\/]+/', $clean))
            ->map(fn ($t) => strtolower(trim($t)))
            ->filter(fn ($t) => $t !== '')
            ->filter(fn ($t) => !is_numeric($t))
            ->filter(fn ($t) => strlen($t) >= 2)
            ->values()
            ->toArray();
    }

    protected function possibleVehicleTokens(array $tokens): array
    {
        $vehicleTokens = collect($tokens)
            ->reject(fn ($t) => in_array($t, $this->junkWords, true))
            ->filter(fn ($t) => $this->looksLikeVehicleToken($t))
            ->values()
            ->take(2)
            ->toArray();

        if (count($vehicleTokens) < 2) {
            return [];
        }

        if (!$this->looksLikeMakeToken($vehicleTokens[0]) || !$this->looksLikeModelToken($vehicleTokens[1])) {
            return [];
        }

        return $vehicleTokens;
    }

    protected function hasVehicleCandidateToken(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (!in_array($token, $this->junkWords, true)) {
                return true;
            }
        }

        return false;
    }

    protected function looksLikeVehicleToken(string $token): bool
    {
        return $this->looksLikeMakeToken($token) || $this->looksLikeModelToken($token);
    }

    protected function looksLikeMakeToken(string $token): bool
    {
        $token = strtolower(trim($token));

        if ($token === '' || in_array($token, $this->junkWords, true)) {
            return false;
        }

        return preg_match('/^[a-z][a-z0-9]{2,}$/i', $token) === 1;
    }

    protected function looksLikeModelToken(string $token): bool
    {
        $token = strtolower(trim($token));

        if ($token === '' || in_array($token, $this->junkWords, true)) {
            return false;
        }

        if (preg_match('/^[a-z]{2,}$/i', $token) === 1 && strlen($token) >= 3) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Short model support:
        | q5, x5, q7, gl, rx, lx, cx5, glc, etc.
        |--------------------------------------------------------------------------
        */
        return preg_match('/^[a-z]{1,3}[0-9]{0,2}$/i', $token) === 1
            && strlen($token) >= 2;
    }

    protected function formatVehicleWord(string $word): string
    {
        $word = trim($word);

        if ($word === '') {
            return $word;
        }

        /*
        |--------------------------------------------------------------------------
        | Keep alphanumeric models uppercase
        | q5 → Q5
        | x5 → X5
        |--------------------------------------------------------------------------
        */
        if (preg_match('/[a-z]/i', $word) && preg_match('/[0-9]/', $word)) {
            return strtoupper($word);
        }

        return ucfirst(strtolower($word));
    }

    protected function isFuzzyMatch(string $token, string $name): bool
    {
        $token = strtolower(trim($token));
        $name  = strtolower(trim($name));

        if ($token === '' || $name === '') {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Exact short match support
        | Important for q5, x5, rx, gl, lx etc.
        |--------------------------------------------------------------------------
        */
        if ($token === $name) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Avoid fuzzy matching very short names
        |--------------------------------------------------------------------------
        */
        if (strlen($token) < 4 || strlen($name) < 4) {
            return false;
        }

        if (abs(strlen($token) - strlen($name)) > 2) {
            return false;
        }

        return levenshtein($token, $name) <= 1;
    }
}
