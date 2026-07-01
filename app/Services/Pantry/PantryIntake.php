<?php

namespace Services\Pantry;

use Models\Items;
use Services\Pantry\Sources\CatalogSource;

/**
 * The add-to-pantry flow, kind-agnostic. Replaces the twin implementations
 * that lived in IngredientsController and ProductsController (and the
 * controller-to-controller delegation in ItemsController).
 *
 * begin():    exact local match -> save immediately; otherwise return the
 *             choices for the confirm page.
 * complete(): resolve the user's confirm choice (local / manual / provider),
 *             persist the catalog row if needed, save the pantry item.
 *
 * Results are plain arrays; controllers translate them into redirects/views.
 */
class PantryIntake
{
    public function __construct(
        private CatalogSource $source,
        private ?Items $items = null,
    ) {
        // Items is created lazily: its constructor requires a live DB
        // connection, which unit tests don't have.
    }

    /**
     * @param array $input The validated create-form POST (name, brand,
     *                     quantity, unit, purchase_date, expiration_date).
     * @return array{type:'saved'}|array{type:'confirm', choices: array}
     */
    public function begin(array $input, int $userId): array
    {
        $name  = trim($input['name'] ?? '');
        $brand = isset($input['brand']) ? trim($input['brand']) : null;

        $exactId = $this->source->findExactId($name, $brand);
        if ($exactId !== null) {
            $this->saveItem($userId, $exactId, $input, $name, $brand);
            return ['type' => 'saved'];
        }

        return [
            'type'    => 'confirm',
            'choices' => $this->source->searchChoices($name, $brand),
        ];
    }

    /**
     * @param array $post The confirm-form POST: picked_source, api_id,
     *                    ingredient_id|product_id, original_input[].
     * @return array{type:'saved'}
     *       | array{type:'manual_unsupported'}
     *       | array{type:'error', message: string}
     */
    public function complete(array $post, int $userId): array
    {
        $picked   = $post['picked_source'] ?? null;
        $apiId    = $post['api_id'] ?? null;
        $original = is_array($post['original_input'] ?? null) ? $post['original_input'] : [];

        $name  = trim($original['name'] ?? '');
        $brand = isset($original['brand']) ? trim($original['brand']) : null;

        // 1. An existing local catalog row was picked.
        $localKey = $this->source->kind() === 'product' ? 'product_id' : 'ingredient_id';
        $localId  = (int)($post[$localKey] ?? 0);
        if ($picked === 'local' && $localId > 0) {
            $this->saveItem($userId, $localId, $original, $name, $brand);
            return ['type' => 'saved'];
        }

        // 2. Manual save — explicitly picked, or implied by a missing api_id
        //    (the ingredient flow has always treated no-api_id as manual).
        if ($picked === 'manual' || empty($apiId)) {
            if (!$this->source->supportsManual()) {
                return $picked === 'manual'
                    ? ['type' => 'manual_unsupported']
                    : ['type' => 'error', 'message' => 'Please select an option.'];
            }
            $catalogId = $this->source->createManual($name, $brand);
            if ($catalogId === null) {
                return ['type' => 'error', 'message' => 'Failed to save item.'];
            }
            $this->saveItem($userId, $catalogId, $original, $name, $brand);
            return ['type' => 'saved'];
        }

        // 3. Provider result — persist into the catalog, then save.
        $catalogId = $this->source->ensureFromApi($apiId, $name, $brand);
        if ($catalogId === null) {
            return ['type' => 'error', 'message' => 'Failed to save ' . $this->source->kind() . ' from provider.'];
        }
        $this->saveItem($userId, $catalogId, $original, $name, $brand);
        return ['type' => 'saved'];
    }

    private function saveItem(int $userId, int $catalogId, array $input, string $name, ?string $brand): void
    {
        $this->itemsModel()->create([
            'user_id'         => $userId,
            ...$this->source->itemColumns($catalogId),
            'quantity'        => $input['quantity'] ?? 1,
            'unit'            => $input['unit'] ?? null,
            'purchase_date'   => $input['purchase_date'] ?? null,
            'expiration_date' => $input['expiration_date'] ?? null,
            'entered_name'    => $name,
            'entered_brand'   => $brand,
        ]);

        PantryCache::bustForUser($userId);
    }

    private function itemsModel(): Items
    {
        return $this->items ??= new Items();
    }
}
