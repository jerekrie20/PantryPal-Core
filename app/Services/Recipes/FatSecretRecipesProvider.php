<?php

namespace Services\Recipes;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Helpers\Cache;

/**
 * FatSecret recipe search and retrieval.
 * Implements RecipesProvider so it plugs directly into RecipesController.
 *
 * ToS compliance:
 *   Full JSON is cached in Redis (24-hour TTL only).
 *   Only recipe_id (api_id) is written permanently to the recipes table.
 *   raw_payload is intentionally NULL for FatSecret rows.
 *
 * Auth: OAuth 2.0 client_credentials (shared with FatSecretProvider).
 *   Env vars: FATSECRET_CLIENT_ID, FATSECRET_CLIENT_SECRET
 */
class FatSecretRecipesProvider implements RecipesProvider
{
    private const TOKEN_URL       = 'https://oauth.fatsecret.com/connect/token';
    private const API_BASE        = 'https://platform.fatsecret.com/rest/';
    private const TOKEN_CACHE_KEY = 'fatsecret:__oauth_token__';
    private const CACHE_TTL       = 86400; // 24 hours

    private string $clientId;
    private string $clientSecret;
    private Client $http;

    public function __construct(
        ?string $clientId     = null,
        ?string $clientSecret = null,
        ?Client $http         = null
    ) {
        $this->clientId     = $clientId     ?? ($_ENV['FATSECRET_CLIENT_ID']     ?? '');
        $this->clientSecret = $clientSecret ?? ($_ENV['FATSECRET_CLIENT_SECRET'] ?? '');
        $this->http = $http ?? new Client(['timeout' => 8.0]);
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    // -------------------------------------------------------------------------
    // RecipesProvider interface
    // -------------------------------------------------------------------------

    /**
     * Search recipes by text query via recipes/search/v3.
     */
    public function searchByQuery(string $query, int $number = 12): array
    {
        if (!$this->isConfigured() || trim($query) === '') {
            return [];
        }
        $params = [
            'search_expression' => $query,
            'max_results'       => max(1, min(50, $number)),
            'format'            => 'json',
        ];
        $data = $this->cachedCall('recipes.search.v3', self::API_BASE . 'recipes/search/v3', $params);
        return $this->normalizeSearchResults($data);
    }

    /**
     * Find recipes by ingredient list — joins terms into a single query.
     */
    public function findByIngredients(array $ingredients, int $number = 12): array
    {
        if (!$this->isConfigured() || empty($ingredients)) {
            return [];
        }
        $query = implode(' ', array_filter(array_map('trim', $ingredients)));
        if ($query === '') {
            return [];
        }
        return $this->searchByQuery($query, $number);
    }

    // -------------------------------------------------------------------------
    // Extended method
    // -------------------------------------------------------------------------

    /**
     * Fetch full recipe detail via recipe/v2.
     * Returns normalized recipe array including nutrition_per_serving (from Redis cache).
     * Caller must not store raw_payload.
     */
    public function getRecipeById(string $id): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $params = [
            'recipe_id' => $id,
            'format'    => 'json',
        ];
        $data = $this->cachedCall('recipe.get.v2', self::API_BASE . 'recipe/v2', $params);
        if (!$data) {
            return null;
        }
        $r = $data['recipe'] ?? $data;
        if (empty($r['recipe_id'])) {
            return null;
        }
        return $this->normalizeDetail($r);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function cacheKey(string $endpoint, array $params): string
    {
        ksort($params);
        return 'fatsecret:' . hash('sha256', $endpoint . json_encode($params));
    }

    /**
     * Check Redis → on miss call the API → cache result → return decoded data.
     */
    private function cachedCall(string $endpoint, string $url, array $params): ?array
    {
        $key = $this->cacheKey($endpoint, $params);
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit;
        }
        $data = $this->callApi($url, $params);
        if ($data !== null) {
            Cache::set($key, $data, self::CACHE_TTL);
        }
        return $data;
    }

    private function callApi(string $url, array $params): ?array
    {
        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }
        try {
            $resp = $this->http->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query'   => $params,
            ]);
            $body    = (string)$resp->getBody();
            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                error_log('FatSecretRecipesProvider: non-JSON response url=' . $url . ' body=' . substr($body, 0, 500));
                return null;
            }
            if (isset($decoded['error'])) {
                error_log('FatSecretRecipesProvider API error: ' . json_encode($decoded['error']));
                return null;
            }
            return $decoded;
        } catch (GuzzleException $e) {
            error_log('FatSecretRecipesProvider HTTP error: ' . $e->getMessage());
            return null;
        }
    }

    private function getBearerToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_array($cached) && isset($cached['access_token'], $cached['expires_at'])) {
            if ($cached['expires_at'] > time() + 60) {
                return (string)$cached['access_token'];
            }
        }
        try {
            $resp = $this->http->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => 'basic',
                ],
            ]);
            $data = json_decode((string)$resp->getBody(), true);
            if (!isset($data['access_token'])) {
                error_log('FatSecretRecipesProvider: token response missing access_token');
                return null;
            }
            $expiresIn = (int)($data['expires_in'] ?? 86400);
            $payload   = [
                'access_token' => $data['access_token'],
                'expires_at'   => time() + $expiresIn,
            ];
            Cache::set(self::TOKEN_CACHE_KEY, $payload, max(60, $expiresIn - 60));
            return (string)$data['access_token'];
        } catch (GuzzleException $e) {
            error_log('FatSecretRecipesProvider token fetch error: ' . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Normalization
    // -------------------------------------------------------------------------

    private function normalizeSearchResults(?array $data): array
    {
        if (!$data) {
            return [];
        }
        $recipes = $data['recipes']['recipe'] ?? [];
        // API returns a single object when there is exactly one result
        if (isset($recipes['recipe_id'])) {
            $recipes = [$recipes];
        }
        $out = [];
        foreach ($recipes as $r) {
            $out[] = [
                'id'                => (string)($r['recipe_id'] ?? ''),
                'title'             => (string)($r['recipe_name'] ?? ''),
                'image'             => isset($r['recipe_image']) && $r['recipe_image'] !== '' ? (string)$r['recipe_image'] : null,
                'sourceUrl'         => isset($r['recipe_url']) && $r['recipe_url'] !== '' ? (string)$r['recipe_url'] : null,
                'usedIngredients'   => [],
                'missedIngredients' => [],
                'source'            => 'fatsecret',
            ];
        }
        return $out;
    }

    private function normalizeDetail(array $r): array
    {
        // Ingredient lines
        $ingredientLines = [];
        $ingredients = $r['ingredients']['ingredient'] ?? [];
        if (isset($ingredients['ingredient_id'])) {
            $ingredients = [$ingredients];
        }
        foreach ($ingredients as $ing) {
            $line = trim((string)($ing['ingredient_description'] ?? $ing['food_name'] ?? ''));
            if ($line !== '') {
                $ingredientLines[] = $line;
            }
        }

        // Instruction steps
        $steps = [];
        $directions = $r['directions']['direction'] ?? [];
        if (isset($directions['direction_number'])) {
            $directions = [$directions];
        }
        usort($directions, static fn($a, $b) => (int)($a['direction_number'] ?? 0) <=> (int)($b['direction_number'] ?? 0));
        foreach ($directions as $dir) {
            $step = trim((string)($dir['direction_description'] ?? ''));
            if ($step !== '') {
                $steps[] = $step;
            }
        }

        // Nutrition per serving from serving_sizes.serving
        $nutrition = $this->normalizeNutrition($r['serving_sizes']['serving'] ?? null);

        return [
            'id'                  => (string)($r['recipe_id'] ?? ''),
            'title'               => (string)($r['recipe_name'] ?? ''),
            'image'               => isset($r['recipe_image']) && $r['recipe_image'] !== '' ? (string)$r['recipe_image'] : null,
            'sourceUrl'           => isset($r['recipe_url']) && $r['recipe_url'] !== '' ? (string)$r['recipe_url'] : null,
            'usedIngredients'     => [],
            'missedIngredients'   => [],
            'ingredients_list'    => $ingredientLines,
            'instructions_list'   => $steps,
            'nutrition_per_serving' => $nutrition,
            'source'              => 'fatsecret',
        ];
    }

    /**
     * Convert a FatSecret serving_sizes.serving object into the nutrition_per_serving
     * format expected by the Recipes/show.php view:
     *   [ 'Calories' => ['amount' => 350.0, 'unit' => 'kcal'], ... ]
     */
    private function normalizeNutrition(?array $s): array
    {
        if (empty($s)) {
            return [];
        }

        $map = [
            'calories'            => ['Calories',        'kcal'],
            'protein'             => ['Protein',          'g'],
            'carbohydrate'        => ['Carbohydrates',    'g'],
            'fat'                 => ['Fat',              'g'],
            'saturated_fat'       => ['Saturated Fat',    'g'],
            'polyunsaturated_fat' => ['Polyunsaturated Fat', 'g'],
            'monounsaturated_fat' => ['Monounsaturated Fat', 'g'],
            'trans_fat'           => ['Trans Fat',        'g'],
            'fiber'               => ['Fiber',            'g'],
            'sugar'               => ['Sugar',            'g'],
            'sodium'              => ['Sodium',           'mg'],
            'potassium'           => ['Potassium',        'mg'],
            'cholesterol'         => ['Cholesterol',      'mg'],
            'calcium'             => ['Calcium',          'mg'],
            'iron'                => ['Iron',             'mg'],
            'vitamin_a'           => ['Vitamin A',        'IU'],
            'vitamin_c'           => ['Vitamin C',        'mg'],
        ];

        $out = [];
        foreach ($map as $key => [$label, $unit]) {
            if (isset($s[$key]) && $s[$key] !== '' && $s[$key] !== null) {
                $amount = (float)$s[$key];
                if ($amount > 0 || $key === 'calories') {
                    $out[$label] = ['amount' => $amount, 'unit' => $unit];
                }
            }
        }
        return $out;
    }
}
