<?php

namespace Controllers;

use Helpers\Validator;
use Helpers\View;
use Models\Items;
use Models\Ingredients;
use Models\Products;
use Services\FoodService;
use Services\Nutrition\Normalizer as NutritionNormalizer;
use Services\Pantry\CategoryFormatter;
use Services\Recipes\RelatedRecipeFinder;

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

            $row = $this->items->find($id, (int)$userId);
            if (!$row) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }

            // If this is an ingredient-backed item, delegate to the IngredientsController show route
            if (!empty($row['ingredient_id'])) {
                header('Location: /ingredients/view/' . (int)$id);
                exit;
            }

            // ----- status / badge
            $statusData = $this->items->getExpirationStatus($row['expiration_date'] ?? null);
            $status = $statusData['status'];
            $badge = $statusData['badge'];

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
                    $nutrition = NutritionNormalizer::normalize($decoded);
                }
            }

            $productRaw = null;
            if ($nutrition === null && ($row['product_api_source'] ?? '') === 'fatsecret' && !empty($row['product_api_id'])) {
                $fsSvc = new \Services\FoodService();
                $fsData = $fsSvc->getFatSecretFood($row['product_api_id']);
                if ($fsData && isset($fsData['food'])) {
                    $productRaw = $fsData['food'];
                    $nutrition = NutritionNormalizer::normalize($productRaw);
                }
            }

            // Products (if no nutrition yet): try OFF raw payload, then product_nutrition_info
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
                    $nutrition = NutritionNormalizer::normalize($productRaw);
                }
            }

            if ($nutrition === null && !empty($row['product_nutrition_info'])) {
                $pn = is_array($row['product_nutrition_info'])
                    ? $row['product_nutrition_info']
                    : json_decode((string)$row['product_nutrition_info'], true);
                if (is_array($pn)) {
                    $nutrition = NutritionNormalizer::normalize($pn);
                }
            }

            // ----- display fields (prefer ingredient, then product)
            $displayName = $row['ingredient_name'] ?? ($row['product_title'] ?? 'Item');

            // Category may be a JSON array / path → stringify safely
            $displayCategory = CategoryFormatter::stringify($row['ingredient_category'] ?? ($row['product_category'] ?? null));

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
                'brand'           => $row['product_brand'] ?? ($row['ingredient_brand'] ?? ($row['entered_brand'] ?? null)),
                'product_title'   => $row['product_title'] ?? null,
                'product_raw'     => $productRaw,
            ];

            $isIngredient = !empty($row['ingredient_id']);
            $view = $isIngredient ? 'Ingredients/show' : 'Products/show';
            $title = $isIngredient ? 'Ingredient Details' : 'Product Details';
            return View::render($view, [
                'title'       => $title,
                'item'        => $item,
                'recipesList' => (new RelatedRecipeFinder())->findByName($item['name'] ?? ''),
            ]);

        } catch (\Throwable $e) {
            error_log('ItemsController::show error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
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
            $row = $this->items->find($id, (int)$userId);
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
            $category = CategoryFormatter::stringify($row['ingredient_category'] ?? ($row['product_category'] ?? null));
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
            $item = $this->items->find($id, (int)$userId);
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
