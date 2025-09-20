<?php

namespace Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Models\GlobalItems;

class SpoonacularService
{
    protected Client $client;
    protected string $apiKey;
    protected GlobalItems $globalItemsModel;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.spoonacular.com/',
            'timeout'  => 5.0,
        ]);
        $this->apiKey = $_ENV['SPOONACULAR_API_KEY'] ?? 'YOUR_SPOONACULAR_API_KEY';
        $this->globalItemsModel = new GlobalItems();
    }

    /**
     * Searches the API for potential matches for a user's query.
     * It prioritizes specific products and falls back to generic ingredients.
     *
     * @param string $itemName The user-provided item name.
     * @return array An array of potential choices, each with an id, name, and image.
     */
    public function searchForChoices(string $itemName): array
    {
        try {
            // First, try to find specific branded products.
            $products = $this->searchProducts($itemName);
            if (!empty($products)) {
                return $products;
            }

            // If no products are found, fall back to generic ingredients.
            $normalizedName = $this->normalizeName($itemName);
            if (empty($normalizedName)) {
                return [];
            }
            return $this->searchIngredients($normalizedName);

        } catch (GuzzleException $e) {
            error_log("Spoonacular API search error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches final details for a confirmed Spoonacular item ID and creates it in the local DB if needed.
     *
     * @param int $apiId The confirmed Spoonacular ID (from either product or ingredient search).
     * @param string $originalQuery The user's original search term for caching.
     * @return array|null The full global item record from the local database.
     */

    /**
     * Create or fetch a Global Item based on a Spoonacular id and kind.
     *
     * @param int $apiId
     * @param string|null $kind 'ingredient' or 'product'
     * @param string $originalQuery
     * @return array|null
     */
    public function createGlobalItemFromApi(int $apiId, ?string $kind, string $originalQuery): ?array
    {
        // existing?
        if ($existing = $this->globalItemsModel->findByApiId($apiId)) {
            return $existing;
        }

        $details = null;
        try {
            if ($kind === 'product') {
                $details = $this->fetchProductDetails($apiId);
            } else {
                // default path
                $details = $this->fetchIngredientDetails($apiId);
            }
        } catch (GuzzleException $e) {
            error_log("Spoonacular API detail fetch error: " . $e->getMessage());
        }

        // Fallback: if wrong kind or missing, try the other one too
        if (!$details) {
            try {
                $tryOther = ($kind === 'product') ? 'ingredient' : 'product';
                $details = ($tryOther === 'product')
                    ? $this->fetchProductDetails($apiId)
                    : $this->fetchIngredientDetails($apiId);
            } catch (GuzzleException $e) { /* ignore */ }
        }

        return $this->upsertGlobalFromDetailsWrapper($apiId, $details, $originalQuery);
    }

    private function upsertGlobalFromDetailsWrapper(int $apiId, ?array $details, string $originalQuery): ?array
    {
        if ($details) {
            $newItemId = $this->globalItemsModel->create([
                'name' => $details['name'],
                'normalized_name' => strtolower(trim($originalQuery)),
                'api_id' => $apiId,
                'image_url' => $details['image_url'] ?? null,
                'category' => $details['category'] ?? 'uncategorized',
                'nutrition_info' => isset($details['nutrition_info']) ? (is_array($details['nutrition_info']) ? json_encode($details['nutrition_info']) : $details['nutrition_info']) : null,
            ]);
            return $newItemId ? $this->globalItemsModel->find($newItemId) : null;
        }

        // minimal fallback
        $normalized = $this->normalizeName($originalQuery);
        if ($existingByName = $this->globalItemsModel->findByNormalizedName($normalized)) {
            return $existingByName;
        }
        $newItemId = $this->globalItemsModel->create([
            'name' => trim($originalQuery),
            'normalized_name' => $normalized,
            'api_id' => null,
            'image_url' => null,
            'category' => 'uncategorized',
            'nutrition_info' => null,
        ]);
        return $newItemId ? $this->globalItemsModel->find($newItemId) : null;
    }

    public function createGlobalItemFromApiId(int $apiId, string $originalQuery): ?array
    {
        // 1) If we already cached this API id, return it.
        $item = $this->globalItemsModel->findByApiId($apiId);
        if ($item) {
            return $item;
        }

        $details = null;
        try {
            // 2) Try to fetch details from Spoonacular.
            $details = $this->fetchIngredientDetails($apiId);
        } catch (GuzzleException $e) {
            // Log and continue to fallback.
            error_log("Spoonacular API detail fetch error: " . $e->getMessage());
        }

        if (!$details) {
            try { $details = $this->fetchProductDetails($apiId); } catch (\GuzzleHttp\Exception\GuzzleException $e) { /* ignore */ }
        }

        if ($details) {
            // 3) Create using full details when available.
            $newItemId = $this->globalItemsModel->create([
                'name' => $details['name'],
                'normalized_name' => strtolower(trim($originalQuery)),
                'api_id' => $apiId,
                'image_url' => $details['image_url'],
                'category' => $details['category'],
                'nutrition_info' => $details['nutrition_info'],
            ]);
            return $newItemId ? $this->globalItemsModel->find($newItemId) : null;
        }

        // 4) Fallback: if the API didn't return details (404, network, key missing, etc.),
        //    create or reuse a minimal global item using the user's original input.
        $normalized = $this->normalizeName($originalQuery);
        $existingByName = $this->globalItemsModel->findByNormalizedName($normalized);
        if ($existingByName) {
            return $existingByName;
        }

        $newItemId = $this->globalItemsModel->create([
            'name' => trim($originalQuery),
            'normalized_name' => $normalized,
            // Do not store a broken external id; keep it null so we can re-enrich later if needed.
            'api_id' => null,
            'image_url' => null,
            'category' => 'uncategorized',
            'nutrition_info' => null,
        ]);
        return $newItemId ? $this->globalItemsModel->find($newItemId) : null;
    }

    /**
     * Searches for branded grocery products.
     * @return array A list of choices.
     * @throws GuzzleException
     */
    private function searchProducts(string $query): array
    {
        $response = $this->client->get('food/products/search', [
            'query' => ['query' => $query, 'number' => 5, 'apiKey' => $this->apiKey]
        ]);
        $data = json_decode($response->getBody(), true);

        $choices = [];
        foreach ($data['products'] ?? [] as $product) {
            $choices[] = [
                'api_id' => $product['id'],
                'name' => $product['title'],
                'image_url' => (isset($product['imageType']) ? "https://img.spoonacular.com/products/{$product['id']}-312x231.{$product['imageType']}" : ($product['image'] ?? null)),
                'type' => 'product',
            ];
        }
        return $choices;
    }

    /**
     * Searches for generic ingredients.
     * @return array A list of choices.
     * @throws GuzzleException
     */
    private function searchIngredients(string $query): array
    {
        $response = $this->client->get('food/ingredients/search', [
            'query' => ['query' => $query, 'number' => 5, 'apiKey' => $this->apiKey]
        ]);
        $data = json_decode($response->getBody(), true);

        $choices = [];
        foreach ($data['results'] ?? [] as $ingredient) {
            $choices[] = [
                'api_id' => $ingredient['id'],
                'name' => $ingredient['name'],
                'image_url' => "https://img.spoonacular.com/ingredients_250x250/" . $ingredient['image'],
                'type' => 'ingredient',
            ];
        }
        return $choices;
    }

    /**
     * @throws GuzzleException
     */
    private function fetchIngredientDetails(int $apiId): ?array {
        $response = $this->client->get("food/ingredients/{$apiId}/information", [
            'query' => [ 'amount' => 1, 'apiKey' => $this->apiKey ]
        ]);
        $data = json_decode($response->getBody(), true);

        if (empty($data)) return null;

        return [
            'name' => $data['name'],
            'image_url' => "https://img.spoonacular.com/ingredients_250x250/" . $data['image'],
            'category' => $data['categoryPath'][0] ?? 'uncategorized',
            'nutrition_info' => json_encode($data['nutrition'] ?? []),
        ];
    }

    /**
     * Fetch product details by product ID.
     * @throws GuzzleException
     */
    private function fetchProductDetails(int $apiId): ?array
    {
        $response = $this->client->get("food/products/{$apiId}", [
            'query' => ['apiKey' => $this->apiKey]
        ]);
        $data = json_decode($response->getBody(), true);

        if (empty($data)) {
            return null;
        }

        $imageType = $data['imageType'] ?? 'jpg';
        return [
            'name'           => $data['title'] ?? '',
            // Recommended product image pattern
            'image_url'      => "https://img.spoonacular.com/products/{$apiId}-312x231.{$imageType}",
            'category'       => $data['aisle'] ?? ($data['breadcrumbs'][0] ?? 'uncategorized'),
            'nutrition_info' => $data['nutrition'] ?? null, // keep as array; your model can json_encode
        ];
    }

    private function normalizeName(string $name): string {
        $name = strtolower($name);
        $commonWords = ['organic', 'fresh', 'whole', 'natural', 'free-range'];
        $name = preg_replace('/\b(' . implode('|', $commonWords) . ')\b/i', '', $name);
        if (str_ends_with($name, 's') && !str_ends_with($name, 'ss')) {
            $name = rtrim($name, 's');
        }
        return trim(preg_replace('/\s+/', ' ', $name));
    }
}

