<?php

namespace Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Models\Ingredients;
use Models\Products;

class SpoonacularService
{
    protected Client $client;
    protected string $apiKey;
    protected Ingredients $ingredients;
    protected Products $products;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.spoonacular.com/',
            'timeout'  => 5.0,
        ]);
        $this->apiKey = $_ENV['SPOONACULAR_API_KEY'] ?? 'YOUR_SPOONACULAR_API_KEY';
        $this->ingredients = new Ingredients();
        $this->products = new Products();
    }

    public static function normalizeName(string $name): string
    {
        return Ingredients::normalizeName($name);
    }

    public static function normalizeBrand(?string $brand): ?string
    {
        return Ingredients::normalizeBrand($brand);
    }

    /** Search by explicit kind to avoid guesswork. */
    public function searchWithKind(string $query, string $apiKind, ?string $brand = null, int $limit = 5): array
    {
        try {
            if ($apiKind === 'product') {
                return $this->searchProducts($brand ? ($brand . ' ' . $query) : $query, $limit);
            } elseif ($apiKind === 'ingredient') {
                return $this->searchIngredients($query, $limit);
            }
            return [];
        } catch (GuzzleException $e) {
            error_log("Spoonacular search error: " . $e->getMessage());
            return [];
        }
    }

    /** Ensure an ingredient exists from API id (ingredient kind). */
    public function ensureIngredientFromApi(int $apiId, string $originalQuery, ?string $brand = null): ?array
    {
        if ($existing = $this->ingredients->findByApiId($apiId)) return $existing;

        try {
            $details = $this->fetchIngredientDetails($apiId);
        } catch (GuzzleException $e) {
            error_log("Spoonacular fetch ingredient error: " . $e->getMessage());
            $details = null;
        }

        $name = $details['name'] ?? $originalQuery;
        $newId = $this->ingredients->create([
            'name'            => $name,
            'normalized_name' => self::normalizeName($name),
            'brand'           => self::normalizeBrand($brand),
            'api_id'          => $apiId,
            'api_kind'        => 'ingredient',
            'image_url'       => $details['image_url'] ?? null,
            'category'        => $details['category'] ?? null,
            'nutrition_info'  => $details['nutrition_info'] ?? null,
            'search_terms'    => $originalQuery,
        ]);
        return $newId ? $this->ingredients->find($newId) : null;
    }

    /** Ensure a product exists from API id; optionally link to an ingredient if derivable. */
    public function ensureProductFromApi(int $apiId, string $originalQuery, ?string $brand = null): ?array
    {
        if ($existing = $this->products->findByApiId($apiId)) return $existing;

        try {
            $details = $this->fetchProductDetails($apiId);
        } catch (GuzzleException $e) {
            error_log("Spoonacular fetch product error: " . $e->getMessage());
            $details = null;
        }
        if (!$details) return null;

        // Best-effort map to a generic ingredient by normalized title
        $nameForIng = $details['name'] ?? $originalQuery;
        $ing = $this->ingredients->findExact(self::normalizeName($nameForIng), null, 'ingredient');
        $ingId = $ing['id'] ?? null;

        $newId = $this->products->create([
            'ingredient_id'  => $ingId,
            'api_id'         => $apiId,
            'title'          => $details['name'] ?? $originalQuery,
            'brand'          => $details['brand'] ?? $brand,
            'upc'            => $details['upc'] ?? null,
            'size_text'      => $details['size_text'] ?? null,
            'image_url'      => $details['image_url'] ?? null,
            'category'       => $details['category'] ?? null,
            'nutrition_info' => $details['nutrition_info'] ?? null,
            'raw_payload'    => $details['raw'] ?? null,
        ]);
        return $newId ? $this->products->findByApiId($apiId) : null;
    }

    // --------- Spoonacular low-level ---------

    /**
     * @throws GuzzleException
     */
    private function searchProducts(string $query, int $limit = 20): array
    {
        $r = $this->client->get('food/products/search', [
            'query' => ['query' => $query, 'number' => $limit, 'apiKey' => $this->apiKey]
        ]);
        $d = json_decode($r->getBody(), true) ?: [];
        $out = [];
        foreach ($d['products'] ?? [] as $p) {
            $imageType = $p['imageType'] ?? 'jpg';
            $out[] = [
                'api_id'    => (int)$p['id'],
                'name'      => $p['title'] ?? '',
                'brand'     => $p['brand'] ?? null,
                'image_url' => "https://img.spoonacular.com/products/{$p['id']}-312x231.{$imageType}",
                'type'      => 'product',
            ];
        }
        return $out;
    }

    /**
     * @throws GuzzleException
     */
    private function fetchProductDetails(int $apiId): ?array
    {
        $r = $this->client->get("food/products/{$apiId}", [
            'query' => ['apiKey' => $this->apiKey]
        ]);
        $d = json_decode($r->getBody(), true) ?: null;
        if (!$d) return null;
        $imageType = $d['imageType'] ?? 'jpg';
        return [
            'name'           => $d['title'] ?? '',
            'brand'          => $d['brand'] ?? null,
            'upc'            => $d['upc'] ?? null,
            'size_text'      => $d['servings']['number'] ?? null,  // adjust if you prefer different size fields
            'image_url'      => "https://img.spoonacular.com/products/{$apiId}-312x231.{$imageType}",
            'category'       => $d['aisle'] ?? ($d['breadcrumbs'][0] ?? null),
            'nutrition_info' => $d['nutrition'] ?? null,
            'raw'            => $d
        ];
    }

    /**
     */
    private function searchIngredients(string $query, int $limit = 5): array
    {
        $variants = $this->generateIngredientQueryVariants($query);
        $variants[] = $query; // ensure original is included
        $seen = [];
        $results = [];

        foreach (array_unique($variants) as $q) {
            try {
                $r = $this->client->get('food/ingredients/search', [
                    'query' => ['query' => $q, 'number' => $limit, 'apiKey' => $this->apiKey]
                ]);
                $d = json_decode($r->getBody(), true) ?: [];
                foreach (($d['results'] ?? []) as $ing) {
                    $id = (int)($ing['id'] ?? 0);
                    if ($id && !isset($seen[$id])) {
                        $seen[$id] = true;
                        $results[] = [
                            'api_id'    => $id,
                            'name'      => $ing['name'] ?? '',
                            'brand'     => null,
                            'image_url' => 'https://img.spoonacular.com/ingredients_250x250/' . ($ing['image'] ?? ''),
                            'type'      => 'ingredient',
                        ];
                        if (count($results) >= $limit) {
                            return $results;
                        }
                    }
                }
                if (count($results) >= $limit) break;
            } catch (GuzzleException $e) {
                error_log("Spoonacular ingredient search error for '$q': " . $e->getMessage());
            }
        }

        // Fallback to autocomplete if we still have no results
        if (empty($results)) {
            try {
                $r = $this->client->get('food/ingredients/autocomplete', [
                    'query' => ['query' => $query, 'number' => $limit, 'metaInformation' => true, 'apiKey' => $this->apiKey]
                ]);
                $d = json_decode($r->getBody(), true) ?: [];
                foreach ($d as $ing) {
                    $id = (int)($ing['id'] ?? 0);
                    if ($id && !isset($seen[$id])) {
                        $seen[$id] = true;
                        $results[] = [
                            'api_id'    => $id,
                            'name'      => $ing['name'] ?? '',
                            'brand'     => null,
                            'image_url' => !empty($ing['image']) ? 'https://img.spoonacular.com/ingredients_250x250/' . $ing['image'] : null,
                            'type'      => 'ingredient',
                        ];
                        if (count($results) >= $limit) break;
                    }
                }
            } catch (GuzzleException $e) {
                error_log("Spoonacular ingredient autocomplete error for '$query': " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Generate useful query variants for ingredient search to improve recall.
     * Examples:
     *  - "honeycrisps apple" -> ["honeycrisp apple", "honeycrisp apples", "honeycrisps apples", "apple honeycrisp"]
     */
    private function generateIngredientQueryVariants(string $query): array
    {
        $q = trim(mb_strtolower($query));
        $q = preg_replace('/\s+/', ' ', $q);
        $tokens = array_values(array_filter(explode(' ', $q)));
        if (empty($tokens)) return [];

        // singularize simple plurals (apples -> apple), (berries -> berry)
        $singular = function (string $w): string {
            if (preg_match('/[a-z]ies$/', $w)) return preg_replace('/ies$/', 'y', $w);
            if (preg_match('/ses$|xes$|zes$|ches$|shes$/', $w)) return substr($w, 0, -2); // crude
            if (str_ends_with($w, 's') && !str_ends_with($w, 'ss')) return substr($w, 0, -1);
            return $w;
        };

        $variants = [];

        // Base: singularize all tokens
        $singTokens = array_map($singular, $tokens);
        $variants[] = implode(' ', $singTokens);

        // Also try pluralizing last noun with simple 's' if was singular
        if (!empty($singTokens)) {
            $last = $singTokens[count($singTokens)-1];
            $variants[] = implode(' ', array_merge(array_slice($singTokens, 0, -1), [$last . 's']));
        }

        // Try swapping order for two-token phrases (e.g., apple honeycrisp)
        if (count($singTokens) === 2) {
            $variants[] = $singTokens[1] . ' ' . $singTokens[0];
        }

        // De-duplicate and remove identical to original lowercased
        return array_values(array_unique(array_filter($variants, function ($v) use ($q) { return $v && $v !== $q; })));
    }

    /**
     * @throws GuzzleException
     */
    private function fetchIngredientDetails(int $apiId): ?array
    {
        $r = $this->client->get("food/ingredients/{$apiId}/information", [
            'query' => ['amount' => 100, 'unit' => 'grams' , 'apiKey' => $this->apiKey]
        ]);
        $d = json_decode($r->getBody(), true) ?: null;
        if (!$d) return null;
        return [
            'name'           => $d['name'] ?? '',
            'image_url'      => 'https://img.spoonacular.com/ingredients_250x250/' . ($d['image'] ?? ''),
            'category'       => $d['aisle'] ?? ($d['categoryPath'][0] ?? null),
            'nutrition_info' => $d['nutrition'] ?? null,
            'raw'            => $d
        ];
    }
}
