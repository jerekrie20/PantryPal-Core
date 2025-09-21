<?php
global $router;

use Controllers\DashboardController;
use Controllers\HomeController;
use Controllers\ItemsController;
use Controllers\IngredientsController;
use Controllers\ProductsController;
use Controllers\UserController;
use Middleware\AuthMiddleware;

// Define application routes

//Guests
$router->get('/', [HomeController::class, 'index']);
$router->get('/overview', function () {
    require VIEW_PATH . '/Learning/overview.html';
});
$router->get('/theme', function () {
    require VIEW_PATH . '/Learning/theme.php';
});
$router->get('/login', function () {
    require VIEW_PATH . '/Pages/login.php';
});
$router->get('/register', function () {
    require VIEW_PATH . '/Pages/register.php';
});
$router->post('/login', [UserController::class, 'index']);
$router->post('/register', [UserController::class, 'create']);


// --- Authenticated Routes ---
// All routes defined inside this group will first run the AuthMiddleware.
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {

    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/logout', [UserController::class, 'logout']);

    // Items Routes (existing)
    $router->get('/items', [ItemsController::class, 'index']); // Display all items
    $router->get('/items/create', [ItemsController::class, 'create']); // Display the form
    $router->post('/items', [ItemsController::class, 'store']); // Store data from the forms
    $router->post('/items/confirm', [ItemsController::class, 'storeConfirmed']); // Confirm selection from API choices
    $router->get('/items/view/{id:int}', [ItemsController::class, 'show']); // View a specific item
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

});


// 404 fallback
$router->fallback(function () {
    http_response_code(404);
    require VIEW_PATH . '/Pages/404.php';
});
