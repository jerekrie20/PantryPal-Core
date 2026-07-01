<?php

namespace Controllers;

use Controllers\Concerns\RunsPantryIntake;
use Helpers\View;
use Services\Pantry\Sources\ProductSource;

/**
 * Product-focused controller: thin HTTP adapter over the shared
 * PantryIntake flow (via RunsPantryIntake).
 */
class ProductsController
{
    use RunsPantryIntake;

    /** GET /products/create */
    public function create(): string
    {
        // Use the unified Items/create view; preset api_kind for product flow
        return View::render('Items/create', [
            'title'  => 'Add Product',
            'errors' => [],
            'input'  => ['api_kind' => 'product'],
        ]);
    }

    /** POST /products */
    public function store(): string
    {
        return $this->beginIntake(new ProductSource(), $_POST, '/products/confirm');
    }

    /** POST /products/confirm */
    public function confirm(): string
    {
        return $this->completeIntake(new ProductSource(), $_POST);
    }

    /** GET /products/view/{id:int} → same detail flow as ItemsController::show */
    public function show(int $id): string
    {
        return (new ItemsController())->show($id);
    }
}
