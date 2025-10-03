<?php

namespace Controllers;

use Helpers\Validator;
use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;
use Models\Recipes;
use Services\FoodService;

class ItemsController
{
    protected Items $items;
    protected Ingredients $ingredients;
    protected Products $products;
    protected FoodService $svc;

    public function __construct()
    {
        $this->items = new Items();
        $this->ingredients = new Ingredients();
        $this->products = new Products();
        $this->svc = new FoodService();
    }

    public function index(): string
    {
        try {
            // 1. Get Inputs for Pagination
            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $itemsPerPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 15;

            // 2. Get the Authenticated User's ID
            $userId = $_SESSION['user_id'];

            // 3. Fetch
            $results = $this->items->findAll($userId, $currentPage, $itemsPerPage);

            // 4. Render
            $data = [
                'title' => 'My Pantry',
                'items' => $results['items'],
                'pagination' => $results['pagination']
            ];

            return View::render('Items/index', $data);

        } catch (\PDOException $e) {
            error_log("Database Error in ItemsController::index(): " . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    public function create(): string
    {
        return View::render('Items/create', [
            'title' => 'Add Item',
            'errors' => [],
            'input' => [],
        ]);
    }

    public function store()
    {
        // Slim controller: delegate based on api_kind
        $kind = $_POST['api_kind'] ?? 'ingredient';
        if ($kind === 'product') {
            $ctrl = new ProductsController();
            return $ctrl->store();
        }
        // treat manual as ingredient flow (manual ingredient creation)
        if ($kind === 'manual') {
            // Force a manual confirm path via IngredientsController
            $_POST['api_kind'] = 'manual';
            $_POST['picked_source'] = 'manual';
            $_POST['api_id'] = 0;
            $_POST['original_input'] = $_POST;
            $ctrl = new IngredientsController();
            return $ctrl->confirm();
        }
        $ctrl = new IngredientsController();
        return $ctrl->store();
    }

    /** POST /items/confirm (finalize selection) */
    public function confirm()
    {
        // Slim controller: delegate based on api_kind (POST or original_input)
        $apiKind = $_POST['api_kind'] ?? ($_POST['original_input']['api_kind'] ?? 'ingredient');
        if ($apiKind === 'product') {
            $ctrl = new ProductsController();
            return $ctrl->confirm();
        }
        // manual treated in ingredient flow
        $ctrl = new IngredientsController();
        return $ctrl->confirm();
    }

    // If you still have a route pointing to storeConfirmed(), keep this shim:
    public function storeConfirmed(): ?string
    {
        return $this->confirm();
    }

    public function show(int $id): string
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }

            $itemRow = $this->items->find($id);
            if (!$itemRow || (int)$itemRow['user_id'] !== (int)$userId) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }

            // If this is an ingredient-backed item, delegate to the IngredientsController show route
            if (!empty($itemRow['ingredient_id'])) {
                header('Location: /ingredients/view/' . (int)$id);
                exit;
            }

            // Build a unified $row structure without Items doing cross-table joins
            $ing = null; $prod = null;
            if (!empty($itemRow['ingredient_id'])) {
                $ingModel = new Ingredients();
                $ing = $ingModel->find((int)$itemRow['ingredient_id']);
            }
            if (!empty($itemRow['product_id'])) {
                $prodModel = new Products();
                $prod = $prodModel->find((int)$itemRow['product_id']);
            }

            $row = $itemRow; // start with items columns (quantity, dates, etc.)
            if ($ing) {
                $row['ingredient_name'] = $ing['name'] ?? null;
                $row['ingredient_category'] = $ing['category'] ?? null;
                $row['ingredient_image_url'] = $ing['image_url'] ?? null;
                $row['ingredient_nutrition_info'] = $ing['nutrition_info'] ?? null;
                // legacy plain key sometimes used
                $row['nutrition_info'] = $ing['nutrition_info'] ?? null;
            }
            if ($prod) {
                $row['product_title'] = $prod['title'] ?? null;
                $row['product_brand'] = $prod['brand'] ?? null;
                $row['product_category'] = $prod['category'] ?? null;
                $row['product_image_url'] = $prod['image_url'] ?? null;
                $row['product_upc'] = $prod['upc'] ?? null;
                $row['product_nutrition_info'] = $prod['nutrition_info'] ?? null;
                $row['product_raw_payload'] = $prod['raw_payload'] ?? null;
            }

            // ----- status / badge
            $status = 'In Stock';
            $badge  = 'badge-success';
            if (!empty($row['expiration_date'])) {
                try {
                    $today    = new \DateTimeImmutable('today');
                    $exp      = new \DateTimeImmutable($row['expiration_date']);
                    $diffDays = (int)$today->diff($exp)->format('%r%a');
                    if     ($diffDays < 0) { $status = 'Expired ' . (abs($diffDays) === 1 ? '1 day ago' : abs($diffDays) . ' days ago'); $badge = 'badge-danger'; }
                    elseif ($diffDays === 0){ $status = 'Expires today'; $badge = 'badge-warning'; }
                    elseif ($diffDays <= 3){ $status = 'Expires in ' . ($diffDays === 1 ? '1 day' : $diffDays . ' days'); $badge = 'badge-warning'; }
                    else                    { $status = 'Expires in ' . $diffDays . ' days'; $badge = 'badge-neutral'; }
                } catch (\Exception $e) {}
            }

            // ----- nutrition (ingredient first)
            $nutrition = null;
            $rawNutri = null;

            // Ingredients: prefer ingredient_nutrition_info; also accept plain 'nutrition_info'
            $ingNutri = $row['ingredient_nutrition_info']
                ?? $row['nutrition_info']
                ?? null;

            if ($ingNutri) {
                $decoded = is_array($ingNutri) ? $ingNutri : json_decode((string)$ingNutri, true);
                // Flexible decode: handle escaped or double-encoded JSON stored in DB
                if (!is_array($decoded)) {
                    $s = (string)$ingNutri;
                    // remove common escaping
                    $stripped = stripslashes($s);
                    $decoded = json_decode($stripped, true);
                    if (!is_array($decoded)) {
                        // Sometimes JSON is string inside JSON {"nutrition":"{...}"}
                        $once = json_decode($s, true);
                        if (is_string($once)) {
                            $decoded = json_decode($once, true);
                        } elseif (is_array($once)) {
                            // find first large JSON-ish string value
                            foreach ($once as $vv) {
                                if (is_string($vv) && strlen($vv) > 10 && ($vv[0] === '{' || $vv[0] === '[')) {
                                    $decoded = json_decode($vv, true);
                                    if (is_array($decoded)) break;
                                }
                            }
                        }
                    }
                }
                if (is_array($decoded)) {
                    $rawNutri = $decoded;
                    $nutrition = $this->normalizeNutrition($decoded);
                }
            }

            // Products (if no nutrition yet): try OFF raw payload, then product_nutrition_info
            $productRaw = null;
            if ($nutrition === null && !empty($row['product_raw_payload'])) {
                $productRaw = is_array($row['product_raw_payload'])
                    ? $row['product_raw_payload']
                    : json_decode((string)$row['product_raw_payload'], true);

                if (is_array($productRaw) && isset($productRaw['product']) && is_array($productRaw['product'])) {
                    $productRaw = $productRaw['product']; // OFF embeds under 'product'
                }
                if (is_array($productRaw)) {
                    // normalize a few common keys used by the view
                    if (!isset($productRaw['brand']) && isset($productRaw['brands'])) {
                        $productRaw['brand'] = $productRaw['brands'];
                    }
                    if (!isset($productRaw['upc'])) {
                        $productRaw['upc'] = $productRaw['code'] ?? ($row['product_upc'] ?? null);
                    }
                    if (!isset($productRaw['image']) && isset($productRaw['image_url'])) {
                        $productRaw['image'] = $productRaw['image_url'];
                    }
                    // OFF nutriments → nutrition
                    $nutrition = $this->normalizeNutrition($productRaw);
                }
            }

            if ($nutrition === null && !empty($row['product_nutrition_info'])) {
                $pn = is_array($row['product_nutrition_info'])
                    ? $row['product_nutrition_info']
                    : json_decode((string)$row['product_nutrition_info'], true);
                if (is_array($pn)) {
                    $nutrition = $this->normalizeNutrition($pn);
                }
            }

            // ----- display fields (prefer ingredient, then product)
            $displayName = $row['ingredient_name'] ?? ($row['product_title'] ?? 'Item');

            // Category may be a JSON array / path → stringify safely
            $catRaw = $row['ingredient_category'] ?? ($row['product_category'] ?? null);
            $displayCategory = $this->stringifyCategory($catRaw);

            $displayImage = $row['ingredient_image_url']
                ?? ($row['product_image_url'] ?? ($productRaw['image'] ?? null));

            $item = [
                'id'              => (int)$row['id'],
                'name'            => $displayName,
                'category'        => $displayCategory,
                'image'           => $displayImage,
                'quantity'        => $row['quantity'] ?? null,
                'unit'            => $row['unit'] ?? null,
                'purchase_date'   => $row['purchase_date'] ?? null,
                'expiration_date' => $row['expiration_date'] ?? null,
                'status'          => $status,
                'badge_class'     => $badge,
                'nutrition'       => $nutrition,
                'nutrition_raw'   => $rawNutri,

                // Brand: prefer product brand, else ingredient brand, else entered brand
                'brand'           => $row['product_brand'] ?? ($ing['brand'] ?? ($row['entered_brand'] ?? null)),
                'product_title'   => $row['product_title'] ?? null,
                'product_raw'     => $productRaw,
            ];

            $isIngredient = !empty($itemRow['ingredient_id']);
            $view = $isIngredient ? 'Ingredients/show' : 'Products/show';
            $title = $isIngredient ? 'Ingredient Details' : 'Product Details';
            return View::render($view, [
                'title' => $title,
                'item'  => $item,
                                // Preload related recipes locally by the item's display name
                                'recipesList' => (function() use ($item) {
                                    $list = [];
                                    try {
                                        $term = isset($item['name']) ? (string)$item['name'] : '';
                                        if ($term !== '') {
                                            // Normalize the term similarly to RecipesController::normalizePantryTerm
                                            $t = trim($term);
                                            $t = preg_replace("/^['\"]+|['\"]+$/", '', (string)$t);
                                            $t = preg_replace('/\([^)]+\)/', '', $t);
                                            if (strpos($t, ',') !== false) { $parts = array_map('trim', explode(',', $t)); foreach ($parts as $p) { if ($p !== '') { $t = $p; break; } } }
                                            $t = str_replace(['–','—','-','/'], ' ', $t);
                                            $t = strtolower($t);
                                            $stop = ['raw','california','with','and','or','value','pack','bottle','bottles','enhancing','minerals','purified','drinking','boneless','skinless','shredded','sliced','ground','fresh','large','small','organic','original','classic'];
                                            $tokens = preg_split('/\s+/', $t);
                                            $clean = [];
                                            foreach ($tokens as $tok) {
                                                $tok = trim($tok);
                                                if ($tok === '') continue;
                                                if (preg_match('/^\d+[a-zA-Z-]*$/', $tok)) continue;
                                                if (in_array($tok, $stop, true)) continue;
                                                $clean[] = $tok;
                                            }
                                            if ($clean) { $t = implode(' ', $clean); } else { $t = trim(preg_replace('/\s+/', ' ', $t)); }
                                            $map = [
                                                'milk chocolate' => 'chocolate',
                                                'milk chocolate chips' => 'chocolate',
                                                'semi sweet chocolate' => 'chocolate',
                                                'semisweet chocolate' => 'chocolate',
                                                'dark chocolate' => 'chocolate',
                                                'white chocolate' => 'chocolate',
                                                'chocolate chips' => 'chocolate',
                                                'honeycrisp apples' => 'apple',
                                                'honeycrisp apple' => 'apple',
                                            ];
                                            if (isset($map[$t])) { $t = $map[$t]; }
                                            else {
                                                if (str_contains($t, 'apple')) {
                                                    $appleCultivars = ['honeycrisp','gala','fuji','granny smith','pink lady','ambrosia','mcintosh','golden delicious','red delicious','braeburn','jonagold'];
                                                    foreach ($appleCultivars as $cv) {
                                                        if (str_starts_with($t, $cv.' ') || $t === $cv || str_starts_with($t, $cv.' apple') || str_starts_with($t, $cv.' apples')) { $t = 'apple'; break; }
                                                    }
                                                    if ($t === 'apples') $t = 'apple';
                                                }
                                            }
                                            $t = trim(preg_replace('/\s+/', ' ', $t));
                                            if ($t === '') { return []; }

                                            $model = new Recipes();
                                            // Local search by ingredient keyword (AND with single term)
                                            $local = $model->findByIngredientsLocal([$t], 6, true);
                                            $list = $local;

                                            // If underfilled, top up from Suggestic API if configured
                                            if (count($list) < 6) {
                                                $needed = 6 - count($list);
                                                try {
                                                    $prov = new \Services\Recipes\SuggesticProvider();
                                                    if ($prov->isConfigured()) {
                                                        $apiResults = $prov->findByIngredients([$t], $needed);
                                                        // De-dup by title|image and persist to DB for Details links
                                                        $seen = [];
                                                        foreach ($list as $r0) {
                                                            $k0 = strtolower(trim(($r0['title'] ?? '').'|'.($r0['image'] ?? '')));
                                                            if ($k0 !== '') $seen[$k0] = true;
                                                        }
                                                        foreach ($apiResults as $r) {
                                                            $k = strtolower(trim(($r['title'] ?? '').'|'.($r['image'] ?? '')));
                                                            if ($k === '' || isset($seen[$k])) continue;
                                                            try { $id = $model->upsertFromProvider($r, null, ($r['provider'] ?? 'suggestic')); $r['db_id'] = $id; } catch (\Throwable $e) { /* ignore */ }
                                                            $list[] = $r; $seen[$k] = true;
                                                            if (count($list) >= 6) break;
                                                        }
                                                    }
                                                } catch (\Throwable $e) { /* ignore provider errors */ }
                                            }
                                        }
                                    } catch (\Throwable $e) { $list = []; }
                                    return $list;
                                })(),
            ]);

        } catch (\Throwable $e) {
            error_log('ItemsController::show error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /** Stringify category which could be a string, JSON string, or array. */
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
            return $cat; // already a plain string
        }

        if (is_array($cat)) {
            // OFF sometimes gives category paths / arrays
            if (isset($cat['categoryPath']) && is_array($cat['categoryPath'])) {
                return implode(' › ', array_filter($cat['categoryPath'], 'is_string'));
            }
            // generic: collapse any stringy array
            $vals = [];
            foreach ($cat as $v) {
                if (is_string($v)) $vals[] = $v;
            }
            return $vals ? implode(' › ', $vals) : null;
        }

        return null;
    }

    /**
     * Normalize to:
     * ['nutrients' => [ ['name','amount','unit'], ... ],
     *  'servings'  => ['original' => '...']]
     *
     * Supports:
     * - Spoonacular-like structure (nutrients[])
     * - FDC labelNutrients
     * - FDC foodNutrients (Foundation/Survey/Branded)
     * - OFF nutriments (per serving or per 100g)
     */
    private function normalizeNutrition($src): ?array
    {
        if (!is_array($src) || !$src) return null;

        // If we received a bare FDC-style list of FoodNutrient entries, wrap it.
        if (isset($src[0]) && is_array($src[0]) && (isset($src[0]['nutrient']) || isset($src[0]['nutrientName']) || (isset($src[0]['type']) && $src[0]['type'] === 'FoodNutrient'))) {
            $src = ['foodNutrients' => $src];
        }

        // Already in target form?
        if (isset($src['nutrients']) && is_array($src['nutrients'])) {
            return $src;
        }

        // ---------- FDC: labelNutrients ----------
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

        // ---------- FDC: foodNutrients ----------
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
                    $bucket[$out['name']] = $out;
                } else {
                    $extras[] = $out;
                }
            }
            $list = array_values($bucket);
            foreach ($extras as $ex) {
                if (!isset($bucket[$ex['name']])) $list[] = $ex;
            }
            if ($list) {
                $servingText = null;
                if (isset($src['servingSize'], $src['servingSizeUnit'])) {
                    $servingText = $src['servingSize'].' '.$src['servingSizeUnit'];
                } elseif (isset($src['householdServingFullText'])) {
                    $servingText = $src['householdServingFullText'];
                }
                if (count($list) > 200) $list = array_slice($list, 0, 200);
                return ['nutrients'=>$list, 'servings'=>['original'=>$servingText ?? 'per 100 g']];
            }
        }

        // ---------- OFF: nutriments ----------
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

        // Flat label-like structure
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


    public function validation(): ?string
    {
        global $conn;
        $validator = new Validator($_POST, $conn);

        $rules = [
            'name' => ['required' => true, 'min' => 2, 'max' => 255],
            'quantity' => ['required' => true, 'numeric' => true],
            'unit' => ['required' => false, 'max' => 10, 'string' => true],
            'purchase_date' => ['required' => false, 'date' => true],
            'expiration_date' => ['required' => false, 'date' => true]
        ];

        $validator->check($rules);

        // Cross-field validation: expiration_date not before purchase_date
        $errors = $validator->errors();
        $pd = $_POST['purchase_date'] ?? null;
        $ed = $_POST['expiration_date'] ?? null;
        if (!empty($pd) && !empty($ed)) {
            $pdObj = \DateTime::createFromFormat('Y-m-d', (string)$pd);
            $edObj = \DateTime::createFromFormat('Y-m-d', (string)$ed);
            if ($pdObj && $edObj && $edObj < $pdObj) {
                $errors['expiration_date'] = 'Expiration date cannot be before the purchase date';
            }
        }

        if (!empty($errors)) {
            return View::render('Items/create', [
                'title' => 'Add New Item',
                'errors' => $errors,
                'input' => $_POST
            ]);
        }

        return null; // success
    }

    public function edit(int $id): string
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $row = $this->items->findWithGlobalById($id, (int)$userId);
            if (!$row) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }
            // Prepare item fields for form
            $item = [
                'id' => (int)$row['id'],
                'quantity' => $row['quantity'] ?? '1',
                'unit' => $row['unit'] ?? '',
                'purchase_date' => $row['purchase_date'] ?? null,
                'expiration_date' => $row['expiration_date'] ?? null,
                'entered_name' => $row['entered_name'] ?? null,
                'entered_brand' => $row['entered_brand'] ?? null,
            ];
            // Display name/category/image for context
            $name = $row['ingredient_name'] ?? ($row['product_title'] ?? ($row['entered_name'] ?? 'Item'));
            $category = $this->stringifyCategory($row['ingredient_category'] ?? ($row['product_category'] ?? null));
            $image = $row['ingredient_image_url'] ?? ($row['product_image_url'] ?? null);
            return View::render('Items/edit', [
                'title' => 'Edit Item',
                'item' => $item,
                'display' => [
                    'name' => $name,
                    'category' => $category,
                    'image' => $image,
                ],
                'errors' => [],
            ]);
        } catch (\Throwable $e) {
            error_log('ItemsController::edit error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    public function update(int $id)
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            // Basic validation
            $data = [
                'quantity' => isset($_POST['quantity']) ? (string)$_POST['quantity'] : null,
                'unit' => $_POST['unit'] ?? null,
                'purchase_date' => $_POST['purchase_date'] ?? null,
                'expiration_date' => $_POST['expiration_date'] ?? null,
                'entered_name' => $_POST['entered_name'] ?? null,
                'entered_brand' => $_POST['entered_brand'] ?? null,
            ];
            // Clean empty strings to nulls
            foreach ($data as $k => $v) {
                if ($v === '') $data[$k] = null;
            }

            $errors = [];
            // Quantity numeric
            if (!is_numeric($data['quantity'] ?? null)) {
                $errors['quantity'] = 'Quantity must be numeric';
            }
            // Unit max length 10
            if (isset($data['unit']) && $data['unit'] !== null && strlen((string)$data['unit']) > 10) {
                $errors['unit'] = 'Unit must be 10 characters or fewer';
            }
            // Dates format and logical order
            $pd = $data['purchase_date'] ?? null;
            $ed = $data['expiration_date'] ?? null;
            $pdObj = null; $edObj = null;
            if ($pd !== null) {
                $pdObj = \DateTime::createFromFormat('Y-m-d', (string)$pd);
                if (!$pdObj || $pdObj->format('Y-m-d') !== (string)$pd) {
                    $errors['purchase_date'] = 'Purchase date must be a valid date (YYYY-MM-DD)';
                }
            }
            if ($ed !== null) {
                $edObj = \DateTime::createFromFormat('Y-m-d', (string)$ed);
                if (!$edObj || $edObj->format('Y-m-d') !== (string)$ed) {
                    $errors['expiration_date'] = 'Expiration date must be a valid date (YYYY-MM-DD)';
                }
            }
            if ($pdObj && $edObj && $edObj < $pdObj) {
                $errors['expiration_date'] = 'Expiration date cannot be before the purchase date';
            }

            if (!empty($errors)) {
                return View::render('Items/edit', [
                    'title' => 'Edit Item',
                    'errors' => $errors,
                    'item' => array_merge(['id'=>$id], $data),
                    'display' => ['name' => $_POST['display_name'] ?? 'Item', 'category' => $_POST['display_category'] ?? null, 'image' => $_POST['display_image'] ?? null],
                ]);
            }

            $ok = $this->items->update($id, $data, (int)$userId);
            if (!$ok) {
                return View::render('Items/edit', [
                    'title' => 'Edit Item',
                    'errors' => ['general' => 'No changes were made or update failed.'],
                    'item' => array_merge(['id'=>$id], $data),
                    'display' => ['name' => $_POST['display_name'] ?? 'Item', 'category' => $_POST['display_category'] ?? null, 'image' => $_POST['display_image'] ?? null],
                ]);
            }
            // Invalidate dashboard caches after successful update
            try {
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
            } catch (\Throwable $e) { /* ignore */ }
            header('Location: /items/view/' . (int)$id);
            exit;
        } catch (\Throwable $e) {
            error_log('ItemsController::update error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    public function destroy(int $id)
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            $item = $this->items->find($id);
            if (!$item) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }
            if ((int)($item['user_id'] ?? 0) !== (int)$userId) {
                http_response_code(403);
                return View::render('Pages/403', ['title' => 'Forbidden']);
            }

            $this->items->delete($id, (int)$userId);
            // Invalidate dashboard caches after delete
            try {
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
            } catch (\Throwable $e) { /* ignore */ }
            header('Location: /dashboard');
            exit;
        } catch (\Throwable $e) {
            error_log('ItemsController::destroy error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    public function renew(int $id)
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }
            // Ensure item exists and belongs to the user
            $item = $this->items->findWithGlobalById($id, (int)$userId);
            if (!$item) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }

            // Simple renew rule: set expiration_date to today + 7 days
            $today = new \DateTimeImmutable('today');
            $newExp = $today->modify('+7 days')->format('Y-m-d');

            $ok = $this->items->update($id, ['expiration_date' => $newExp], (int)$userId);
            if (!$ok) {
                // If nothing updated (e.g., same value), still redirect back to view
                header('Location: /items/view/' . (int)$id);
                exit;
            }

            // Invalidate caches after renew
            try {
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':items:recent:v1');
                \Helpers\Cache::del('pp:user:' . (int)$userId . ':dashboard:stats:v1');
            } catch (\Throwable $e) { /* ignore */ }

            header('Location: /items/view/' . (int)$id);
            exit;
        } catch (\Throwable $e) {
            error_log('ItemsController::renew error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }
}
