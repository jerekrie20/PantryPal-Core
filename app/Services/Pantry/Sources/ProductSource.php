<?php

namespace Services\Pantry\Sources;

use Models\Products;
use Services\FoodService;

class ProductSource implements CatalogSource
{
    public function __construct(
        private ?Products $products = null,
        private ?FoodService $food = null,
    ) {
        $this->products ??= new Products();
        $this->food     ??= new FoodService();
    }

    public function kind(): string
    {
        return 'product';
    }

    public function findExactId(string $name, ?string $brand): ?int
    {
        $row = $this->products->findExact($name, $brand ?: null);
        return $row ? (int)$row['id'] : null;
    }

    public function searchChoices(string $name, ?string $brand): array
    {
        $brand = $brand ?: null;

        $choices = [];
        foreach ($this->products->searchFuzzy($name, $brand, 8) as $p) {
            $choices[] = [
                'source'        => 'local',
                'api_id'        => $p['api_id'] ?? 0,
                'name'          => $p['title'],
                'brand'         => $p['brand'] ?? null,
                'image_url'     => $p['image_url'] ?? null,
                'type'          => 'product',
                'ingredient_id' => null,
                'product_id'    => (int)$p['id'],
            ];
        }

        foreach ($this->food->searchWithKind($name, 'product', $brand, 6) as $r) {
            $choices[] = [
                'source'        => $r['source'] ?? 'provider',
                'api_id'        => $r['api_id'],
                'name'          => $r['name'],
                'brand'         => $r['brand'] ?? null,
                'image_url'     => $r['image_url'] ?? null,
                'type'          => $r['type'],
                'ingredient_id' => null,
                'product_id'    => null,
            ];
        }

        return $choices;
    }

    public function ensureFromApi(int|string $apiId, string $name, ?string $brand): ?int
    {
        $row = $this->food->ensureProductFromApi($apiId, $name, $brand);
        return $row ? (int)$row['id'] : null;
    }

    /** Products have no manual-create path; PantryIntake reports it unsupported. */
    public function supportsManual(): bool
    {
        return false;
    }

    public function createManual(string $name, ?string $brand): ?int
    {
        return null;
    }

    public function itemColumns(int $catalogId): array
    {
        return ['ingredient_id' => null, 'product_id' => $catalogId];
    }
}
