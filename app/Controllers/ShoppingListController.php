<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\ShoppingList;
use Services\FoodService;

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
}
