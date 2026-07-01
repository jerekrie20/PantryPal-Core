<?php

namespace Controllers;

use Controllers\Concerns\RunsPantryIntake;
use Helpers\View;
use Models\Items;
use Services\Pantry\CategoryFormatter;
use Services\Pantry\PantryCache;
use Services\Pantry\PantryItemAssembler;
use Services\Pantry\Sources\CatalogSource;
use Services\Pantry\Sources\IngredientSource;
use Services\Pantry\Sources\ProductSource;
use Services\Recipes\RelatedRecipeFinder;

class ItemsController
{
    use RunsPantryIntake;

    protected Items $items;

    public function __construct()
    {
        $this->items = new Items();
    }

    public function index(): string
    {
        try {
            // 1. Get Inputs for Pagination
            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $itemsPerPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 15;

            // 2. Get the Authenticated User's ID
            $userId = $_SESSION['user_id'];

            // 3. Fetch and assemble display items, split by kind for the tabs
            $results = $this->items->findAll($userId, $currentPage, $itemsPerPage);

            $ingredients = [];
            $products    = [];
            foreach ($results['items'] as $row) {
                $display = PantryItemAssembler::summary($row);
                if ($display['kind'] === 'product') {
                    $products[] = $display;
                } else {
                    $ingredients[] = $display;
                }
            }

            // 4. Render
            return View::render('Items/index', [
                'title'       => 'My Pantry',
                'ingredients' => $ingredients,
                'products'    => $products,
                'pagination'  => $results['pagination'],
            ]);

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

    public function store(): string
    {
        $kind = $_POST['api_kind'] ?? 'ingredient';

        // Manual skips the search/confirm round-trip entirely.
        if ($kind === 'manual') {
            if ($errorView = $this->pantryValidation($_POST)) {
                return $errorView;
            }
            return $this->completeIntake(new IngredientSource(), [
                'picked_source'  => 'manual',
                'original_input' => $_POST,
            ]);
        }

        return $this->beginIntake(
            $this->sourceFor($kind),
            $_POST,
            $kind === 'product' ? '/products/confirm' : '/ingredients/confirm'
        );
    }

    /** POST /items/confirm (finalize selection) */
    public function confirm(): string
    {
        $apiKind = $_POST['api_kind'] ?? ($_POST['original_input']['api_kind'] ?? 'ingredient');
        return $this->completeIntake($this->sourceFor($apiKind), $_POST);
    }

    // If you still have a route pointing to storeConfirmed(), keep this shim:
    public function storeConfirmed(): string
    {
        return $this->confirm();
    }

    /** Pick the catalog for an api_kind value; manual runs through ingredients. */
    private function sourceFor(string $kind): CatalogSource
    {
        return $kind === 'product' ? new ProductSource() : new IngredientSource();
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

            $item = (new PantryItemAssembler())->detail($row);

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
            PantryCache::bustForUser((int)$userId);
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
            PantryCache::bustForUser((int)$userId);
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

            PantryCache::bustForUser((int)$userId);

            header('Location: /items/view/' . (int)$id);
            exit;
        } catch (\Throwable $e) {
            error_log('ItemsController::renew error: '.$e->getMessage());
            http_response_code(500);
            return View::render('Pages/500', ['title' => 'Server Error']);
        }
    }
}
