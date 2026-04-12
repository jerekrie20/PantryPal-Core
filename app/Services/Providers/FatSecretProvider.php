<?php

namespace Services\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Models\FatSecretCache;

/**
 * FatSecret Platform API client — food search and retrieval.
 *
 * ToS compliance:
 *   Full JSON responses are stored in fatsecret_cache (24-hour TTL only).
 *   Nutritional data is NEVER written to the permanent ingredients/products tables.
 *
 * Auth: OAuth 2.0 client_credentials flow.
 *   Token endpoint : https://oauth.fatsecret.com/connect/token
 *   API base       : https://platform.fatsecret.com/rest/
 *   Env vars       : FATSECRET_CLIENT_ID, FATSECRET_CLIENT_SECRET
 */
class FatSecretProvider
{
    private const TOKEN_URL = 'https://oauth.fatsecret.com/connect/token';
    private const API_BASE  = 'https://platform.fatsecret.com/rest/';
    // Reserved cache key for the Bearer token
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
    // Public API methods
    // -------------------------------------------------------------------------

    /**
     * Search foods via foods.search v1.
     * Returns normalized results; full response cached for 24 hours.
     *
     * @return array<int, array{api_id:string,name:string,brand:string|null,type:string,url:string|null,source:string}>
     */
    public function searchFoods(string $query, int $maxResults = 10): array
    {
        if (!$this->isConfigured() || trim($query) === '') {
            return [];
        }
        $params = [
            'method'       => 'foods.search',
            'search_expression' => $query,
            'max_results'  => max(1, min(50, $maxResults)),
            'format'       => 'json',
        ];
        $data = $this->cachedCall('foods.search', $params);
        if (!$data) {
            return [];
        }
        $foods = $data['foods']['food'] ?? [];
        // API returns a single object when there is exactly one result
        if (isset($foods['food_id'])) {
            $foods = [$foods];
        }
        $out = [];
        foreach ($foods as $f) {
            $out[] = [
                'api_id' => (string)($f['food_id'] ?? ''),
                'name'   => (string)($f['food_name'] ?? ''),
                'brand'  => isset($f['brand_name']) && $f['brand_name'] !== '' ? (string)$f['brand_name'] : null,
                'type'   => (string)($f['food_type'] ?? 'Generic'), // 'Generic' or 'Brand'
                'url'    => isset($f['food_url']) && $f['food_url'] !== '' ? (string)$f['food_url'] : null,
                'source' => 'fatsecret',
            ];
        }
        return $out;
    }

    /**
     * Fetch full food details via food.get v5.
     * Returns the raw decoded response array (cached); caller must not store permanently.
     */
    public function getFood(int|string $foodId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $params = [
            'method'  => 'food.get.v5',
            'food_id' => (string)$foodId,
            'format'  => 'json',
        ];
        return $this->cachedCall('food.get.v5', $params);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Check cache → on miss call the API → store result → return decoded data.
     */
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

    /**
     * Execute a signed API call to platform.fatsecret.com/rest/server.api.
     */
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
            error_log('FatSecretProvider API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Return a valid Bearer token, fetching a new one via client_credentials if needed.
     * The token is cached in fatsecret_cache under a reserved key.
     * We embed an explicit expires_at in the stored JSON so we can detect near-expiry
     * without relying solely on the row's created_at timestamp.
     */
    private function getBearerToken(): ?string
    {
        // Check cached token
        $cached = $this->cache->get(self::TOKEN_CACHE_KEY);
        if ($cached !== null && isset($cached['access_token'], $cached['expires_at'])) {
            // Treat as valid if it expires more than 60 seconds from now
            if ($cached['expires_at'] > time() + 60) {
                return (string)$cached['access_token'];
            }
        }
        // Fetch a new token
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
                error_log('FatSecretProvider: token response missing access_token');
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
            error_log('FatSecretProvider token fetch error: ' . $e->getMessage());
            return null;
        }
    }
}
