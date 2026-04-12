<?php

namespace Services\Recipes;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Models\FatSecretCache;

/**
 * FatSecret recipe search and retrieval.
 * Implements RecipesProvider so it plugs directly into RecipesController.
 *
 * ToS compliance:
 *   Full JSON is stored only in fatsecret_cache (24-hour TTL).
 *   Only recipe_id (api_id) is written permanently to the recipes table.
 *   raw_payload is intentionally left NULL for FatSecret rows.
 *
 * Auth: OAuth 2.0 client_credentials (shared with FatSecretProvider).
 *   Env vars: FATSECRET_CLIENT_ID, FATSECRET_CLIENT_SECRET
 */
class FatSecretRecipesProvider implements RecipesProvider
{
    private const TOKEN_URL       = 'https://oauth.fatsecret.com/connect/token';
    private const API_BASE        = 'https://platform.fatsecret.com/rest/';
    private const TOKEN_CACHE_KEY = '__fatsecret_oauth_token__';
    private const TOKEN_ENDPOINT  = '__oauth_token__';

    private string $clientId;
    private string $clientSecret;
    private Client $http;
    private FatSecretCache $cache;

    public function __construct(
        ?string $clientId     = null,
        ?string $clientSecret = null,
        ?Client $http         = null,
        ?FatSecretCache $cache = null
    ) {
        $this->clientId     = $clientId     ?? ($_ENV['FATSECRET_CLIENT_ID']     ?? '');
        $this->clientSecret = $clientSecret ?? ($_ENV['FATSECRET_CLIENT_SECRET'] ?? '');
        $this->http  = $http  ?? new Client(['timeout' => 8.0]);
        $this->cache = $cache ?? new FatSecretCache();
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    // -------------------------------------------------------------------------
    // RecipesProvider interface
    // -------------------------------------------------------------------------

    /**
     * Search recipes by text query via recipes.search v3.
     */
    public function searchByQuery(string $query, int $number = 12): array
    {
        if (!$this->isConfigured() || trim($query) === '') {
            return [];
        }
        $params = [
            'method'       => 'recipes.search.v3',
            'search_expression' => $query,
            'max_results'  => max(1, min(50, $number)),
            'format'       => 'json',
        ];
        $data = $this->cachedCall('recipes.search.v3', $params);
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
    // Extended method (mirrors SuggesticProvider pattern used in RecipesController)
    // -------------------------------------------------------------------------

    /**
     * Fetch full recipe detail via recipe.get v2.
     * Returns the raw (normalized) recipe array from cache; caller must not store raw_payload.
     */
    public function getRecipeById(string $id): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $params = [
            'method'    => 'recipe.get.v2',
            'recipe_id' => $id,
            'format'    => 'json',
        ];
        $data = $this->cachedCall('recipe.get.v2', $params);
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

    private function cachedCall(string $endpoint, array $params): ?array
    {
        $key = $this->cache->makeKey($endpoint, $params);
        $hit = $this->cache->get($key);
        if ($hit !== null) {
            return $hit;
        }
        $data = $this->callApi($params);
        if ($data !== null) {
            $this->cache->set($key, $endpoint, $data);
        }
        return $data;
    }

    private function callApi(array $params): ?array
    {
        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }
        try {
            $resp = $this->http->post(self::API_BASE . 'server.api', [
                'headers'     => ['Authorization' => 'Bearer ' . $token],
                'form_params' => $params,
            ]);
            $decoded = json_decode((string)$resp->getBody(), true);
            return is_array($decoded) ? $decoded : null;
        } catch (GuzzleException $e) {
            error_log('FatSecretRecipesProvider API error: ' . $e->getMessage());
            return null;
        }
    }

    private function getBearerToken(): ?string
    {
        $cached = $this->cache->get(self::TOKEN_CACHE_KEY);
        if ($cached !== null && isset($cached['access_token'], $cached['expires_at'])) {
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
            $this->cache->set(self::TOKEN_CACHE_KEY, self::TOKEN_ENDPOINT, $payload);
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
                'id'               => (string)($r['recipe_id'] ?? ''),
                'title'            => (string)($r['recipe_name'] ?? ''),
                'image'            => isset($r['recipe_image']) && $r['recipe_image'] !== '' ? (string)$r['recipe_image'] : null,
                'sourceUrl'        => isset($r['recipe_url']) && $r['recipe_url'] !== '' ? (string)$r['recipe_url'] : null,
                'usedIngredients'  => [],
                'missedIngredients'=> [],
                'source'           => 'fatsecret',
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

        return [
            'id'               => (string)($r['recipe_id'] ?? ''),
            'title'            => (string)($r['recipe_name'] ?? ''),
            'image'            => isset($r['recipe_image']) && $r['recipe_image'] !== '' ? (string)$r['recipe_image'] : null,
            'sourceUrl'        => isset($r['recipe_url']) && $r['recipe_url'] !== '' ? (string)$r['recipe_url'] : null,
            'usedIngredients'  => [],
            'missedIngredients'=> [],
            'ingredients_list' => $ingredientLines,
            'instructions_list'=> $steps,
            'source'           => 'fatsecret',
        ];
    }
}
