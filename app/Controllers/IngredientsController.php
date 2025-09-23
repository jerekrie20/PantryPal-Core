<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Recipes;
use Services\FoodService;

/**
 * Ingredient-focused controller: handles search/confirm/create for ingredient flow.
 */
class IngredientsController
{
    protected Items $items;
    protected Ingredients $ingredients;
    protected FoodService $svc;

    public function __construct()
    {
        $this->items = new Items();
        $this->ingredients = new Ingredients();
        $this->svc = new FoodService();
    }

    /** GET /ingredients/create */
    public function create(): string
    {
        // Use the unified Items/create view; preset api_kind for ingredient flow
        return View::render('Items/create', [
            'title' => 'Add Ingredient',
            'errors' => [],
            'input' => ['api_kind' => 'ingredient'],
        ]);
    }

    /** POST /ingredients */
    public function store()
    {
        // Reuse validation rules from ItemsController
        $itemsController = new ItemsController();
        $_POST['api_kind'] = 'ingredient';
        $validationView = $itemsController->validation();
        if ($validationView !== null) {
            return $validationView;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $rawName = trim($_POST['name'] ?? '');
        $brand   = isset($_POST['brand']) ? trim($_POST['brand']) : null;
        $normName  = Ingredients::normalizeName($rawName);
        $normBrand = Ingredients::normalizeBrand($brand);

        // Exact local match
        $ingredient = $this->ingredients->findExact($normName, $normBrand, 'ingredient');
        if ($ingredient) {
            $this->items->create([
                'user_id' => $userId,
                'ingredient_id' => (int)$ingredient['id'],
                'product_id' => null,
                'quantity' => $_POST['quantity'],
                'unit' => $_POST['unit'] ?? null,
                'purchase_date' => $_POST['purchase_date'] ?? null,
                'expiration_date' => $_POST['expiration_date'] ?? null,
                'entered_name' => $rawName,
                'entered_brand' => $brand,
            ]);
            // Invalidate dashboard caches
            try {
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
            } catch (\Throwable $e) { /* ignore */ }
            header('Location: /dashboard');
            exit;
        }

        // Build choices: local ingredients + provider
        $choices = [];
        $localIngs = $this->ingredients->searchFuzzy($normName, $normBrand, 'ingredient', 8);
        foreach ($localIngs as $r) {
            $choices[] = [
                'source' => 'local',
                'api_id' => $r['api_id'] ?? 0,
                'name' => $r['name'],
                'brand' => $r['brand'] ?? null,
                'image_url' => $r['image_url'] ?? null,
                'type' => $r['api_kind'] ?? 'ingredient',
                'ingredient_id' => (int)$r['id'],
                'product_id' => null,
            ];
        }

        $apiChoices = $this->svc->searchWithKind($rawName, 'ingredient', $normBrand, 6);
        foreach ($apiChoices as $r) {
            $choices[] = [
                'source' => $r['source'] ?? 'provider',
                'api_id' => $r['api_id'],
                'name' => $r['name'],
                'brand' => $r['brand'] ?? null,
                'image_url' => $r['image_url'] ?? null,
                'type' => $r['type'],
                'ingredient_id' => null,
                'product_id' => null,
            ];
        }

        return View::render('Items/confirm', [
            'title' => 'Confirm Ingredient',
            'choices' => $choices,
            'original_input' => $_POST,
            'api_kind' => 'ingredient',
            'confirm_action' => '/ingredients/confirm',
        ]);
    }

    /** POST /ingredients/confirm */
    public function confirm()
    {
        $pickedSource = $_POST['picked_source'] ?? null; // 'local' | provider | 'manual'
        $apiId = $_POST['api_id'] ?? null;
        $ingredientId = (int)($_POST['ingredient_id'] ?? 0);
        $original = $_POST['original_input'] ?? [];
        $userId = $_SESSION['user_id'] ?? null;

        $rawName = trim($original['name'] ?? '');
        $brand   = isset($original['brand']) ? trim($original['brand']) : null;

        if ($pickedSource === 'local' && $ingredientId > 0) {
            $this->items->create([
                'user_id' => $userId,
                'ingredient_id' => $ingredientId,
                'product_id' => null,
                'quantity' => $original['quantity'] ?? 1,
                'unit' => $original['unit'] ?? null,
                'purchase_date' => $original['purchase_date'] ?? null,
                'expiration_date' => $original['expiration_date'] ?? null,
                'entered_name' => $rawName,
                'entered_brand' => $brand,
            ]);
            // Invalidate dashboard caches
            try {
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
            } catch (\Throwable $e) { /* ignore */ }
            header('Location: /dashboard');
            exit;
        }

        if ($pickedSource === 'manual' || empty($apiId)) {
            $norm = Ingredients::normalizeName($rawName);
            $normBrand = Ingredients::normalizeBrand($brand);
            $existing = $this->ingredients->findExact($norm, $normBrand, null);
            $ingId = $existing ? (int)$existing['id'] : $this->ingredients->create([
                'name' => $rawName,
                'normalized_name' => $norm,
                'brand' => $normBrand,
                'api_source' => null,
                'api_id' => null,
                'api_kind' => 'manual',
                'image_url' => null,
                'category' => null,
                'nutrition_info' => null,
                'search_terms' => $rawName . ' ' . ($brand ?? ''),
            ]);

            $this->items->create([
                'user_id' => $userId,
                'ingredient_id' => (int)$ingId,
                'product_id' => null,
                'quantity' => $original['quantity'] ?? 1,
                'unit' => $original['unit'] ?? null,
                'purchase_date' => $original['purchase_date'] ?? null,
                'expiration_date' => $original['expiration_date'] ?? null,
                'entered_name' => $rawName,
                'entered_brand' => $brand,
            ]);
            // Invalidate dashboard caches
            try {
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
            } catch (\Throwable $e) { /* ignore */ }
            header('Location: /dashboard');
            exit;
        }

        // Provider path
        $ing = $this->svc->ensureIngredientFromApi($apiId, $rawName, $brand);
        if (!$ing) {
            return View::render('Items/create', [
                'title' => 'Add Ingredient',
                'errors' => ['Failed to save ingredient from provider.'],
                'input' => $original,
            ]);
        }
        $this->items->create([
            'user_id' => $userId,
            'ingredient_id' => (int)$ing['id'],
            'product_id' => null,
            'quantity' => $original['quantity'] ?? 1,
            'unit' => $original['unit'] ?? null,
            'purchase_date' => $original['purchase_date'] ?? null,
            'expiration_date' => $original['expiration_date'] ?? null,
            'entered_name' => $rawName,
            'entered_brand' => $brand,
        ]);
        // Invalidate dashboard caches
        try {
            \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
            \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
        } catch (\Throwable $e) { /* ignore */ }
        header('Location: /dashboard');
        exit;
    }

    /** GET /ingredients/view/{id:int} */
    public function show(int $id): string
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }

            // Load the base item and ensure it belongs to the user
            $itemRow = $this->items->find($id);
            if (!$itemRow || (int)$itemRow['user_id'] !== (int)$userId) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }

            // Ingredient must exist for this view
            if (empty($itemRow['ingredient_id'])) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Ingredient Not Found']);
            }

            $ing = $this->ingredients->find((int)$itemRow['ingredient_id']);
            if (!$ing) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Ingredient Not Found']);
            }

            // Status/badge
            $status = 'In Stock';
            $badge  = 'badge-success';
            if (!empty($itemRow['expiration_date'])) {
                try {
                    $today    = new \DateTimeImmutable('today');
                    $exp      = new \DateTimeImmutable($itemRow['expiration_date']);
                    $diffDays = (int)$today->diff($exp)->format('%r%a');
                    if     ($diffDays < 0) { $status = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago'); $badge = 'badge-danger'; }
                    elseif ($diffDays === 0){ $status = 'Expires today'; $badge = 'badge-warning'; }
                    elseif ($diffDays <= 3){ $status = 'Expires in ' . ($diffDays === 1 ? '1 day' : $diffDays . ' days'); $badge = 'badge-warning'; }
                    else                    { $status = 'Expires in ' . $diffDays . ' days'; $badge = 'badge-neutral'; }
                } catch (\Exception $e) {}
            }

            // Nutrition: robustly decode and normalize the ingredient's nutrition_info
            $nutrition = null;
            $rawNutri = null;
            $ingNutri = $ing['nutrition_info'] ?? null;
            if ($ingNutri) {
                $decoded = is_array($ingNutri) ? $ingNutri : json_decode((string)$ingNutri, true);
                if (!is_array($decoded)) {
                    $s = (string)$ingNutri;
                    $stripped = stripslashes($s);
                    $decoded = json_decode($stripped, true);
                    if (!is_array($decoded)) {
                        $once = json_decode($s, true);
                        if (is_string($once)) {
                            $decoded = json_decode($once, true);
                        } elseif (is_array($once)) {
                            foreach ($once as $vv) {
                                if (is_string($vv) && strlen($vv) > 10 && ($vv[0] === '{' || $vv[0] === '[')) {
                                    $decoded = json_decode($vv, true);
                                    if (is_array($decoded)) break;
                                }
                            }
                        }
                    }
                }
                // Handle bare array of FoodNutrient entries
                if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0]) && (isset($decoded[0]['nutrient']) || isset($decoded[0]['nutrientName']) || (isset($decoded[0]['type']) && $decoded[0]['type'] === 'FoodNutrient'))) {
                    $decoded = ['foodNutrients' => $decoded];
                }
                if (is_array($decoded)) {
                    $rawNutri = $decoded;
                    $nutrition = $this->normalizeNutrition($decoded);
                }
            }

            // Display fields
            $displayName = $ing['name'] ?? ($itemRow['entered_name'] ?? 'Ingredient');
            $displayCategory = $this->stringifyCategory($ing['category'] ?? null);
            $displayImage = $ing['image_url'] ?? null;

            $item = [
                'id'              => (int)$itemRow['id'],
                'name'            => $displayName,
                'category'        => $displayCategory,
                'image'           => $displayImage,
                'quantity'        => $itemRow['quantity'] ?? null,
                'unit'            => $itemRow['unit'] ?? null,
                'purchase_date'   => $itemRow['purchase_date'] ?? null,
                'expiration_date' => $itemRow['expiration_date'] ?? null,
                'status'          => $status,
                'badge_class'     => $badge,
                'nutrition'       => $nutrition,
                'nutrition_raw'   => $rawNutri,
                'brand'           => $ing['brand'] ?? ($itemRow['entered_brand'] ?? null),
            ];

            return View::render('Ingredients/show', [
                'title' => 'Ingredient Details',
                'item'  => $item,
                'recipesList' => (function() use ($item) {
                    $list = [];
                    try {
                        $term = isset($item['name']) ? (string)$item['name'] : '';
                        if ($term !== '') {
                            $model = new Recipes();
                            $list = $model->searchLocalByQuery($term, 6);
                        }
                    } catch (\Throwable $e) { $list = []; }
                    return $list;
                })(),
            ]);

        } catch (\Throwable $e) {
            error_log('IngredientsController::show error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    // --- Helpers copied locally to keep separation from ItemsController ---
    private function stringifyCategory($cat): ?string
    {
        if ($cat === null || $cat === '') return null;
        if (is_string($cat)) {
            $trim = ltrim($cat);
            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($cat, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->stringifyCategory($decoded);
                }
            }
            return $cat;
        }
        if (is_array($cat)) {
            if (isset($cat['categoryPath']) && is_array($cat['categoryPath'])) {
                return implode(' › ', array_filter($cat['categoryPath'], 'is_string'));
            }
            $vals = [];
            foreach ($cat as $v) if (is_string($v)) $vals[] = $v;
            return $vals ? implode(' › ', $vals) : null;
        }
        return null;
    }

    /**
     * Normalize various provider formats to a common nutrition structure.
     */
    private function normalizeNutrition($src): ?array
    {
        if (!is_array($src) || !$src) return null;

        // Already normalized
        if (isset($src['nutrients']) && is_array($src['nutrients'])) {
            return $src;
        }

        // FDC labelNutrients
        if (isset($src['labelNutrients']) && is_array($src['labelNutrients'])) {
            $ln = $src['labelNutrients'];
            $get = fn($k) => isset($ln[$k]['value']) ? (float)$ln[$k]['value'] : null;
            $nutrients = [
                ['name'=>'Calories',       'amount'=>$get('calories'),      'unit'=>'kcal'],
                ['name'=>'Protein',        'amount'=>$get('protein'),       'unit'=>'g'],
                ['name'=>'Fat',            'amount'=>$get('fat'),           'unit'=>'g'],
                ['name'=>'Saturated Fat',  'amount'=>$get('saturatedFat'),  'unit'=>'g'],
                ['name'=>'Carbohydrates',  'amount'=>$get('carbohydrates'), 'unit'=>'g'],
                ['name'=>'Fiber',          'amount'=>$get('fiber'),         'unit'=>'g'],
                ['name'=>'Sugar',          'amount'=>$get('sugars'),        'unit'=>'g'],
                ['name'=>'Sodium',         'amount'=>$get('sodium'),        'unit'=>'mg'],
                ['name'=>'Calcium',        'amount'=>$get('calcium'),       'unit'=>'mg'],
                ['name'=>'Iron',           'amount'=>$get('iron'),          'unit'=>'mg'],
                ['name'=>'Potassium',      'amount'=>$get('potassium'),     'unit'=>'mg'],
                ['name'=>'Cholesterol',    'amount'=>$get('cholesterol'),   'unit'=>'mg'],
            ];
            $nutrients = array_values(array_filter($nutrients, fn($n) => $n['amount'] !== null));
            $servingText = null;
            if (isset($src['servingSize'], $src['servingSizeUnit'])) {
                $servingText = $src['servingSize'].' '.$src['servingSizeUnit'];
            } elseif (isset($src['householdServingFullText'])) {
                $servingText = $src['householdServingFullText'];
            }
            return $nutrients ? ['nutrients'=>$nutrients, 'servings'=>['original'=>$servingText ?? 'per serving']] : null;
        }

        // FDC foodNutrients
        if (isset($src['foodNutrients']) && is_array($src['foodNutrients'])) {
            $core = [
                'Energy'                          => ['Calories','kcal'],
                'Energy (Atwater General Factors)'=> ['Calories','kcal'],
                'Protein'                         => ['Protein','g'],
                'Total lipid (fat)'               => ['Fat','g'],
                'Carbohydrate, by difference'     => ['Carbohydrates','g'],
                'Fiber, total dietary'            => ['Fiber','g'],
                'Sugars, total including NLEA'    => ['Sugar','g'],
                'Sugars, total'                   => ['Sugar','g'],
                'Sodium, Na'                      => ['Sodium','mg'],
                'Calcium, Ca'                     => ['Calcium','mg'],
                'Iron, Fe'                        => ['Iron','mg'],
                'Potassium, K'                    => ['Potassium','mg'],
                'Cholesterol'                     => ['Cholesterol','mg'],
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
                    $bucket[$out['name']] = $out; // de-dup core
                } else {
                    $extras[] = $out; // keep other vitamins/minerals too
                }
            }
            // merge: core first, then extras
            $list = array_values($bucket);
            foreach ($extras as $ex) {
                // avoid duplicates by name
                if (!isset($bucket[$ex['name']])) $list[] = $ex;
            }
            if ($list) {
                $servingText = null;
                if (isset($src['servingSize'], $src['servingSizeUnit'])) {
                    $servingText = $src['servingSize'].' '.$src['servingSizeUnit'];
                } elseif (isset($src['householdServingFullText'])) {
                    $servingText = $src['householdServingFullText'];
                }
                // Cap to avoid overlong lists
                if (count($list) > 200) $list = array_slice($list, 0, 200);
                return ['nutrients'=>$list, 'servings'=>['original'=>$servingText ?? 'per 100 g']];
            }
        }

        // OFF nutriments
        $nutriments = $src['nutriments'] ?? null;
        if (!$nutriments && $this->looksLikeOffNutriments($src)) {
            $nutriments = $src;
        }
        if (is_array($nutriments)) {
            $pick = function(string $base) use ($nutriments) {
                if (isset($nutriments[$base.'_serving'])) return ['v'=>(float)$nutriments[$base.'_serving'], 'scope'=>'per serving'];
                if (isset($nutriments[$base.'_100g']))    return ['v'=>(float)$nutriments[$base.'_100g'],    'scope'=>'per 100 g'];
                return null;
            };
            $unit = function(string $base, string $default) use ($nutriments) {
                $k = $base.'_unit';
                return isset($nutriments[$k]) && is_string($nutriments[$k]) ? $nutriments[$k] : $default;
            };
            $map = [
                ['Calories',      'energy-kcal',  'kcal'],
                ['Protein',       'proteins',     'g'],
                ['Fat',           'fat',          'g'],
                ['Saturated Fat', 'saturated-fat','g'],
                ['Carbohydrates', 'carbohydrates','g'],
                ['Fiber',         'fiber',        'g'],
                ['Sugar',         'sugars',       'g'],
                ['Sodium',        'sodium',       'mg'],
                ['Calcium',       'calcium',      'mg'],
                ['Iron',          'iron',         'mg'],
                ['Potassium',     'potassium',    'mg'],
            ];
            $scope = null; $out = [];
            $taken = [];
            foreach ($map as [$name, $base, $defUnit]) {
                $picked = $pick($base);
                if ($picked) {
                    $out[] = ['name'=>$name, 'amount'=>$picked['v'], 'unit'=>$unit($base, $defUnit)];
                    $scope = $scope ?? $picked['scope'];
                    $taken[$base] = true;
                }
            }
            // Generic pass: include any other nutriments *_serving or *_100g
            foreach ($nutriments as $key => $val) {
                if (!is_scalar($val)) continue;
                if (preg_match('/^(.+?)_(serving|100g)$/', (string)$key, $m)) {
                    $base = $m[1];
                    if (isset($taken[$base])) continue;
                    $v = (float)$val; if (!is_finite($v)) continue;
                    $nm = ucwords(str_replace(['-', '_'], [' ', ' '], $base));
                    $out[] = ['name'=>$nm, 'amount'=>$v, 'unit'=>$unit($base, '')];
                }
            }
            if ($out) {
                if (count($out) > 200) $out = array_slice($out, 0, 200);
                return ['nutrients'=>$out, 'servings'=>['original'=>$scope ?? 'per 100 g']];
            }
        }

        // Flat label-like structure (e.g., ['calories'=>['value'=>120], 'fat'=>['value'=>1], ...])
        $flatKeys = ['calories','protein','fat','saturatedFat','transFat','carbohydrates','fiber','sugars','sodium','calcium','iron','potassium','cholesterol','addedSugars'];
        $hasFlat = false;
        foreach ($flatKeys as $k) {
            if (isset($src[$k]) && is_array($src[$k]) && array_key_exists('value', $src[$k])) { $hasFlat = true; break; }
        }
        if ($hasFlat) {
            $get = function(string $k) use ($src) { return isset($src[$k]['value']) ? (float)$src[$k]['value'] : null; };
            $nutrients = [
                ['name'=>'Calories',       'amount'=>$get('calories'),      'unit'=>'kcal'],
                ['name'=>'Protein',        'amount'=>$get('protein'),       'unit'=>'g'],
                ['name'=>'Fat',            'amount'=>$get('fat'),           'unit'=>'g'],
                ['name'=>'Saturated Fat',  'amount'=>$get('saturatedFat'),  'unit'=>'g'],
                ['name'=>'Trans Fat',      'amount'=>$get('transFat'),      'unit'=>'g'],
                ['name'=>'Carbohydrates',  'amount'=>$get('carbohydrates'), 'unit'=>'g'],
                ['name'=>'Fiber',          'amount'=>$get('fiber'),         'unit'=>'g'],
                ['name'=>'Sugar',          'amount'=>$get('sugars'),        'unit'=>'g'],
                ['name'=>'Added Sugars',   'amount'=>$get('addedSugars'),   'unit'=>'g'],
                ['name'=>'Sodium',         'amount'=>$get('sodium'),        'unit'=>'mg'],
                ['name'=>'Calcium',        'amount'=>$get('calcium'),       'unit'=>'mg'],
                ['name'=>'Iron',           'amount'=>$get('iron'),          'unit'=>'mg'],
                ['name'=>'Potassium',      'amount'=>$get('potassium'),     'unit'=>'mg'],
                ['name'=>'Cholesterol',    'amount'=>$get('cholesterol'),   'unit'=>'mg'],
            ];
            $nutrients = array_values(array_filter($nutrients, fn($n) => $n['amount'] !== null));
            return $nutrients ? ['nutrients'=>$nutrients, 'servings'=>['original'=>'per serving']] : null;
        }

        return null;
    }

    private function looksLikeOffNutriments(array $a): bool
    {
        foreach (['energy-kcal_100g','fat_100g','proteins_100g','carbohydrates_100g'] as $k) {
            if (array_key_exists($k, $a)) return true;
        }
        return false;
    }
}
