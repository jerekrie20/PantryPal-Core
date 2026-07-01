<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Services\FoodService;
use Services\Nutrition\Normalizer as NutritionNormalizer;
use Services\Pantry\CategoryFormatter;
use Services\Recipes\RelatedRecipeFinder;

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

            if (($ing['api_source'] ?? '') === 'fatsecret' && !empty($ing['api_id'])) {
                $fsSvc = new \Services\FoodService();
                $fsData = $fsSvc->getFatSecretFood($ing['api_id']);
                if ($fsData && isset($fsData['food'])) {
                    $rawNutri = $fsData['food'];
                    $nutrition = NutritionNormalizer::normalize($rawNutri);
                }
            }

            $ingNutri = $ing['nutrition_info'] ?? null;
            if ($nutrition === null && $ingNutri) {
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
                    $nutrition = NutritionNormalizer::normalize($decoded);
                }
            }

            // Display fields
            $displayName = $ing['name'] ?? ($itemRow['entered_name'] ?? 'Ingredient');
            $displayCategory = CategoryFormatter::stringify($ing['category'] ?? null);
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
                'title'       => 'Ingredient Details',
                'item'        => $item,
                'recipesList' => (new RelatedRecipeFinder())->findByName($item['name'] ?? ''),
            ]);

        } catch (\Throwable $e) {
            error_log('IngredientsController::show error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }
}
