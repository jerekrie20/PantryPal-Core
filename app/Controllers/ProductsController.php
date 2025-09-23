<?php

namespace Controllers;

use Helpers\View;
use Models\Items;
use Models\Products;
use Services\FoodService;

/**
 * Product-focused controller: handles search/confirm/create for product flow.
 */
class ProductsController
{
    protected Items $items;
    protected Products $products;
    protected FoodService $svc;

    public function __construct()
    {
        $this->items = new Items();
        $this->products = new Products();
        $this->svc = new FoodService();
    }

    /** GET /products/create */
    public function create(): string
    {
        // Use the unified Items/create view; preset api_kind for product flow
        return View::render('Items/create', [
            'title' => 'Add Product',
            'errors' => [],
            'input' => ['api_kind' => 'product'],
        ]);
    }

    /** POST /products */
    public function store()
    {
        // Reuse validation rules from ItemsController
        $itemsController = new ItemsController();
        $_POST['api_kind'] = 'product';
        $validationView = $itemsController->validation();
        if ($validationView !== null) {
            return $validationView;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $rawName = trim($_POST['name'] ?? '');
        $brand   = isset($_POST['brand']) ? trim($_POST['brand']) : null;

        // Exact local product match
        $product = $this->products->findExact($rawName, $brand ? $brand : null);
        if ($product) {
            $this->items->create([
                'user_id' => $userId,
                'ingredient_id' => null,
                'product_id' => (int)$product['id'],
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

        // Choices: local products + provider
        $choices = [];
        $localProds = $this->products->searchFuzzy($rawName, $brand ? $brand : null, 8);
        foreach ($localProds as $p) {
            $choices[] = [
                'source' => 'local',
                'api_id' => $p['api_id'] ?? 0,
                'name' => $p['title'],
                'brand' => $p['brand'] ?? null,
                'image_url' => $p['image_url'] ?? null,
                'type' => 'product',
                'ingredient_id' => null,
                'product_id' => (int)$p['id'],
            ];
        }

        $apiChoices = $this->svc->searchWithKind($rawName, 'product', $brand ? $brand : null, 6);
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
            'title' => 'Confirm Product',
            'choices' => $choices,
            'original_input' => $_POST,
            'api_kind' => 'product',
            'confirm_action' => '/products/confirm',
        ]);
    }

    /** POST /products/confirm */
    public function confirm()
    {
        $pickedSource = $_POST['picked_source'] ?? null;
        $apiId = $_POST['api_id'] ?? null;
        $productId = (int)($_POST['product_id'] ?? 0);
        $original = $_POST['original_input'] ?? [];
        $userId = $_SESSION['user_id'] ?? null;

        $rawName = trim($original['name'] ?? '');
        $brand   = isset($original['brand']) ? trim($original['brand']) : null;

        if ($pickedSource === 'local' && $productId > 0) {
            $this->items->create([
                'user_id' => $userId,
                'ingredient_id' => null,
                'product_id' => $productId,
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

        // No manual creation for products here; if user picks manual, redirect to ingredient/manual flow
        if ($pickedSource === 'manual') {
            header('Location: /ingredients/create');
            exit;
        }

        // Provider path
        if (!empty($apiId)) {
            $prod = $this->svc->ensureProductFromApi($apiId, $rawName, $brand);
            if (!$prod) {
                return View::render('Items/create', [
                    'title' => 'Add Product',
                    'errors' => ['Failed to save product from provider.'],
                    'input' => $original,
                ]);
            }
            $this->items->create([
                'user_id' => $userId,
                'ingredient_id' => null,
                'product_id' => (int)$prod['id'],
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

        return View::render('Items/create', [
            'title' => 'Add Product',
            'errors' => ['Please select an option.'],
            'input' => $original,
        ]);
    }

    /** GET /products/view/{id:int} → reuse ItemsController::show */
    public function show(int $id): string
    {
        $itemsController = new ItemsController();
        return $itemsController->show($id);
    }
}
