<?php

namespace Services\Nutrition;

/**
 * Normalize nutrition payloads from every provider we consume
 * (Spoonacular, FDC, Open Food Facts, FatSecret) into a common shape:
 *
 *   [
 *       'nutrients' => [ ['name' => ..., 'amount' => ..., 'unit' => ...], ... ],
 *       'servings'  => ['original' => 'per serving' | 'per 100 g' | actual string],
 *   ]
 *
 * Return null when nothing parses.
 */
class Normalizer
{
    public static function normalize(mixed $src): ?array
    {
        if (!is_array($src) || !$src) return null;

        // Bare FDC list of FoodNutrient entries -> wrap so downstream branches see it.
        if (isset($src[0]) && is_array($src[0])
            && (isset($src[0]['nutrient'])
                || isset($src[0]['nutrientName'])
                || (isset($src[0]['type']) && $src[0]['type'] === 'FoodNutrient'))
        ) {
            $src = ['foodNutrients' => $src];
        }

        // Already in target shape.
        if (isset($src['nutrients']) && is_array($src['nutrients'])) {
            return $src;
        }

        if ($fs = self::fromFatSecret($src)) return $fs;
        if ($ln = self::fromFdcLabelNutrients($src)) return $ln;
        if ($fn = self::fromFdcFoodNutrients($src)) return $fn;
        if ($off = self::fromOffNutriments($src)) return $off;
        if ($flat = self::fromFlatLabel($src)) return $flat;

        return null;
    }

    private static function fromFatSecret(array $src): ?array
    {
        if (!isset($src['servings']['serving']) || !is_array($src['servings']['serving'])) {
            return null;
        }
        $servings = $src['servings']['serving'];
        // Single object gets wrapped when there's only one serving.
        if (isset($servings['serving_id'])) {
            $servings = [$servings];
        }
        $serving = $servings[0] ?? null;
        foreach ($servings as $s) {
            if (!empty($s['is_default'])) { $serving = $s; break; }
        }
        if (!$serving) return null;

        $map = [
            'calories'            => ['Calories',            'kcal'],
            'protein'             => ['Protein',             'g'],
            'carbohydrate'        => ['Carbohydrates',       'g'],
            'fat'                 => ['Fat',                 'g'],
            'saturated_fat'       => ['Saturated Fat',       'g'],
            'polyunsaturated_fat' => ['Polyunsaturated Fat', 'g'],
            'monounsaturated_fat' => ['Monounsaturated Fat', 'g'],
            'trans_fat'           => ['Trans Fat',           'g'],
            'fiber'               => ['Fiber',               'g'],
            'sugar'               => ['Sugar',               'g'],
            'sodium'              => ['Sodium',              'mg'],
            'potassium'           => ['Potassium',           'mg'],
            'cholesterol'         => ['Cholesterol',         'mg'],
            'calcium'             => ['Calcium',             'mg'],
            'iron'                => ['Iron',                'mg'],
            'vitamin_a'           => ['Vitamin A',           'IU'],
            'vitamin_c'           => ['Vitamin C',           'mg'],
        ];

        $nutrients = [];
        foreach ($map as $key => [$label, $unit]) {
            if (isset($serving[$key]) && $serving[$key] !== '' && $serving[$key] !== null) {
                $amount = (float)$serving[$key];
                if ($amount > 0 || $key === 'calories' || $amount !== 0.0) {
                    $nutrients[] = ['name' => $label, 'amount' => $amount, 'unit' => $unit];
                }
            }
        }
        $servingText = $serving['serving_description'] ?? 'per serving';
        return $nutrients ? ['nutrients' => $nutrients, 'servings' => ['original' => $servingText]] : null;
    }

    private static function fromFdcLabelNutrients(array $src): ?array
    {
        if (!isset($src['labelNutrients']) || !is_array($src['labelNutrients'])) return null;

        $ln = $src['labelNutrients'];
        $get = fn($k) => isset($ln[$k]['value']) ? (float)$ln[$k]['value'] : null;
        $nutrients = [
            ['name' => 'Calories',      'amount' => $get('calories'),      'unit' => 'kcal'],
            ['name' => 'Protein',       'amount' => $get('protein'),       'unit' => 'g'],
            ['name' => 'Fat',           'amount' => $get('fat'),           'unit' => 'g'],
            ['name' => 'Saturated Fat', 'amount' => $get('saturatedFat'),  'unit' => 'g'],
            ['name' => 'Carbohydrates', 'amount' => $get('carbohydrates'), 'unit' => 'g'],
            ['name' => 'Fiber',         'amount' => $get('fiber'),         'unit' => 'g'],
            ['name' => 'Sugar',         'amount' => $get('sugars'),        'unit' => 'g'],
            ['name' => 'Sodium',        'amount' => $get('sodium'),        'unit' => 'mg'],
            ['name' => 'Calcium',       'amount' => $get('calcium'),       'unit' => 'mg'],
            ['name' => 'Iron',          'amount' => $get('iron'),          'unit' => 'mg'],
            ['name' => 'Potassium',     'amount' => $get('potassium'),     'unit' => 'mg'],
            ['name' => 'Cholesterol',   'amount' => $get('cholesterol'),   'unit' => 'mg'],
        ];
        $nutrients = array_values(array_filter($nutrients, fn($n) => $n['amount'] !== null));

        $servingText = null;
        if (isset($src['servingSize'], $src['servingSizeUnit'])) {
            $servingText = $src['servingSize'] . ' ' . $src['servingSizeUnit'];
        } elseif (isset($src['householdServingFullText'])) {
            $servingText = $src['householdServingFullText'];
        }
        return $nutrients ? ['nutrients' => $nutrients, 'servings' => ['original' => $servingText ?? 'per serving']] : null;
    }

    private static function fromFdcFoodNutrients(array $src): ?array
    {
        if (!isset($src['foodNutrients']) || !is_array($src['foodNutrients'])) return null;

        $core = [
            'Energy'                            => ['Calories',      'kcal'],
            'Energy (Atwater General Factors)'  => ['Calories',      'kcal'],
            'Protein'                           => ['Protein',       'g'],
            'Total lipid (fat)'                 => ['Fat',           'g'],
            'Carbohydrate, by difference'       => ['Carbohydrates', 'g'],
            'Fiber, total dietary'              => ['Fiber',         'g'],
            'Sugars, total including NLEA'      => ['Sugar',         'g'],
            'Sugars, total'                     => ['Sugar',         'g'],
            'Sodium, Na'                        => ['Sodium',        'mg'],
            'Calcium, Ca'                       => ['Calcium',       'mg'],
            'Iron, Fe'                          => ['Iron',          'mg'],
            'Potassium, K'                      => ['Potassium',     'mg'],
            'Cholesterol'                       => ['Cholesterol',   'mg'],
        ];
        $bucket = [];
        $extras = [];
        foreach ($src['foodNutrients'] as $fn) {
            $name = $fn['nutrient']['name'] ?? $fn['nutrientName'] ?? null;
            $amount = $fn['amount'] ?? $fn['value'] ?? null;
            $unit = $fn['nutrient']['unitName'] ?? $fn['unitName'] ?? null;
            if (!$name || $amount === null) continue;
            $out = [
                'name'   => $core[$name][0] ?? $name,
                'amount' => (float)$amount,
                'unit'   => $unit ?: ($core[$name][1] ?? ''),
            ];
            if (isset($core[$name])) {
                $bucket[$out['name']] = $out;
            } else {
                $extras[] = $out;
            }
        }
        $list = array_values($bucket);
        foreach ($extras as $ex) {
            if (!isset($bucket[$ex['name']])) $list[] = $ex;
        }
        if (!$list) return null;

        $servingText = null;
        if (isset($src['servingSize'], $src['servingSizeUnit'])) {
            $servingText = $src['servingSize'] . ' ' . $src['servingSizeUnit'];
        } elseif (isset($src['householdServingFullText'])) {
            $servingText = $src['householdServingFullText'];
        }
        if (count($list) > 200) $list = array_slice($list, 0, 200);
        return ['nutrients' => $list, 'servings' => ['original' => $servingText ?? 'per 100 g']];
    }

    private static function fromOffNutriments(array $src): ?array
    {
        $nutriments = $src['nutriments'] ?? null;
        if (!$nutriments && self::looksLikeOffNutriments($src)) {
            $nutriments = $src;
        }
        if (!is_array($nutriments)) return null;

        $pick = function (string $base) use ($nutriments): ?array {
            if (isset($nutriments[$base . '_serving'])) return ['v' => (float)$nutriments[$base . '_serving'], 'scope' => 'per serving'];
            if (isset($nutriments[$base . '_100g']))    return ['v' => (float)$nutriments[$base . '_100g'],    'scope' => 'per 100 g'];
            return null;
        };
        $unit = function (string $base, string $default) use ($nutriments): string {
            $k = $base . '_unit';
            return isset($nutriments[$k]) && is_string($nutriments[$k]) ? $nutriments[$k] : $default;
        };

        $map = [
            ['Calories',      'energy-kcal',    'kcal'],
            ['Protein',       'proteins',       'g'],
            ['Fat',           'fat',            'g'],
            ['Saturated Fat', 'saturated-fat',  'g'],
            ['Carbohydrates', 'carbohydrates',  'g'],
            ['Fiber',         'fiber',          'g'],
            ['Sugar',         'sugars',         'g'],
            ['Sodium',        'sodium',         'mg'],
            ['Calcium',       'calcium',        'mg'],
            ['Iron',          'iron',           'mg'],
            ['Potassium',     'potassium',      'mg'],
        ];

        $scope = $src['serving_size'] ?? null;
        if (!$scope && isset($src['serving_quantity'], $src['serving_quantity_unit'])) {
            $scope = $src['serving_quantity'] . ' ' . $src['serving_quantity_unit'];
        }

        $out = [];
        $taken = [];
        foreach ($map as [$name, $base, $defUnit]) {
            $picked = $pick($base);
            if ($picked) {
                $out[] = ['name' => $name, 'amount' => $picked['v'], 'unit' => $unit($base, $defUnit)];
                if ($scope === null) $scope = $picked['scope'];
                $taken[$base] = true;
            }
        }
        foreach ($nutriments as $key => $val) {
            if (!is_scalar($val)) continue;
            if (preg_match('/^(.+?)_(serving|100g)$/', (string)$key, $m)) {
                $base = $m[1];
                if (isset($taken[$base])) continue;
                $v = (float)$val;
                if (!is_finite($v)) continue;
                $nm = ucwords(str_replace(['-', '_'], [' ', ' '], $base));
                $out[] = ['name' => $nm, 'amount' => $v, 'unit' => $unit($base, '')];
            }
        }
        if (!$out) return null;

        if (count($out) > 200) $out = array_slice($out, 0, 200);
        return ['nutrients' => $out, 'servings' => ['original' => $scope ?? 'per 100 g']];
    }

    private static function fromFlatLabel(array $src): ?array
    {
        $flatKeys = ['calories', 'protein', 'fat', 'saturatedFat', 'transFat', 'carbohydrates', 'fiber', 'sugars', 'sodium', 'calcium', 'iron', 'potassium', 'cholesterol', 'addedSugars'];
        $hasFlat = false;
        foreach ($flatKeys as $k) {
            if (isset($src[$k]) && is_array($src[$k]) && array_key_exists('value', $src[$k])) {
                $hasFlat = true;
                break;
            }
        }
        if (!$hasFlat) return null;

        $get = fn(string $k): ?float => isset($src[$k]['value']) ? (float)$src[$k]['value'] : null;
        $nutrients = [
            ['name' => 'Calories',      'amount' => $get('calories'),       'unit' => 'kcal'],
            ['name' => 'Protein',       'amount' => $get('protein'),        'unit' => 'g'],
            ['name' => 'Fat',           'amount' => $get('fat'),            'unit' => 'g'],
            ['name' => 'Saturated Fat', 'amount' => $get('saturatedFat'),   'unit' => 'g'],
            ['name' => 'Trans Fat',     'amount' => $get('transFat'),       'unit' => 'g'],
            ['name' => 'Carbohydrates', 'amount' => $get('carbohydrates'),  'unit' => 'g'],
            ['name' => 'Fiber',         'amount' => $get('fiber'),          'unit' => 'g'],
            ['name' => 'Sugar',         'amount' => $get('sugars'),         'unit' => 'g'],
            ['name' => 'Added Sugars',  'amount' => $get('addedSugars'),    'unit' => 'g'],
            ['name' => 'Sodium',        'amount' => $get('sodium'),         'unit' => 'mg'],
            ['name' => 'Calcium',       'amount' => $get('calcium'),        'unit' => 'mg'],
            ['name' => 'Iron',          'amount' => $get('iron'),           'unit' => 'mg'],
            ['name' => 'Potassium',     'amount' => $get('potassium'),      'unit' => 'mg'],
            ['name' => 'Cholesterol',   'amount' => $get('cholesterol'),    'unit' => 'mg'],
        ];
        $nutrients = array_values(array_filter($nutrients, fn($n) => $n['amount'] !== null));
        return $nutrients ? ['nutrients' => $nutrients, 'servings' => ['original' => 'per serving']] : null;
    }

    private static function looksLikeOffNutriments(array $a): bool
    {
        foreach (['energy-kcal_100g', 'fat_100g', 'proteins_100g', 'carbohydrates_100g'] as $k) {
            if (array_key_exists($k, $a)) return true;
        }
        return false;
    }
}
