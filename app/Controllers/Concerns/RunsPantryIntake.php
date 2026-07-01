<?php

namespace Controllers\Concerns;

use Helpers\Validator;
use Helpers\View;
use Services\Pantry\PantryIntake;
use Services\Pantry\Sources\CatalogSource;

/**
 * Shared HTTP handling for the add-to-pantry flow. Controllers stay thin:
 * validate, run PantryIntake, translate the result into a redirect or view.
 */
trait RunsPantryIntake
{
    /**
     * Validate the create-form input. Returns a rendered error view on
     * failure, null on success. (Moved from ItemsController::validation.)
     */
    protected function pantryValidation(array $input): ?string
    {
        global $conn;
        $validator = new Validator($input, $conn);

        $validator->check([
            'name'            => ['required' => true, 'min' => 2, 'max' => 255],
            'quantity'        => ['required' => true, 'numeric' => true],
            'unit'            => ['required' => false, 'max' => 10, 'string' => true],
            'purchase_date'   => ['required' => false, 'date' => true],
            'expiration_date' => ['required' => false, 'date' => true],
        ]);

        $errors = $validator->errors();

        // Cross-field: expiration_date not before purchase_date
        $pd = $input['purchase_date'] ?? null;
        $ed = $input['expiration_date'] ?? null;
        if (!empty($pd) && !empty($ed)) {
            $pdObj = \DateTime::createFromFormat('Y-m-d', (string)$pd);
            $edObj = \DateTime::createFromFormat('Y-m-d', (string)$ed);
            if ($pdObj && $edObj && $edObj < $pdObj) {
                $errors['expiration_date'] = 'Expiration date cannot be before the purchase date';
            }
        }

        if (!empty($errors)) {
            return View::render('Items/create', [
                'title'  => 'Add New Item',
                'errors' => $errors,
                'input'  => $input,
            ]);
        }

        return null;
    }

    /** POST store: validate, then save-or-confirm. */
    protected function beginIntake(CatalogSource $source, array $input): string
    {
        if ($errorView = $this->pantryValidation($input)) {
            return $errorView;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $result = (new PantryIntake($source))->begin($input, $userId);

        if ($result['type'] === 'saved') {
            header('Location: /dashboard');
            exit;
        }

        return View::render('Items/confirm', [
            'title'          => 'Confirm ' . ucfirst($source->kind()),
            'choices'        => $result['choices'],
            'original_input' => $input,
            'api_kind'       => $source->kind(),
            'confirm_action' => '/items/confirm',
        ]);
    }

    /** POST <kind>-confirm: resolve the user's choice. */
    protected function completeIntake(CatalogSource $source, array $post): string
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $result = (new PantryIntake($source))->complete($post, $userId);

        if ($result['type'] === 'saved') {
            header('Location: /dashboard');
            exit;
        }

        if ($result['type'] === 'manual_unsupported') {
            header('Location: /items/create?kind=ingredient');
            exit;
        }

        return View::render('Items/create', [
            'title'  => 'Add ' . ucfirst($source->kind()),
            'errors' => [$result['message']],
            'input'  => is_array($post['original_input'] ?? null) ? $post['original_input'] : [],
        ]);
    }
}
