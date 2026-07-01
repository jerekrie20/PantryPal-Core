<?php

namespace Services\Pantry\Sources;

use Models\Ingredients;
use Services\FoodService;

class IngredientSource implements CatalogSource
{
    public function __construct(
        private ?Ingredients $ingredients = null,
        private ?FoodService $food = null,
    ) {
        $this->ingredients ??= new Ingredients();
        $this->food        ??= new FoodService();
    }

    public function kind(): string
    {
        return 'ingredient';
    }

    public function findExactId(string $name, ?string $brand): ?int
    {
        $row = $this->ingredients->findExact(
            Ingredients::normalizeName($name),
            Ingredients::normalizeBrand($brand),
            'ingredient'
        );
        return $row ? (int)$row['id'] : null;
    }

    public function searchChoices(string $name, ?string $brand): array
    {
        $normName  = Ingredients::normalizeName($name);
        $normBrand = Ingredients::normalizeBrand($brand);

        $choices = [];
        foreach ($this->ingredients->searchFuzzy($normName, $normBrand, 'ingredient', 8) as $r) {
            $choices[] = [
                'source'        => 'local',
                'api_id'        => $r['api_id'] ?? 0,
                'name'          => $r['name'],
                'brand'         => $r['brand'] ?? null,
                'image_url'     => $r['image_url'] ?? null,
                'type'          => $r['api_kind'] ?? 'ingredient',
                'ingredient_id' => (int)$r['id'],
                'product_id'    => null,
            ];
        }

        foreach ($this->food->searchWithKind($name, 'ingredient', $normBrand, 6) as $r) {
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
        $row = $this->food->ensureIngredientFromApi($apiId, $name, $brand);
        return $row ? (int)$row['id'] : null;
    }

    public function supportsManual(): bool
    {
        return true;
    }

    public function createManual(string $name, ?string $brand): ?int
    {
        $norm      = Ingredients::normalizeName($name);
        $normBrand = Ingredients::normalizeBrand($brand);

        $existing = $this->ingredients->findExact($norm, $normBrand, null);
        if ($existing) {
            return (int)$existing['id'];
        }

        $id = $this->ingredients->create([
            'name'            => $name,
            'normalized_name' => $norm,
            'brand'           => $normBrand,
            'api_source'      => null,
            'api_id'          => null,
            'api_kind'        => 'manual',
            'image_url'       => null,
            'category'        => null,
            'nutrition_info'  => null,
            'search_terms'    => $name . ' ' . ($brand ?? ''),
        ]);
        return $id ? (int)$id : null;
    }

    public function itemColumns(int $catalogId): array
    {
        return ['ingredient_id' => $catalogId, 'product_id' => null];
    }
}
