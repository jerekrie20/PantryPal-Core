<?php
namespace Services;

use Models\Ingredients;
use Models\Products;
use Services\Providers\FdcProvider;
use Services\Providers\OffProvider;

class FoodService
{
    public function __construct(
        protected ?FoodProvider $fdc = null,
        protected ?FoodProvider $off = null,
        protected ?Ingredients $ingredients = null,
        protected ?Products $products = null,
    ) {
        $this->fdc = $fdc ?? new FdcProvider();
        $this->off = $off ?? new OffProvider();
        $this->ingredients = $ingredients ?? new Ingredients();
        $this->products    = $products ?? new Products();
    }

    /** Same signature/shape you used before. */
    public function searchWithKind(string $query, string $apiKind, ?string $brand = null, int $limit = 5): array
    {
        if ($apiKind === 'product') {
            $q = trim(($brand ? $brand.' ' : '').$query);
            return $this->off->searchProducts($q, $limit);
        }
        if ($apiKind === 'ingredient') {
            return $this->fdc->searchIngredients($query, $limit);
        }
        return [];
    }

    /** Save ingredient from provider detail (FDC). */
    public function ensureIngredientFromApi(int|string $apiId, string $originalQuery, ?string $brand = null): ?array
    {
        // already have it?
        if ($hit = $this->ingredients->findBySourceAndApiId('fdc', $apiId)) {
            return $hit;
        }
        $details = $this->fdc->fetchIngredient($apiId);
        $name = $details['name'] ?? $originalQuery;

        $prunedNutri = isset($details['nutrition_info']) ? $this->pruneNutritionInfo($details['nutrition_info']) : null;

        $id = $this->ingredients->create([
            'name'            => $name,
            'normalized_name' => Ingredients::normalizeName($name),
            'brand'           => Ingredients::normalizeBrand($brand),
            'api_source'      => 'fdc',
            'api_id'          => (string)$apiId,
            'api_kind'        => 'ingredient',
            'image_url'       => $details['image_url'] ?? null,
            'category'        => $details['category'] ?? null,
            'nutrition_info'  => $prunedNutri,
            'search_terms'    => $originalQuery,
        ]);
        return $id ? $this->ingredients->find($id) : null;
    }

    /** Save product from provider detail (OFF). */
    public function ensureProductFromApi(int|string $apiId, string $originalQuery, ?string $brand = null): ?array
    {
        if ($hit = $this->products->findBySourceAndApiId('off', $apiId)) {
            return $hit;
        }
        $d = $this->off->fetchProduct($apiId);
        if (!$d) return null;

        $prunedNutri = isset($d['nutrition_info']) ? $this->pruneNutritionInfo($d['nutrition_info']) : null;

        $id = $this->products->create([
            'api_source'     => 'off',
            'api_id'         => (string)$apiId,
            'title'          => $d['name'] ?? $originalQuery,
            'brand'          => $d['brand'] ?? $brand,
            'upc'            => $d['upc'] ?? null,
            'size_text'      => $d['size_text'] ?? null,
            'image_url'      => $d['image_url'] ?? null,
            'category'       => $d['category'] ?? null,
            'nutrition_info' => $prunedNutri,
            'raw_payload'    => $d['raw'] ?? null,
        ]);
        return $id ? $this->products->findBySourceAndApiId('off', $apiId) : null;
    }

    /**
     * Reduce bulky nutrition payloads to the essentials we can render.
     * Accepts array|string JSON; returns array simplified.
     */
    private function pruneNutritionInfo($data): ?array
    {
        $src = is_array($data) ? $data : json_decode((string)$data, true);
        if (!is_array($src)) return null;
        // If OFF nutriments block, keep only nutriments + serving hints
        if (isset($src['nutriments']) && is_array($src['nutriments'])) {
            $keep = ['nutriments'];
            foreach (['serving_size','servingSize','servings','householdServingFullText','serving_quantity','serving_quantity_unit'] as $k) {
                if (isset($src[$k])) $keep[] = $k;
            }
            $out = [];
            foreach ($keep as $k) if (isset($src[$k])) $out[$k] = $src[$k];
            return $out;
        }
        // If FDC labelNutrients, keep that and serving info
        if (isset($src['labelNutrients']) && is_array($src['labelNutrients'])) {
            $out = ['labelNutrients' => $src['labelNutrients']];
            foreach (['servingSize','servingSizeUnit','householdServingFullText'] as $k) if (isset($src[$k])) $out[$k] = $src[$k];
            return $out;
        }
        // If FDC foodNutrients array present, keep only simplified list of {name,unitName,amount}
        if (isset($src['foodNutrients']) && is_array($src['foodNutrients'])) {
            $list = [];
            foreach ($src['foodNutrients'] as $fn) {
                $name = $fn['nutrient']['name'] ?? $fn['nutrientName'] ?? null;
                $amount = $fn['amount'] ?? $fn['value'] ?? null;
                $unit = $fn['nutrient']['unitName'] ?? $fn['unitName'] ?? null;
                if (!$name || $amount === null) continue;
                $list[] = ['nutrientName'=>$name, 'unitName'=>$unit, 'amount'=>(float)$amount];
            }
            $out = ['foodNutrients' => $list];
            foreach (['servingSize','servingSizeUnit','householdServingFullText'] as $k) if (isset($src[$k])) $out[$k] = $src[$k];
            return $out;
        }
        // Flat label-like map: keep as-is
        return $src;
    }
}
