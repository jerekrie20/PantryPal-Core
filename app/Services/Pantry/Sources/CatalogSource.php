<?php

namespace Services\Pantry\Sources;

/**
 * A catalog a pantry item can be backed by (the global `ingredients` or
 * `products` tables). Encapsulates everything that differs between the
 * ingredient and product intake flows so PantryIntake can run one flow.
 */
interface CatalogSource
{
    /** 'ingredient' | 'product' — matches the api_kind form value. */
    public function kind(): string;

    /** Catalog row id when an exact local match exists, else null. */
    public function findExactId(string $name, ?string $brand): ?int;

    /**
     * Choices for the confirm page: fuzzy local matches first, then
     * provider results. Shape matches the Items/confirm view contract
     * (source, api_id, name, brand, image_url, type, ingredient_id, product_id).
     */
    public function searchChoices(string $name, ?string $brand): array;

    /** Persist a provider result into the catalog; id or null on failure. */
    public function ensureFromApi(int|string $apiId, string $name, ?string $brand): ?int;

    /** Whether this catalog supports manual (no-API) entries. */
    public function supportsManual(): bool;

    /** Find-or-create a manual catalog row; null on persistence failure. */
    public function createManual(string $name, ?string $brand): ?int;

    /** FK columns for items.create, e.g. ['ingredient_id' => 3, 'product_id' => null]. */
    public function itemColumns(int $catalogId): array;
}
