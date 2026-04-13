<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\ShoppingList;
use Services\FoodService;
use Services\Providers\FatSecretProvider;

class ShoppingListController
{
    protected ShoppingList $list;
    protected Items $items;
    protected FoodService $svc;

    public function __construct()
    {
        $this->list  = new ShoppingList();
        $this->items = new Items();
        $this->svc   = new FoodService();
    }

    /** GET /shopping-list — show the user's full list */
    public function index(): string
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                return View::render('Pages/401', ['title' => 'Unauthorized']);
            }

            $rows  = $this->list->findAllForUser($userId);
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);

            // Group by recipe title (null → manually added)
            $groups = [];
            foreach ($rows as $row) {
                $key = $row['recipe_title'] ?? '';
                $groups[$key][] = $row;
            }

            return View::render('ShoppingList/index', [
                'title'  => 'Shopping List',
                'groups' => $groups,
                'total'  => count($rows),
                'flash'  => $flash,
            ]);
        } catch (\Throwable $e) {
            error_log('ShoppingListController::index error: ' . $e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }

    /**
     * POST /shopping-list/add-from-recipe
     * Adds the missing (non-pantry) ingredients from a recipe to the list.
     * Expects: ingredients[] (array of strings), recipe_id (int), recipe_title (string)
     */
    public function addFromRecipe(): void
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                header('Location: /login');
                exit;
            }

            $rawIngredients = $_POST['ingredients'] ?? [];
            $recipeId       = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : null;
            $recipeTitle    = trim((string)($_POST['recipe_title'] ?? ''));

            if (!is_array($rawIngredients) || empty($rawIngredients)) {
                $_SESSION['flash'] = ['type' => 'info', 'message' => 'No missing ingredients to add.'];
                $back = $recipeId ? "/recipes/view/{$recipeId}" : '/shopping-list';
                header("Location: {$back}");
                exit;
            }

            $added = 0;
            foreach ($rawIngredients as $ing) {
                $name = trim((string)$ing);
                if ($name === '') continue;
                $this->list->create($userId, $name, null, $recipeId ?: null, $recipeTitle ?: null);
                $added++;
            }

            $msg = $added === 1 ? '1 ingredient added to your shopping list.'
                                : "{$added} ingredients added to your shopping list.";
            $_SESSION['flash'] = ['type' => 'success', 'message' => $msg];

            $back = $recipeId ? "/recipes/view/{$recipeId}" : '/shopping-list';
            header("Location: {$back}");
            exit;
        } catch (\Throwable $e) {
            error_log('ShoppingListController::addFromRecipe error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong. Please try again.'];
            header('Location: /shopping-list');
            exit;
        }
    }

    /**
     * POST /shopping-list/item/add
     * Manually add a single item.
     * Expects: name (string), quantity (string, optional)
     */
    public function addItem(): void
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                header('Location: /login');
                exit;
            }

            $name     = trim((string)($_POST['name'] ?? ''));
            $quantity = trim((string)($_POST['quantity'] ?? '')) ?: null;

            if ($name === '') {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Item name cannot be empty.'];
                header('Location: /shopping-list');
                exit;
            }

            $this->list->create($userId, $name, $quantity);
            $_SESSION['flash'] = ['type' => 'success', 'message' => "'{$name}' added to your list."];
        } catch (\Throwable $e) {
            error_log('ShoppingListController::addItem error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong.'];
        }

        header('Location: /shopping-list');
        exit;
    }

    /**
     * POST /shopping-list/item/{id}/update
     * Update name / quantity of a list item.
     * Expects: name (string), quantity (string, optional)
     */
    public function update(int $id): void
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                header('Location: /login');
                exit;
            }

            $name     = trim((string)($_POST['name'] ?? ''));
            $quantity = trim((string)($_POST['quantity'] ?? '')) ?: null;

            if ($name === '') {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Item name cannot be empty.'];
                header('Location: /shopping-list');
                exit;
            }

            $this->list->update($id, $userId, $name, $quantity);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item updated.'];
        } catch (\Throwable $e) {
            error_log('ShoppingListController::update error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong.'];
        }

        header('Location: /shopping-list');
        exit;
    }

    /**
     * POST /shopping-list/item/{id}/delete
     * Remove an item from the list.
     */
    public function delete(int $id): void
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                header('Location: /login');
                exit;
            }

            $this->list->delete($id, $userId);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item removed.'];
        } catch (\Throwable $e) {
            error_log('ShoppingListController::delete error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong.'];
        }

        header('Location: /shopping-list');
        exit;
    }

    /**
     * POST /shopping-list/item/{id}/move-to-pantry
     * Shows a modal (view-side) then processes the form.
     * Expects: quantity (string, optional), unit (string, optional), brand (string, optional)
     * Tries to resolve the ingredient via FDC; falls back to entered_name.
     */
    public function moveToPantry(int $id): void
    {
        try {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            if (!$userId) {
                http_response_code(401);
                header('Location: /login');
                exit;
            }

            $item = $this->list->find($id, $userId);
            if (!$item) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Item not found.'];
                header('Location: /shopping-list');
                exit;
            }

            $quantity = trim((string)($_POST['quantity'] ?? '')) ?: null;
            $unit     = trim((string)($_POST['unit'] ?? '')) ?: null;
            $brand    = trim((string)($_POST['brand'] ?? '')) ?: null;

            // Strip leading measurement from ingredient string to get a clean search term
            // e.g. "2 cups all-purpose flour" → "all-purpose flour"
            $rawName   = $item['name'];
            $cleanName = preg_replace(
                '/^\d[\d\/\. ]*\s*(cup|cups|tbsp|tsp|tablespoon|teaspoon|lb|lbs|oz|ounce|ounces|g|gram|grams|kg|ml|liter|liters|pound|pounds|clove|cloves|slice|slices|piece|pieces|can|cans|bunch|bunches|pinch|dash)s?\b[\s,]*/i',
                '',
                $rawName
            );
            $searchTerm = trim($cleanName) ?: $rawName;

            $ingredientId = null;
            try {
                $results = $this->svc->searchWithKind($searchTerm, 'ingredient', $brand, 1);
                if (!empty($results[0]['api_id'])) {
                    $ing = $this->svc->ensureIngredientFromApi($results[0]['api_id'], $searchTerm, $brand);
                    if ($ing && !empty($ing['id'])) {
                        $ingredientId = (int)$ing['id'];
                    }
                }
            } catch (\Throwable $e) {
                error_log('ShoppingListController::moveToPantry FDC lookup failed: ' . $e->getMessage());
                // Non-fatal — fall through to entered_name path
            }

            $this->items->create([
                'user_id'         => $userId,
                'ingredient_id'   => $ingredientId,
                'product_id'      => null,
                'quantity'        => $quantity,
                'unit'            => $unit,
                'purchase_date'   => date('Y-m-d'),
                'expiration_date' => null,
                'entered_name'    => $ingredientId ? null : $rawName,
                'entered_brand'   => $brand,
            ]);

            $this->list->delete($id, $userId);

            $_SESSION['flash'] = ['type' => 'success', 'message' => "'{$rawName}' added to your pantry."];
        } catch (\Throwable $e) {
            error_log('ShoppingListController::moveToPantry error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Something went wrong. Please try again.'];
        }

        header('Location: /shopping-list');
        exit;
    }

    /**
     * POST /api/shopping/scan-barcode
     * Looks up a barcode via the FatSecret Premier barcode API and checks whether
     * the resolved food already exists on the user's shopping list.
     * Returns JSON.
     */
    public function scanBarcode(): void
    {
        header('Content-Type: application/json');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $barcode = trim((string)($_POST['barcode'] ?? ''));
        if ($barcode === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Barcode is required']);
            exit;
        }

        try {
            $fs   = new FatSecretProvider();
            $data = $fs->findFoodByBarcode($barcode);

            if (!$data || isset($data['error'])) {
                echo json_encode(['found' => false, 'message' => 'Product not found for this barcode.']);
                exit;
            }

            $food = $data['food'] ?? null;
            if (!$food) {
                echo json_encode(['found' => false, 'message' => 'Product not found for this barcode.']);
                exit;
            }

            $foodName  = (string)($food['food_name'] ?? '');
            $brandName = (string)($food['brand_name'] ?? '');

            // Check if any shopping list item fuzzy-matches the scanned food name
            $listItems    = $this->list->findAllForUser($userId);
            $inList       = false;
            $listItemId   = null;
            $listItemName = '';

            $needle = strtolower($foodName);
            foreach ($listItems as $listItem) {
                $haystack = strtolower($listItem['name']);
                if (str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
                    $inList       = true;
                    $listItemId   = (int)$listItem['id'];
                    $listItemName = $listItem['name'];
                    break;
                }
            }

            echo json_encode([
                'found'          => true,
                'food_name'      => $foodName,
                'brand_name'     => $brandName,
                'in_list'        => $inList,
                'list_item_id'   => $listItemId,
                'list_item_name' => $listItemName,
            ]);
        } catch (\Throwable $e) {
            error_log('ShoppingListController::scanBarcode error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Something went wrong. Please try again.']);
        }
        exit;
    }

    /**
     * POST /api/shopping/scanned-to-pantry
     * Adds a barcode-scanned item directly to the pantry when it was not on the shopping list.
     * Attempts an FDC ingredient lookup; falls back to entered_name just like moveToPantry.
     * Returns JSON.
     */
    public function addScannedToPantry(): void
    {
        header('Content-Type: application/json');

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $barcode   = trim((string)($_POST['barcode'] ?? ''));
        $foodName  = trim((string)($_POST['food_name'] ?? ''));
        $brandName = trim((string)($_POST['brand_name'] ?? '')) ?: null;
        $quantity  = trim((string)($_POST['quantity'] ?? '')) ?: null;
        $unit      = trim((string)($_POST['unit'] ?? '')) ?: null;

        if ($foodName === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Food name is required']);
            exit;
        }

        try {
            $productId = null;
            try {
                if ($barcode !== '') {
                    $prod = $this->svc->ensureProductFromApi($barcode, $foodName, $brandName);
                    if ($prod && !empty($prod['id'])) {
                        $productId = (int)$prod['id'];
                    }
                }
                
                if (!$productId) {
                    $results = $this->svc->searchWithKind($foodName, 'product', $brandName, 1);
                    if (!empty($results[0]['api_id'])) {
                        $prod = $this->svc->ensureProductFromApi($results[0]['api_id'], $foodName, $brandName);
                        if ($prod && !empty($prod['id'])) {
                            $productId = (int)$prod['id'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('ShoppingListController::addScannedToPantry OFF lookup failed: ' . $e->getMessage());
                // Non-fatal — fall through to entered_name path
            }

            $this->items->create([
                'user_id'         => $userId,
                'ingredient_id'   => null,
                'product_id'      => $productId,
                'quantity'        => $quantity,
                'unit'            => $unit,
                'purchase_date'   => date('Y-m-d'),
                'expiration_date' => null,
                'entered_name'    => $productId ? null : $foodName,
                'entered_brand'   => $brandName,
            ]);

            echo json_encode([
                'success' => true,
                'message' => "'{$foodName}' added to your pantry.",
            ]);
        } catch (\Throwable $e) {
            error_log('ShoppingListController::addScannedToPantry error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Something went wrong. Please try again.']);
        }
        exit;
    }
}
