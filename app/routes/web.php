<?php
global $router;

use Controllers\DashboardController;
use Controllers\HomeController;
use Controllers\ItemsController;
use Controllers\IngredientsController;
use Controllers\ProductsController;
use Controllers\UserController;
use Controllers\RecipesController;
use Controllers\AdminController;
use Middleware\AuthMiddleware;
use Middleware\CsrfMiddleware;
use Middleware\AdminMiddleware;

// Define application routes

// Guests
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', function () {
    require VIEW_PATH . '/Pages/about.php';
});
$router->get('/features', function () {
    require VIEW_PATH . '/Pages/features.php';
});
$router->get('/login', function () {
    require VIEW_PATH . '/Pages/login.php';
});
$router->get('/register', function () {
    require VIEW_PATH . '/Pages/register.php';
});
// Protect guest POST routes with CSRF
$router->group(['middleware' => [CsrfMiddleware::class]], function ($router) {
    $router->post('/login', [UserController::class, 'index']);
    $router->post('/register', [UserController::class, 'create']);
});

// Internal routes gated by env flag and admin role
$router->get('/__internal/learning', function () {
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') { http_response_code(404); require VIEW_PATH . '/Pages/404.php'; return; }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAdmin = !empty($_SESSION['is_admin']);
    if (!$isAdmin) { http_response_code(403); require VIEW_PATH . '/Pages/403.php'; return; }
    require VIEW_PATH . '/Learning/overview.html';
});
$router->get('/__internal/theme', function () {
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') { http_response_code(404); require VIEW_PATH . '/Pages/404.php'; return; }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAdmin = !empty($_SESSION['is_admin']);
    if (!$isAdmin) { http_response_code(403); require VIEW_PATH . '/Pages/403.php'; return; }
    require VIEW_PATH . '/Learning/theme.php';
});

$router->get('/__internal/redis-test', function () {
    $env = getenv('APP_ENV') ?: 'development';
    if ($env === 'production') { http_response_code(404); echo 'Not Found'; return; }
    $enabled = getenv('INTERNAL_TOOLS_ENABLED') ?: '';
    if (strtolower((string)$enabled) !== 'true') { http_response_code(404); echo 'Not Found'; return; }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isAdmin = !empty($_SESSION['is_admin']);
    if (!$isAdmin) { http_response_code(403); echo 'Forbidden'; return; }
    header('Content-Type: text/plain');
    try {
        $ready = \Helpers\Cache::ready();
        if (!$ready) {
            echo "redis: not connected\n";
            // Keep diagnostics concise in non-production environments
            echo 'Predis\\Client: ' . (class_exists('Predis\\Client') ? 'yes' : 'no') . ", ext-redis: " . (class_exists('Redis') ? 'yes' : 'no') . "\n";
            return;
        }
        $k = 'pp:test:' . bin2hex(random_bytes(4));
        \Helpers\Cache::set($k, 'bar', 30);
        $v = \Helpers\Cache::get($k);
        echo "set/get => ".$v."\n";
    } catch (\Throwable $e) {
        echo 'error: ' . $e->getMessage();
    }
});

// --- Authenticated Routes ---
// All routes defined inside this group will first run the AuthMiddleware and CSRF for POSTs.
$router->group(['middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function ($router) {

    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->post('/logout', [UserController::class, 'logout']);

    // Items Routes (existing)
    $router->get('/items', [ItemsController::class, 'index']); // Display all items
    $router->get('/items/create', [ItemsController::class, 'create']); // Display the form
    $router->post('/items', [ItemsController::class, 'store']); // Store data from the forms
    $router->post('/items/confirm', [ItemsController::class, 'storeConfirmed']); // Confirm selection from API choices
    $router->get('/items/view/{id:int}', [ItemsController::class, 'show']); // View a specific item
    $router->get('/items/renew/{id:int}', [ItemsController::class, 'renew']); // Renew an item (quick action)
    $router->get('/items/{id:int}/edit', [ItemsController::class, 'edit']); // Display the form for update
    $router->post('/items/{id:int}', [ItemsController::class, 'update']); // Update data from the form
    $router->post('/items/{id:int}/delete', [ItemsController::class, 'destroy']); // Delete an item

    // New: Ingredient-specific routes (thin controllers over Items flow)
    $router->get('/ingredients/create', [IngredientsController::class, 'create']);
    $router->post('/ingredients', [IngredientsController::class, 'store']);
    $router->post('/ingredients/confirm', [IngredientsController::class, 'confirm']);
    $router->get('/ingredients/view/{id:int}', [IngredientsController::class, 'show']);

    // New: Product-specific routes (thin controllers over Items flow)
    $router->get('/products/create', [ProductsController::class, 'create']);
    $router->post('/products', [ProductsController::class, 'store']);
    $router->post('/products/confirm', [ProductsController::class, 'confirm']);
    $router->get('/products/view/{id:int}', [ProductsController::class, 'show']);

    // Recipes
    $router->get('/recipes', [RecipesController::class, 'index']);
    $router->get('/recipes/suggested', [RecipesController::class, 'suggested']);
    $router->get('/recipes/view/{id:int}', [RecipesController::class, 'show']);
    
    // User-created recipes
    $router->get('/recipes/create', [RecipesController::class, 'create']);
    $router->post('/recipes', [RecipesController::class, 'store']);

    // Save/Unsave
    $router->post('/recipes/save', [RecipesController::class, 'save']);
    $router->post('/recipes/unsave', [RecipesController::class, 'unsave']);

});

// --- Admin Routes ---
$router->group(['middleware' => [AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]], function ($router) {
    $router->get('/admin', [AdminController::class, 'index']);

    // Users CRUD (admin)
    $router->get('/admin/users', [AdminController::class, 'users']);
    $router->post('/admin/users/{id:int}/toggle-admin', [AdminController::class, 'userToggleAdmin']);
    $router->post('/admin/users/{id:int}/delete', [AdminController::class, 'userDelete']);

    // Items CRUD (admin)
    $router->get('/admin/items', [AdminController::class, 'items']);
    $router->get('/admin/items/{id:int}/edit', [AdminController::class, 'itemEdit']);
    $router->post('/admin/items/{id:int}', [AdminController::class, 'itemUpdate']);
    $router->post('/admin/items/{id:int}/delete', [AdminController::class, 'itemDelete']);

    // Recipes CRUD (admin)
    $router->get('/admin/recipes', [AdminController::class, 'recipes']);
    $router->get('/admin/recipes/create', [AdminController::class, 'recipeCreate']);
    // Alias for capitalized path used in some links/bookmarks
    $router->get('/admin/recipes/Create', [AdminController::class, 'recipeCreate']);
    // Accept multiple POST endpoints for creating recipes to avoid 404s from differing links
    $router->post('/admin/recipes', [AdminController::class, 'recipeStore']);
    $router->post('/admin/recipes/create', [AdminController::class, 'recipeStore']);
    $router->post('/admin/recipes/Create', [AdminController::class, 'recipeStore']);
    $router->get('/admin/recipes/{id:int}/edit', [AdminController::class, 'recipeEdit']);
    $router->get('/admin/recipes/{id:int}/Edit', [AdminController::class, 'recipeEdit']);
    // Accept posting back to either /{id} (preferred) or /{id}/edit (when forms or bookmarks post to current URL)
    $router->post('/admin/recipes/{id:int}', [AdminController::class, 'recipeUpdate']);
    $router->post('/admin/recipes/{id:int}/edit', [AdminController::class, 'recipeUpdate']);
    $router->post('/admin/recipes/{id:int}/Edit', [AdminController::class, 'recipeUpdate']);
    $router->post('/admin/recipes/{id:int}/delete', [AdminController::class, 'recipeDelete']);

    // Updates (admin comms)
    $router->get('/admin/updates', [AdminController::class, 'updatesIndex']);
    $router->get('/admin/updates/create', [AdminController::class, 'updatesCreate']);
    $router->post('/admin/updates', [AdminController::class, 'updatesStore']);
    $router->get('/admin/updates/{id:int}/edit', [AdminController::class, 'updatesEdit']);
    $router->post('/admin/updates/{id:int}', [AdminController::class, 'updatesUpdate']);
    $router->post('/admin/updates/{id:int}/delete', [AdminController::class, 'updatesDelete']);
});

// 404 fallback
$router->fallback(function () {
    http_response_code(404);
    require VIEW_PATH . '/Pages/404.php';
});
