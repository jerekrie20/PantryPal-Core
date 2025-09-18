<?php
global $router;

use Controllers\HomeController;
use Controllers\UserController;

// Define application routes

//Guests
$router->get('/', [HomeController::class, 'index']);
$router->get('/overview', function () {require VIEW_PATH . '/Learning/overview.html';});
$router->get('/theme', function () {require VIEW_PATH . '/Learning/theme.php';});
$router->get('/login', function () {require VIEW_PATH . '/Pages/login.php';});
$router->get('/register', function () { require VIEW_PATH . '/Pages/register.php';});
$router->post('/login', [UserController::class, 'index']);
$router->post('/register', [UserController::class, 'create']);

//Auth Users
$router->get('/dashboard', function () {
    require VIEW_PATH . '/Users/dashboard.php';
});




// Placeholder examples for future CRUD
//$router->get('/items', [ItemController::class, 'index']);
//$router->get('/items/create', [ItemController::class, 'create']);
//$router->post('/items', [ItemController::class, 'store']);
//$router->get('/items/{id:int}/edit', [ItemController::class, 'edit']);
//$router->post('/items/{id:int}', [ItemController::class, 'update']);
//$router->post('/items/{id:int}/delete', [ItemController::class, 'destroy']);

// 404 fallback
$router->fallback(function () {
    http_response_code(404);
    require VIEW_PATH . '/Pages/404.php';
});
