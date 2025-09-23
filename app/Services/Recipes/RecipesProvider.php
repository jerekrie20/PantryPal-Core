<?php

namespace Services\Recipes;

interface RecipesProvider
{
    /**
     * Search recipes by a general text query.
     * Should return a normalized array of recipes with keys:
     * - id (string|int)
     * - title (string)
     * - image (string|null)
     * - sourceUrl (string|null)
     * - usedIngredients (string[])
     * - missedIngredients (string[])
     */
    public function searchByQuery(string $query, int $number = 12): array;

    /**
     * Find recipes that use these ingredients (by names/keywords).
     * @param string[] $ingredients
     */
    public function findByIngredients(array $ingredients, int $number = 12): array;

    /** Whether the provider is configured (e.g., API key present). */
    public function isConfigured(): bool;
}
