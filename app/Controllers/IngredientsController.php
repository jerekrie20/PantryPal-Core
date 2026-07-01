<?php

namespace Controllers;

use Controllers\Concerns\RunsPantryIntake;
use Helpers\View;
use Models\Items;
use Services\Pantry\PantryItemAssembler;
use Services\Pantry\Sources\IngredientSource;
use Services\Recipes\RelatedRecipeFinder;

/**
 * Ingredient-focused controller: thin HTTP adapter over the shared
 * PantryIntake flow (via RunsPantryIntake) plus the ingredient detail page.
 */
class IngredientsController
{
    use RunsPantryIntake;

    protected Items $items;

    public function __construct()
    {
        $this->items = new Items();
    }

    /** GET /ingredients/create */
    public function create(): string
    {
        // Use the unified Items/create view; preset api_kind for ingredient flow
        return View::render('Items/create', [
            'title'  => 'Add Ingredient',
            'errors' => [],
            'input'  => ['api_kind' => 'ingredient'],
        ]);
    }

    /** POST /ingredients */
    public function store(): string
    {
        return $this->beginIntake(new IngredientSource(), $_POST, '/ingredients/confirm');
    }

    /** POST /ingredients/confirm */
    public function confirm(): string
    {
        return $this->completeIntake(new IngredientSource(), $_POST);
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

            // Load the joined item row and ensure it belongs to the user
            $row = $this->items->find($id, (int)$userId);
            if (!$row) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Item Not Found']);
            }

            // Ingredient must exist for this view
            if (empty($row['ingredient_id'])) {
                http_response_code(404);
                return View::render('Pages/404', ['title' => 'Ingredient Not Found']);
            }

            $item = (new PantryItemAssembler())->detail($row);

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
