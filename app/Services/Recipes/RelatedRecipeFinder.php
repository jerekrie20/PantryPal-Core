<?php

namespace Services\Recipes;

use Models\Recipes;
use Services\Pantry\PantryTermNormalizer;

/**
 * Given an item / ingredient name, find matching recipes.
 *
 * Strategy:
 *   1. Normalize the term (strip cultivar names, packaging, etc.).
 *   2. Local DB search first.
 *   3. If underfilled, top up from FatSecret and persist new hits.
 *   4. De-duplicate on (title | image).
 *
 * Extracted from ItemsController::show and IngredientsController::show,
 * both of which had this logic inline as an IIFE.
 */
class RelatedRecipeFinder
{
    public function __construct(
        private ?Recipes $recipes = null,
        private ?FatSecretRecipesProvider $provider = null,
    ) {
        $this->recipes  ??= new Recipes();
        $this->provider ??= new FatSecretRecipesProvider();
    }

    /**
     * @return array List of recipe arrays. Each entry has at least 'title' / 'image';
     *               new-from-provider hits also get 'db_id' from persistence.
     */
    public function findByName(string $itemName, int $limit = 6): array
    {
        $term = PantryTermNormalizer::normalize($itemName);
        if ($term === '') return [];

        try {
            $list = $this->recipes->findByIngredientsLocal([$term], $limit, true);
        } catch (\Throwable $e) {
            $list = [];
        }

        if (count($list) >= $limit) return $list;

        try {
            if ($this->provider->isConfigured()) {
                $needed = $limit - count($list);
                $apiResults = $this->provider->findByIngredients([$term], $needed);

                $seen = [];
                foreach ($list as $existing) {
                    $key = $this->dedupeKey($existing);
                    if ($key !== '') $seen[$key] = true;
                }

                foreach ($apiResults as $r) {
                    $key = $this->dedupeKey($r);
                    if ($key === '' || isset($seen[$key])) continue;

                    try {
                        $id = $this->recipes->upsertFromProvider($r, null, 'fatsecret');
                        $r['db_id'] = $id;
                    } catch (\Throwable $e) {
                        // Persist failures are non-fatal; still show the result.
                    }
                    $list[] = $r;
                    $seen[$key] = true;
                    if (count($list) >= $limit) break;
                }
            }
        } catch (\Throwable $e) {
            // Provider unavailable is non-fatal; return what we already have.
        }

        return $list;
    }

    private function dedupeKey(array $recipe): string
    {
        return strtolower(trim(($recipe['title'] ?? '') . '|' . ($recipe['image'] ?? '')));
    }
}
